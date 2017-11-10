<?php

/*
  (c)2011 Webuddha, Holodyn.com
*/

if( !defined("WHMCS") ) die("This file cannot be accessed directly");

/************************************************************************************************************
 * Include wbDataMine Class
 ************************************************************************************************************/

  require 'wbDataMine.class.php';

/************************************************************************************************************
 * Report Class
 ************************************************************************************************************/

  class wbDataMine_Invoices extends wbDataMine {

    function __construct(){

      /************************************************************************************************************
       * Parent Init
       ************************************************************************************************************/

        parent::__construct();

      /************************************************************************************************************
       * Ordering / Headers
       ************************************************************************************************************/

        $ordering = explode('-', @$_REQUEST['order']);
        if( count($ordering) != 2 )
          $ordering = array('tblinvoices.id','asc');
        $tableheadings = $this->createTableHeadings(array(
          'line_number'          => array('label'=>'#', 'field'=>''),
          'tblinvoices.id'       => array('label'=>'ID'),
          'tblinvoices.date'     => array('label'=>'Date'),
          'tblinvoices.duedate'  => array('label'=>'Due Date'),
          'tblclients.lastname'  => array('label'=>'Client'),
          'tblinvoices.status'   => array('label'=>'Status'),
          'tblinvoices.datepaid' => array('label'=>'Date Paid'),
          'tblinvoices.total'    => array('label'=>'Amount'),
          'total_paid'           => array('label'=>'Paid')
          ), $ordering);

      /************************************************************************************************************
       * Report Setup
       ************************************************************************************************************/

        $this->setReportData(array(
          'title'         => 'wbDataMine: Invoice Filter',
          'description'   => 'This report will generate a table of invoices matching your filter criteria.',
          'headertext'    => '',
          'footertext'    => '',
          'tableheadings' => $tableheadings,
          'tablevalues'   => array()
          ));

      /************************************************************************************************************
       * Report Filters
       ************************************************************************************************************/

        $this->setFilterFields(array(
          'datemin'  => array(
            'type'      => 'text',
            'label'     => 'Start Date/Time',
            'default'   => date('m/d/Y',strtotime(date('Y-m').'-01')),
            'extra'     => 'size="24" class="datepick"'
          ),
          'datemax'  => array(
            'type'      => 'text',
            'label'     => 'Stop Date/Time',
            'default'   => date('m/d/Y',strtotime(date('Y-m-d',strtotime('Today')))),
            'extra'     => 'size="24" class="datepick"'
          ),
          'datefield' => array(
            'type'      => 'select',
            'label'     => 'Filter by Date Field',
            'default'   => 'date',
            'options'   => array(
              'date'        => 'Date Created',
              'duedate'     => 'Date Due',
              'datepaid'    => 'Date Paid'
            )
          ),
          'totalmin'  => array(
            'type'      => 'text',
            'label'     => 'Total Min',
            'default'   => '',
            'extra'     => 'size="12"'
          ),
          'totalmax'  => array(
            'type'      => 'text',
            'label'     => 'Total Max',
            'default'   => '',
            'extra'     => 'size="12"'
          ),
          'invstatus' => array(
            'type'      => 'select',
            'label'     => 'Invoice Status',
            'default'   => 'active',
            'options'   => array(
              'active'      => 'Paid & Unpaid',
              'Paid'        => 'Paid',
              'Unpaid'      => 'Unpaid',
              'Overdue'     => 'Overdue',
              'Cancelled'   => 'Cancelled',
              'Refunded'    => 'Refunded',
              'Collections' => 'Collections'
            )
          )
          ));

      /************************************************************************************************************
       * Update Filter Data
       ************************************************************************************************************/

        $filterData =& $this->getFilterData();
        if( !in_array($filterData['datefield'], array('date','duedate','datepaid')) )
          $filterData['datefield'] = 'date';

      /************************************************************************************************************
       * Header & Footer Text
       ************************************************************************************************************/

        if( !$this->_printMode )
          $this->applyFilterForm($filterData);

      /************************************************************************************************************
       * Query
       ************************************************************************************************************/

        $dbh = $this->dbh();
        $dateMin = date('Y-m-d',strtotime($filterData['datemin']));
        $dateMax = date('Y-m-d',strtotime($filterData['datemax']));
        $result = $dbh->runQuery("
                    SELECT `tblinvoices`.*
                      , CONCAT(`tblclients`.`firstname`, ' ', `tblclients`.`lastname`) AS 'fullname'
                      , (
                        SELECT SUM((`tblaccounts`.`amountin` - `tblaccounts`.`amountout`))
                        FROM `tblaccounts`
                        WHERE `tblaccounts`.`invoiceid` = `tblinvoices`.`id`
                        ) AS `total_paid`
                    FROM `tblinvoices`, `tblclients`
                    WHERE `tblclients`.`id` = `tblinvoices`.`userid`
                      AND `tblinvoices`.`".$filterData['datefield']."` >= '". $dbh->getEscaped($dateMin) ."'
                      AND `tblinvoices`.`".$filterData['datefield']."` <= '". $dbh->getEscaped($dateMax) ."'
                      ". (strlen($filterData['totalmin']) ? "AND `tblinvoices`.`total` >= '". $dbh->getEscaped($filterData['totalmin']) ."'" : "") ."
                      ". (strlen($filterData['totalmax']) ? "AND `tblinvoices`.`total` <= '". $dbh->getEscaped($filterData['totalmax']) ."'" : "") ."
                      ". ($filterData['invstatus'] == '' ? "" : ($filterData['invstatus'] == 'active' ? "AND `tblinvoices`.`status` IN ('paid','unpaid')" : "AND `tblinvoices`.`status` = '". $dbh->getEscaped($filterData['invstatus']) ."'")) ."
                    ORDER BY ". $dbh->getEscaped($ordering[0]) .' '. $dbh->getEscaped($ordering[1])
                    );
        $rows = $dbh->getRows();

      /************************************************************************************************************
       * Data Rows
       ************************************************************************************************************/

        $lineNumber     = 1;
        $grandTotal     = 0;
        $grandTotalPaid = 0;
        foreach ($rows AS $row) {
          $reportLine = array(
            $lineNumber++,
            '<a target="_blank" href="invoices.php?action=edit&id='.$row['id'].'">'.$row['id'] .'</a>',
            fromMySQLDate($row['date']),
            fromMySQLDate($row['duedate']),
            '<a target="_blank" href="clientssummary.php?userid='. $row['userid'] . '">'. $row['fullname'] .'</a>',
            '<span style="color:'. ($row['status'] == 'Paid'?'green':($row['status'] == 'Unpaid'?'darkred':'lightgrey')) .'">'. $row['status'] .'<span>',
            (strpos($row['datepaid'],'0000-00-00')===0 ? '-' : fromMySQLDate($row['datepaid'])),
            $this->curFormat($row['total']),
            $this->curFormat($row['total_paid'])
            );
          $grandTotal += (in_array($row['status'],array('Paid','Unpaid'))?$row['total']:0);
          $grandTotalPaid += $row['total_paid'];
          $this->_reportData["tablevalues"][] = preg_replace('/^\*+/','',$this->_printMode ? $this->stripTags($reportLine) : $reportLine);
        }

      /************************************************************************************************************
       * Total Row
       ************************************************************************************************************/

        if( !$isPrintFormat ){
          $reportLine = array(
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->curFormat($grandTotal),
            $this->curFormat($grandTotalPaid)
            );
          $this->_reportData["tablevalues"][] = $reportLine;
        }

    }

  }

/************************************************************************************************************
 * Header & Footer Text
 ************************************************************************************************************/

  $reportdata = (new wbDataMine_Invoices())->getReportData();
