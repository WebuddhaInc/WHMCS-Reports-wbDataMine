<?php

/*
  (c)2011 Webuddha, Holodyn.com
*/

if( !defined("WHMCS") ) die("This file cannot be accessed directly");

/************************************************************************************************************
 * Include wbDataMine Class
 ************************************************************************************************************/

  require 'wbDataMine.class.php';
  $report = new wbDataMine();

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
  if( count($ordering) != 2 ) $ordering = array('tblinvoices.id','asc');
  $headerColumns = array(
    'line_number' => array('label'=>'#', 'field'=>''),
    'tblinvoices.id' => array('label'=>'ID'),
    'tblinvoices.date' => array('label'=>'Date'),
    'tblinvoices.duedate' => array('label'=>'Due Date'),
    'tblclients.lastname' => array('label'=>'Client'),
    'tblinvoices.status' => array('label'=>'Status'),
    'tblinvoices.datepaid' => array('label'=>'Date Paid'),
    'tblinvoices.total' => array('label'=>'Amount'),
    'total_paid' => array('label'=>'Paid')
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
  );
  if( !is_array($filter) ){
    $filter = array();
    foreach( $filterFields AS $filterField => $filterConfig )
      $filter[$filterField] = $filterConfig['default'];
  }
  if( !in_array($filter['datefield'], array('date','duedate','datepaid')) ) $filter['datefield'] = 'date';

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

  $dateMin = date('Y-m-d',strtotime($filter['datemin']));
  $dateMax = date('Y-m-d',strtotime($filter['datemax']));
  $result = mysql_query("
    SELECT `tblinvoices`.*
      , CONCAT(`tblclients`.`firstname`, ' ', `tblclients`.`lastname`) AS 'fullname'
      , (
        SELECT SUM((`tblaccounts`.`amountin` - `tblaccounts`.`amountout`))
        FROM `tblaccounts`
        WHERE `tblaccounts`.`invoiceid` = `tblinvoices`.`id`
        ) AS `total_paid`
    FROM `tblinvoices`, `tblclients`
    WHERE `tblclients`.`id` = `tblinvoices`.`userid`
      AND `tblinvoices`.`".$filter['datefield']."` >= '". mysql_real_escape_string($dateMin) ."'
      AND `tblinvoices`.`".$filter['datefield']."` <= '". mysql_real_escape_string($dateMax) ."'
      ". (strlen($filter['totalmin']) ? "AND `tblinvoices`.`total` >= '". mysql_real_escape_string($filter['totalmin']) ."'" : "") ."
      ". (strlen($filter['totalmax']) ? "AND `tblinvoices`.`total` <= '". mysql_real_escape_string($filter['totalmax']) ."'" : "") ."
      ". ($filter['invstatus'] == '' ? "" : ($filter['invstatus'] == 'active' ? "AND `tblinvoices`.`status` IN ('paid','unpaid')" : "AND `tblinvoices`.`status` = '". mysql_real_escape_string($filter['invstatus']) ."'")) ."
    ORDER BY ". mysql_real_escape_string($ordering[0]) .' '. mysql_real_escape_string($ordering[1])
    );
  $num_rows = mysql_num_rows($result); echo mysql_error();

/************************************************************************************************************
 * Data Rows
 ************************************************************************************************************/

  $grandTotal = $grandTotalPaid = 0;
  $lineNumber = 1;
  while( $row = mysql_fetch_array($result) ) {
    $reportLine = array(
      $lineNumber++,
      '<a target="_blank" href="invoices.php?action=edit&id='.$row['id'].'">'.$row['id'] .'</a>',
      fromMySQLDate($row['date']),
      fromMySQLDate($row['duedate']),
      '<a target="_blank" href="clientssummary.php?userid='. $row['userid'] . '">'. $row['fullname'] .'</a>',
      '<span style="color:'. ($row['status'] == 'Paid'?'green':($row['status'] == 'Unpaid'?'darkred':'lightgrey')) .'">'. $row['status'] .'<span>',
      (strpos($row['datepaid'],'0000-00-00')===0 ? '-' : fromMySQLDate($row['datepaid'])),
      $report->curFormat($row['total']),
      $report->curFormat($row['total_paid'])
      );
    $grandTotal += (in_array($row['status'],array('Paid','Unpaid'))?$row['total']:0);
    $grandTotalPaid += $row['total_paid'];
    $reportdata["tablevalues"][] = preg_replace('/^\*+/','',$isPrintFormat ? $report->stripTags($reportLine) : $reportLine);
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
      $report->curFormat($grandTotal),
      $report->curFormat($grandTotalPaid)
      );
    $reportdata["tablevalues"][] = $reportLine;
  }
