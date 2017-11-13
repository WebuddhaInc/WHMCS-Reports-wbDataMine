<?php

/*
  (c)2011 Webuddha, Holodyn.com
*/

if( !defined("WHMCS") ) die("This file cannot be accessed directly");

/************************************************************************************************************
 * Report Class
 ************************************************************************************************************/
  class wbDataMine {

    public $_printMode       = false;
    public $_currencySymbol  = '$';
    public $_filterData      = array();
    public $_filterFields    = array();
    public $_reportData      = array();

    function __construct(){
      $this->_printMode = (isset($_GET['print']) && $_GET['print']);
      $this->_currencySymbol = (isset($GLOBALS['CONFIG']) && isset($GLOBALS['CONFIG']['CurrencySymbol']) ? $GLOBALS['CONFIG']['CurrencySymbol'] : '$');
      if( isset($_REQUEST['filter']) & is_array($_REQUEST['filter']) )
        $this->_filterData = $_REQUEST['filter'];
    }

    public function &dbh(){
      return wbDatabase::getInstance();
    }

    public function setReportData( $reportData ){
      $this->_reportData = $reportData;
    }

    public function &getReportData(){
      return $this->_reportData;
    }

    public function setReportDataKey( $key, $value ){
      $this->_reportData[ $key ] = $value;
    }

    public function setFilterData( $filterData ){
      $this->_filterData = $filterData;
    }

    public function &getFilterData(){
      if( empty($this->_filterData) ){
        $this->_filterData = array();
        foreach( $this->_filterFields AS $filterField => $filterConfig )
          $this->_filterData[$filterField] = $filterConfig['default'];
      }
      return $this->_filterData;
    }

    public function setFilterDataKey( $key, $value ){
      $this->_filterData[ $key ] = $value;
    }

    public function &getFilterDataKey( $key, $default = null ){
      if( isset($this->_filterData[ $key ]) )
        return $this->_filterData[ $key ];
      return $default;
    }

    public function setFilterFields( $filterFields ){
      $this->_filterFields = $filterFields;
    }

    public function &getFilterFields(){
      return $this->_filterFields;
    }

    public function createTableHeadings($headerColumns, $ordering = null){
      $tableheadings = array();
      foreach($headerColumns AS $colKey => $colCfg) {
        if( $this->_printMode )
          $tableheadings[] = $colCfg['label'];
        else if( isset($colCfg['field']) && empty($colCfg['field']) )
          $tableheadings[] = $colCfg['label'];
        else
          $tableheadings[] = '<a href="javascript:void(0);" onclick="wbReportForm_sort(this);" order="'.($colCfg['field']?$colCfg['field']:$colKey).'-'.($ordering[1]=='asc'?'desc':'asc').'"'.(($colCfg['field']?$colCfg['field']:$colKey) == $ordering[0]?' class="active"':'').'>'.$colCfg['label'].'</a>';
      }
      return $tableheadings;
    }

    public function applyFilterForm( $filter = null ){

      $report = @$_REQUEST['report'];
      $filter = isset($filter) ? $filter : $this->getFilterData();
      $this->_reportData["headertext"] .= '
        <form method="get" action="reports.php" id="wbReportForm">
        <input type="hidden" name="order" value="'. @$_REQUEST['order'] .'" />
        <input type="hidden" name="print" value="" />
        <input type="hidden" name="report" value="'. $report .'" />
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        ';
      $filterCount = 0;
      foreach( $this->_filterFields AS $filterField => $filterConfig ){
        if (!($filterCount % 2))
          $this->_reportData["headertext"] .= '<tr>';
        $this->_reportData["headertext"] .= '<td width="20%" class="fieldlabel">'.$filterConfig['label'].'</td>';
        $this->_reportData["headertext"] .= '<td class="fieldarea">';
        $filterConfig['class'] = isset($filterConfig['class']) ? $filterConfig['class'] : '';
        switch( $filterConfig['type'] ){
          case 'text':
            $this->_reportData["headertext"] .= '<input name="filter['.$filterField.']" value="'.(isset($filter[$filterField]) ? $filter[$filterField] : $filterConfig['default']).'" '. (string)@$filterConfig['extra'] .'/>';
            break;
          case 'select':
            if (@$filterConfig['multiple'])
              $filterConfig['class'] .= ' chosen';
            $this->_reportData["headertext"] .= '<select name="filter['.$filterField.']'. (@$filterConfig['multiple']?'[]':'') .'" class="'. trim($filterConfig['class']) .'" style="min-width:240px;" '. (string)@$filterConfig['extra'] .' '. (@$filterConfig['multiple']?'multiple=true':'onchange="wbReportForm_filter(this);"') .'>';
            foreach( $filterConfig['options'] AS $k => $v )
              $this->_reportData["headertext"] .= '<option value="'. $k .'"'.((isset($filter[$filterField]) && in_array($k, (array)$filter[$filterField])) || ((empty($filter[$filterField]) && ($k == $filterConfig['default']))) ? ' selected' : '').'>'. $v .'</option>';
            $this->_reportData["headertext"] .= '</select>';
            break;
        }
        $this->_reportData["headertext"] .= '</td>';
        if (($filterCount % 2))
          $this->_reportData["headertext"] .= '</tr>';
        $filterCount++;
      }
      if (($filterCount % 2))
        $this->_reportData["headertext"] .= '</tr>';
      $this->_reportData["headertext"] .= '
        </table>
        <p align="center">
          <input type="submit" value=" Filter " onclick="wbReportForm_filter(this);" />
          <input type="button" value=" Export " onclick="wbReportForm_export(this);" />
          <input type="button" value=" Print " onclick="wbReportForm_print(this);" />
        </p>
        </form>
        <div class="dataTable">
        ';
      $this->_reportData["footertext"] .= '
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
            document.getElementById(\'wbReportForm\').print.value = \'1\';
            document.getElementById(\'wbReportForm\').submit();
            return false;
          }
          function wbReportForm_print(el){
            document.getElementById(\'wbReportForm\').action = \'reports.php\';
            document.getElementById(\'wbReportForm\').target = \'_blank\';
            document.getElementById(\'wbReportForm\').print.value = \'1\';
            document.getElementById(\'wbReportForm\').submit();
            return false;
          }
          function wbReportForm_sort(el){
            document.getElementById(\'wbReportForm\').order.value = el.getAttribute(\'order\');
            document.getElementById(\'wbReportForm\').submit();
          }
          jQuery(document).ready(function(){
            if (typeof jQuery.fn.chosen == \'function\') {
              jQuery(\'#wbReportForm .chosen\').chosen();
            }
          });
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
        </style>
        ';

    }

    public function stripTags($data){
      if( is_array($data) )
        foreach($data AS $k => $v)
          $data[ $k ] = strip_tags($v);
      else
        $data = strip_tags($data);
      return $data;
    }

    public function curFormat( $val ){
      $res = $this->_currencySymbol . number_format( $val , 2 );
      return $res;
    }

  }
