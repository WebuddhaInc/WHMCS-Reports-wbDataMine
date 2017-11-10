<?php

/*
  (c)2011 Webuddha, Holodyn.com
*/

if( !defined("WHMCS") ) die("This file cannot be accessed directly");

/************************************************************************************************************
 * Include wbDataMine Class
 ************************************************************************************************************/

  require 'wbDataMine.class.php';
  $wbDataMine = new wbDataMine();

/************************************************************************************************************
 * Import WHMCS
 ************************************************************************************************************/

  global $CONFIG, $CurrencySymbol;
  $CurrencySymbol = isset($CurrencySymbol) ? $CurrencySymbol : (isset($CONFIG["CurrencySymbol"]) ? $CONFIG["CurrencySymbol"] : '$');
  $isPrintFormat  = isset($_GET['print']) && $_GET['print'] == 'true';

/************************************************************************************************************
 * Report Setup
 ************************************************************************************************************/

  $reportdata["title"]          = 'wbDataMine: Invoice Filter';
  $reportdata["description"]    = 'This report will generate a table of invoices matching your filter criteria.';
  $reportdata["tableheadings"]  = array();
  $reportdata["tablevalues"]    = array();
  $reportdata["headertext"]     = '';
  $reportdata["footertext"]     = '';

/************************************************************************************************************
 * Report Columns
 ************************************************************************************************************/

  $ordering = explode('-',$_REQUEST['order']);
  if( count($ordering) != 2 ) $ordering = array('tblhosting.id','asc');
  $headerColumns = array(
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
    );
  foreach($headerColumns AS $colKey => $colCfg)
    if( $isPrintFormat )
      $reportdata["tableheadings"][] = $colCfg['label'];
    else if( isset($colCfg['field']) && empty($colCfg['field']) )
      $reportdata["tableheadings"][] = $colCfg['label'];
    else
      $reportdata["tableheadings"][] = '<a href="javascript:void(0);" onclick="wbReportForm_sort(this);" order="'.($colCfg['field']?$colCfg['field']:$colKey).'-'.($ordering[1]=='asc'?'desc':'asc').'"'.(($colCfg['field']?$colCfg['field']:$colKey) == $ordering[0]?' class="active"':'').'>'.$colCfg['label'].'</a>';

/************************************************************************************************************
 * Report Filters
 ************************************************************************************************************/

  $filter = $_REQUEST['filter'];
  $filterFields = array(
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
    'packageid'  => array(
      'type'      => 'text',
      'label'     => 'Product ID',
      'default'   => '',
      'extra'     => 'size="12"'
    ),
  );
  if( !is_array($filter) ){
    $filter = array();
    foreach( $filterFields AS $filterField => $filterConfig )
      $filter[$filterField] = $filterConfig['default'];
  }

/************************************************************************************************************
 * Header & Footer Text
 ************************************************************************************************************/

  if( !$isPrintFormat ){
    $reportdata["headertext"] .= '
      <form method="get" action="reports.php" id="wbReportForm">
      <input type="hidden" name="print" value="" />
      <input type="hidden" name="report" value="'. $_REQUEST['report'] .'" />
      <input type="hidden" name="order" value="'.$ordering[0].'-'.$ordering[1].'" />
      <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
      ';
    foreach( $filterFields AS $filterField => $filterConfig ){
      $reportdata["headertext"] .= '<tr>';
      $reportdata["headertext"] .= '<td width="20%" class="fieldlabel">'.$filterConfig['label'].'</td>';
      $reportdata["headertext"] .= '<td class="fieldarea">';
      switch( $filterConfig['type'] ){
        case 'text':
          $reportdata["headertext"] .= '<input name="filter['.$filterField.']" value="'.(isset($filter[$filterField]) ? $filter[$filterField] : $filterConfig['default']).'" '.$filterConfig['extra'].'/>';
          break;
        case 'select':
          $reportdata["headertext"] .= '<select name="filter['.$filterField.']" '.$filterConfig['extra'].'>';
          foreach( $filterConfig['options'] AS $k => $v )
            $reportdata["headertext"] .= '<option value="'. $k .'"'.((isset($filter[$filterField]) && ($k == $filter[$filterField])) || ((isset($filter[$filterField]) && ($k == $filterConfig['default']))) ? ' selected' : '').'>'. $v .'</option>';
          $reportdata["headertext"] .= '</select>';
          break;
      }
      $reportdata["headertext"] .= '</td>';
      $reportdata["headertext"] .= '</tr>';
    }
    $reportdata["headertext"] .= '
      </table>
      <p align="center">
        <input type="submit" value=" Filter " onclick="wbReportForm_filter(this);" />
        <input type="button" value=" Export " onclick="wbReportForm_export(this);" />
        <input type="button" value=" Print " onclick="wbReportForm_print(this);" />
      </p>
      </form>
      <div class="dataTable">
      ';
    $reportdata["footertext"] .= '
      </div>
      <script>
        function wbReportForm_filter(el){
          document.getElementById(\'wbReportForm\').action = \'reports.php\';
          document.getElementById(\'wbReportForm\').target = \'_self\';
          document.getElementById(\'wbReportForm\').print.value = \'\';
          document.getElementById(\'wbReportForm\').submit();
          return false;
        }
        function wbReportForm_export(el){
          document.getElementById(\'wbReportForm\').action = \'csvdownload.php\';
          document.getElementById(\'wbReportForm\').target = \'_blank\';
          document.getElementById(\'wbReportForm\').print.value = \'true\';
          document.getElementById(\'wbReportForm\').submit();
          return false;
        }
        function wbReportForm_print(el){
          document.getElementById(\'wbReportForm\').action = \'reports.php\';
          document.getElementById(\'wbReportForm\').target = \'_blank\';
          document.getElementById(\'wbReportForm\').print.value = \'true\';
          document.getElementById(\'wbReportForm\').submit();
          return false;
        }
        function wbReportForm_sort(el){
          document.getElementById(\'wbReportForm\').order.value = el.getAttribute(\'order\');
          document.getElementById(\'wbReportForm\').submit();
        }
      </script>
      <style>
        .dataTable table tr:nth-child(odd) td {
          background-color:#f6f6f6;
        }
        .dataTable table tr:hover td {
          background-color:#FFFFAA;
        }
        .dataTable table tr:first-child td {
          border-bottom:1px solid #999;
          background-color:#efefef;
          font-weight:bold;
        }
        .dataTable table tr:last-child td {
          border-top:3px double #999;
          background-color:#dfdfdf;
          font-weight:bold;
        }
        .dataTable table tr td {
          padding:4px;
        }
      </stlye>
      ';
  }

/************************************************************************************************************
 * Query
 ************************************************************************************************************/

  $dateMin   = date('Y-m-d',strtotime($filter['datemin']));
  $dateMax   = date('Y-m-d',strtotime($filter['datemax']));
  $packageid = intval($filter['packageid']);
  $result = mysql_query("
    SELECT `tblhosting`.*
      , CONCAT(`tblclients`.`firstname`, ' ', `tblclients`.`lastname`) AS 'fullname'
      , SUM(IF(`tblinvoices`.`status` = 'Paid', `tblinvoiceitems`.`amount`, 0)) AS `total_paid`
      , TIMESTAMPDIFF(MONTH, `tblhosting`.`regdate`, IF(`tblhosting`.`termination_date`, `tblhosting`.`termination_date`, CURDATE())) AS `total_months`
    FROM `tblhosting`
    LEFT JOIN `tblclients` ON `tblclients`.`id` = `tblhosting`.`userid`
    LEFT JOIN `tblinvoiceitems` ON `tblinvoiceitems`.`relid` = `tblhosting`.`id`
    LEFT JOIN `tblinvoices` ON `tblinvoices`.`id` = `tblinvoiceitems`.`invoiceid`
    WHERE `tblhosting`.`packageid` = ".$packageid."
      AND `tblhosting`.`regdate` >= '". mysql_real_escape_string($dateMin) ."'
      AND `tblhosting`.`regdate` <= '". mysql_real_escape_string($dateMax) ."'
    GROUP BY `tblhosting`.`id`
    ORDER BY ". mysql_real_escape_string($ordering[0]) .' '. mysql_real_escape_string($ordering[1])
    );
  $num_rows = mysql_num_rows($result); echo mysql_error();

/************************************************************************************************************
 * Data Rows
 ************************************************************************************************************/

  $rowCount = $grandTotal = $grandTotalPaid = $totalMonths = 0;
  $lineNumber = 1;
  while( $row = mysql_fetch_array($result) ) {
    $rowCount++;
    $startDate = $row['regdate'];
    $closeDate = $row['termination_date'] ?: date('Y-m-d');
    $reportLine = array(
      $lineNumber++,
      '<a target="_blank" href="clientssummary.php?userid='. $row['userid'] . '">'.$row['userid'] .'</a>',
      '<a target="_blank" href="clientssummary.php?userid='. $row['userid'] . '">'. $row['fullname'] .'</a>',
      '<a target="_blank" href="https://billing.holodyn.com/_WHMCS_Admin_/clientsservices.php?userid='. $row['userid'] . '&id='.$row['id'].'">'.$row['id'] .'</a>',
      fromMySQLDate($row['regdate']),
      fromMySQLDate($row['nextduedate']),
      fromMySQLDate($row['termination_date']),
      $row['domainstatus'],
      $wbDataMine->curFormat($row['firstpaymentamount']),
      $wbDataMine->curFormat($row['amount']),
      $row['billingcycle'],
      $row['total_months'],
      $wbDataMine->curFormat($row['total_paid']),
      );
    $grandTotal += $row['firstpaymentamount'];
    $totalMonths += $row['total_months'];
    $grandTotalPaid += $row['total_paid'];
    $reportdata["tablevalues"][] = preg_replace('/^\*+/','',$isPrintFormat ? $wbDataMine->stripTags($reportLine) : $reportLine);
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
      '',
      $wbDataMine->curFormat($grandTotal),
      '',
      '',
      $totalMonths,
      $wbDataMine->curFormat($grandTotalPaid)
      );
    $reportdata["tablevalues"][] = $reportLine;
    $reportLine = array(
      '',
      '',
      '',
      '',
      '',
      '',
      '',
      '',
      $wbDataMine->curFormat($grandTotal / $rowCount),
      '',
      '',
      round($totalMonths / $rowCount, 2),
      $wbDataMine->curFormat($grandTotalPaid / $rowCount)
      );
    $reportdata["tablevalues"][] = $reportLine;
  }
