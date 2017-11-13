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

  class wbDataMine_Services extends wbDataMine {

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
          $ordering = array('tblproducts.order','asc');
        $tableheadings = $this->createTableHeadings(array(
          'line_number'           => array('label'=>'#', 'field'=>''),
          'tblproductgroups.name' => array('label'=>'Group'),
          'tblproducts.name'      => array('label'=>'Service'),
          'count_new'             => array('label'=>'# New'),
          'count_active'          => array('label'=>'# Active'),
          'count_cancelled'       => array('label'=>'# Cancelled'),
          'total_paid'            => array('label'=>'Total Paid'),
          ), $ordering);

      /************************************************************************************************************
       * Report Setup
       ************************************************************************************************************/

        $this->setReportData(array(
          'title'         => 'wbDataMine: Services',
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
            `productgroup`.`id`
            , `productgroup`.`name`
          FROM `tblproductgroups` AS `productgroup`
          ORDER BY `productgroup`.`order`
          ");
        $productGroups = $dbh->getRows();
        $productGroupOptions = array();
        foreach ($productGroups AS $productGroups) {
          $productGroupOptions[ $productGroups['id'] ] = $productGroups['name'];
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
          'packagegroupid'  => array(
            'type'      => 'select',
            'label'     => 'Product Group',
            'default'   => '',
            'multiple'  => true,
            'options'   => $productGroupOptions
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
        $dateMin        = date('Y-m-d',strtotime($filterData['datemin']));
        $dateMax        = date('Y-m-d',strtotime($filterData['datemax']));
        $packagegroupid = array_filter(array_map('intval', $filterData['packagegroupid']));
        $result = $dbh->runQuery("
                    SELECT
                      `tblproducts`.*
                      , `tblproductgroups`.`name` AS `group_name`
                      , (
                        SELECT COUNT(DISTINCT(`sub_tblhosting`.`id`))
                        FROM `tblhosting` AS `sub_tblhosting`
                        WHERE `sub_tblhosting`.`packageid` = `tblproducts`.`id`
                          AND `sub_tblhosting`.`regdate` >= '". $dbh->getEscaped($dateMin) ."'
                          AND `sub_tblhosting`.`regdate` <= '". $dbh->getEscaped($dateMax) ."'
                        ) AS `count_new`
                      , (
                        SELECT COUNT(DISTINCT(`sub_tblhosting`.`id`))
                        FROM `tblhosting` AS `sub_tblhosting`
                        WHERE `sub_tblhosting`.`packageid` = `tblproducts`.`id`
                          AND `sub_tblhosting`.`domainstatus` = 'Active'
                          AND `sub_tblhosting`.`regdate` <= '". $dbh->getEscaped($dateMax) ."'
                          AND (`sub_tblhosting`.`termination_date` >= '". $dbh->getEscaped($dateMin) ."' OR `sub_tblhosting`.`termination_date` = '0000-00-00 00:00:00')
                        ) AS `count_active`
                      , (
                        SELECT COUNT(DISTINCT(`sub_tblhosting`.`id`))
                        FROM `tblhosting` AS `sub_tblhosting`
                        WHERE `sub_tblhosting`.`packageid` = `tblproducts`.`id`
                          AND `sub_tblhosting`.`domainstatus` != 'Active'
                          AND `sub_tblhosting`.`termination_date` <= '". $dbh->getEscaped($dateMax) ."'
                          AND `sub_tblhosting`.`termination_date` >= '". $dbh->getEscaped($dateMin) ."'
                        ) AS `count_cancelled`
                      , (
                        SELECT SUM(`sub_tblinvoiceitems`.`amount`)
                        FROM `tblhosting` AS `sub_tblhosting`
                        LEFT JOIN `tblinvoiceitems` AS `sub_tblinvoiceitems` ON `sub_tblinvoiceitems`.`type` = 'Hosting' AND `sub_tblinvoiceitems`.`relid` = `sub_tblhosting`.`id`
                        LEFT JOIN `tblinvoices` AS `sub_tblinvoices` ON `sub_tblinvoices`.`id` = `sub_tblinvoiceitems`.`invoiceid`
                        WHERE `sub_tblhosting`.`packageid` = `tblproducts`.`id`
                          AND `sub_tblinvoices`.`status` = 'Paid'
                          AND `sub_tblinvoices`.`date` >= '". $dbh->getEscaped($dateMin) ."'
                          AND `sub_tblinvoices`.`date` <= '". $dbh->getEscaped($dateMax) ."'
                        ) AS `total_paid`
                    FROM `tblproducts`
                    LEFT JOIN `tblproductgroups` ON `tblproductgroups`.`id` = `tblproducts`.`gid`
                    WHERE 1
                      ". ($packagegroupid ? "AND `tblproductgroups`.`id` IN ('". implode("','", $packagegroupid) ."')" : '') ."
                    GROUP BY `tblproducts`.`id`
                    ORDER BY ". $dbh->getEscaped($ordering[0]) .' '. $dbh->getEscaped($ordering[1]) ."
                      , `tblproductgroups`.`order`
                      , `tblproducts`.`order`
                    ");
        $rows = $dbh->getRows();

      /************************************************************************************************************
       * Data Rows
       ************************************************************************************************************/

        $rowCount             = 0;
        $grandTotalPaid       = 0;
        $lineNumber           = 1;
        foreach ($rows AS $row) {
          $rowCount++;
          $startDate = $row['regdate'];
          $closeDate = $row['termination_date'] ?: date('Y-m-d');
          $reportLine = array(
            $lineNumber++,
            '<a target="_blank" href="configproducts.php?action=edit&id='. $row['id'] . '">'.$row['group_name'].' #'.$row['gid'] .'</a>',
            '<a target="_blank" href="configproducts.php?action=edit&id='. $row['id'] . '">'.$row['name'].' #'.$row['id'] .'</a>',
            $row['count_new'],
            $row['count_active'],
            $row['count_cancelled'],
            $this->curFormat($row['total_paid']),
            );
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
            $this->curFormat($grandTotalPaid / $rowCount)
            );
          $this->_reportData["tablevalues"][] = $reportLine;
        }

    }

  }

/************************************************************************************************************
 * Header & Footer Text
 ************************************************************************************************************/

  $reportdata = (new wbDataMine_Services())->getReportData();
