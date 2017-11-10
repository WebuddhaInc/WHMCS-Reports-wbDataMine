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

  class wbDataMine_ServiceReport extends wbDataMine {

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
          $ordering = array('tblhosting.id','asc');
        $tableheadings = $this->createTableHeadings(array(
          'line_number'                   => array('label'=>'#', 'field'=>''),
          'tblclients.id'                 => array('label'=>'Client ID'),
          'tblclients.lastname'           => array('label'=>'Client'),
          'tblhosting.id'                 => array('label'=>'Service ID'),
          'tblhosting.regdate'            => array('label'=>'Date'),
          'tblhosting.nextduedate'        => array('label'=>'Due Date'),
          'tblhosting.termination_date'   => array('label'=>'Termination Date'),
          'tblhosting.domainstatus'       => array('label'=>'Status'),
          'tblhosting.firstpaymentamount' => array('label'=>'First Payment'),
          'tblhosting.amount'             => array('label'=>'Recurring Payment'),
          'tblhosting.billingcycle'       => array('label'=>'Billing Cycle'),
          'total_months'                  => array('label'=>'Total Months'),
          'total_paid'                    => array('label'=>'Total Paid'),
          ), $ordering);

      /************************************************************************************************************
       * Report Setup
       ************************************************************************************************************/

        $this->setReportData(array(
          'title'         => 'wbDataMine: Service Report',
          'description'   => 'This report shows client services for a product.',
          'headertext'    => '',
          'footertext'    => '',
          'tableheadings' => $tableheadings,
          'tablevalues'   => array()
          ));

      /************************************************************************************************************
       * Report Filters
       ************************************************************************************************************/

        $dbh = $this->dbh();
        $dbh->runQuery("
          SELECT
            `product`.`id`
            , CONCAT(`productgroup`.`name`, ' > ', `product`.`name`) AS `name`
          FROM `tblproducts` AS `product`
          LEFT JOIN `tblproductgroups` AS `productgroup` ON `productgroup`.`id` = `product`.`gid`
          ORDER BY `productgroup`.`order`
            , `product`.`order`
          ");
        $products = $dbh->getRows();
        $productOptions = array();
        foreach ($products AS $product) {
          $productOptions[ $product['id'] ] = $product['name'];
        }

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
          'status'  => array(
            'type'      => 'select',
            'label'     => 'Filter by Status',
            'default'   => '',
            'multiple'  => true,
            'options'   => array(
              'Active'     => 'Active',
              'Pending'    => 'Pending',
              'Completed'  => 'Completed',
              'Suspended'  => 'Suspended',
              'Terminated' => 'Terminated',
              'Cancelled'  => 'Cancelled',
              'Fraud'      => 'Fraud'
            )
          ),
          'packageid'  => array(
            'type'      => 'select',
            'label'     => 'Product',
            'default'   => '',
            'options'   => $productOptions
          )
          ));

      /************************************************************************************************************
       * Update Filter Data
       ************************************************************************************************************/

        $filterData =& $this->getFilterData();
        $filterData['status'] = (array)@$filterData['status'];

      /************************************************************************************************************
       * Header & Footer Text
       ************************************************************************************************************/

        if( !$this->_printMode )
          $this->applyFilterForm($filterData);

      /************************************************************************************************************
       * Query
       ************************************************************************************************************/

        $dbh = $this->dbh();
        $dateMin   = date('Y-m-d',strtotime($filterData['datemin']));
        $dateMax   = date('Y-m-d',strtotime($filterData['datemax']));
        $packageid = (int)$filterData['packageid'];
        $status    = array_filter(array_walk($filterData['status'], function($v){ return preg_replace('/[^A-Za-z]/', '', $v); }));
        $result = $dbh->runQuery("
                    SELECT `tblhosting`.*
                      , CONCAT(`tblclients`.`firstname`, ' ', `tblclients`.`lastname`) AS 'fullname'
                      , SUM(IF(`tblinvoices`.`status` = 'Paid', `tblinvoiceitems`.`amount`, 0)) AS `total_paid`
                      , TIMESTAMPDIFF(MONTH, `tblhosting`.`regdate`, IF(`tblhosting`.`termination_date`, `tblhosting`.`termination_date`, CURDATE())) AS `total_months`
                    FROM `tblhosting`
                    LEFT JOIN `tblclients` ON `tblclients`.`id` = `tblhosting`.`userid`
                    LEFT JOIN `tblinvoiceitems` ON `tblinvoiceitems`.`relid` = `tblhosting`.`id`
                    LEFT JOIN `tblinvoices` ON `tblinvoices`.`id` = `tblinvoiceitems`.`invoiceid`
                    WHERE `tblhosting`.`regdate` >= '". $dbh->getEscaped($dateMin) ."'
                      AND `tblhosting`.`regdate` <= '". $dbh->getEscaped($dateMax) ."'
                      ". ($packageid ? "AND `tblhosting`.`packageid` = '". $dbh->getEscaped($packageid) ."'" : '') ."
                      ". ($status ? "AND `tblhosting`.`domainstatus` IN ('". implode("','", $status) ."')" : '') ."
                    GROUP BY `tblhosting`.`id`
                    ORDER BY ". $dbh->getEscaped($ordering[0]) .' '. $dbh->getEscaped($ordering[1])
                    );
        $rows = $dbh->getRows();

      /************************************************************************************************************
       * Data Rows
       ************************************************************************************************************/

        $rowCount       = 0;
        $grandTotal     = 0;
        $grandTotalPaid = 0;
        $totalMonths    = 0;
        $lineNumber     = 1;
        foreach ($rows AS $row) {
          $rowCount++;
          $startDate = $row['regdate'];
          $closeDate = $row['termination_date'] ?: date('Y-m-d');
          $reportLine = array(
            $lineNumber++,
            '<a target="_blank" href="clientssummary.php?userid='. $row['userid'] . '">'.$row['userid'] .'</a>',
            '<a target="_blank" href="clientssummary.php?userid='. $row['userid'] . '">'. $row['fullname'] .'</a>',
            '<a target="_blank" href="clientsservices.php?userid='. $row['userid'] . '&id='.$row['id'].'">'.$row['id'] .'</a>',
            fromMySQLDate($row['regdate']),
            fromMySQLDate($row['nextduedate']),
            fromMySQLDate($row['termination_date']),
            $row['domainstatus'],
            $this->curFormat($row['firstpaymentamount']),
            $this->curFormat($row['amount']),
            $row['billingcycle'],
            $row['total_months'],
            $this->curFormat($row['total_paid']),
            );
          $grandTotal += $row['firstpaymentamount'];
          $totalMonths += $row['total_months'];
          $grandTotalPaid += $row['total_paid'];
          $this->_reportData["tablevalues"][] = preg_replace('/^\*+/','',$this->_printMode ? $this->stripTags($reportLine) : $reportLine);
        }

      /************************************************************************************************************
       * Total Row
       ************************************************************************************************************/

        if( !$this->_printMode ){
          $reportLine = array(
            'Tot',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->curFormat($grandTotal),
            '',
            '',
            $totalMonths,
            $this->curFormat($grandTotalPaid)
            );
          $this->_reportData["tablevalues"][] = $reportLine;
          $reportLine = array(
            'Avg',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $this->curFormat($grandTotal / $rowCount),
            '',
            '',
            round($totalMonths / $rowCount, 2),
            $this->curFormat($grandTotalPaid / $rowCount)
            );
          $this->_reportData["tablevalues"][] = $reportLine;
        }

    }

  }

/************************************************************************************************************
 * Header & Footer Text
 ************************************************************************************************************/

  $reportdata = (new wbDataMine_ServiceReport())->getReportData();
