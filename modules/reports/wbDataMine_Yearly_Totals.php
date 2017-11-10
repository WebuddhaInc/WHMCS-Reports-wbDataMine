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

  class wbDataMine_Yearly_Totals extends wbDataMine {

    public $_payMethods  = array();
    public $_monthlyNet  = array();
    public $_paymentNet  = array();

    function __construct(){

      /************************************************************************************************************
       * Parent Init
       ************************************************************************************************************/

        parent::__construct();

      /************************************************************************************************************
       * Report Setup
       ************************************************************************************************************/

        $this->setReportData(array(
          'title'         => 'wbDataMine: Yearly Totals',
          'description'   => 'This report will generate a showing total invoice activity over a number of years.',
          'headertext'    => '',
          'footertext'    => '',
          'tableheadings' => array(
            'Year',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec',
            'Total',
            'Mo.Avg'
            ),
          'tablevalues'   => array()
          ));

      /************************************************************************************************************
       * Report Filters
       ************************************************************************************************************/

        $this->setFilterFields(array(
          'yearstart'  => array(
            'type'      => 'text',
            'label'     => 'Start Year',
            'default'   => date('Y'),
            'extra'     => 'size="4"'
          ),
          'numyears'  => array(
            'type'      => 'text',
            'label'     => 'Years to Show',
            'default'   => '10',
            'extra'     => 'size="4"'
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
        $filterData['yearstart'] = (int)$filterData['yearstart'] <= date('Y') ? (int)$filterData['yearstart'] : date('Y');
        if( (int)$filterData['numyears'] > 25 ) $filterData['numyears'] = 25;
        if( (int)$filterData['numyears'] < 2 ) $filterData['numyears'] = 2;

      /************************************************************************************************************
       * Payment Methods
       ************************************************************************************************************/

        $res0 = mysql_query("
          SELECT `inv`.`paymentmethod`
          FROM `tblinvoices` AS `inv`
          GROUP BY `inv`.`paymentmethod`
          ");
        while($data = mysql_fetch_array($res0)) {
          $this->_payMethods[] = $data['paymentmethod'];
          $this->_reportData["tableheadings"][] = ucwords($data['paymentmethod']);
        }

      /************************************************************************************************************
       * Header & Footer Text
       ************************************************************************************************************/

        if( !$isPrintFormat )
          wbDataMine_Yearly_Totals::applyFilterForm();

      /************************************************************************************************************
       * Data Rows
       ************************************************************************************************************/

        $year = $filterData['yearstart']; $runaway = 0;
        do {
          $reportLine = array(
            $year,
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-01-01&filter[datemax]='.$year.'-01-'.cal_days_in_month(CAL_GREGORIAN,1,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 1) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-02-01&filter[datemax]='.$year.'-02-'.cal_days_in_month(CAL_GREGORIAN,2,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 2) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-03-01&filter[datemax]='.$year.'-03-'.cal_days_in_month(CAL_GREGORIAN,3,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 3) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-04-01&filter[datemax]='.$year.'-04-'.cal_days_in_month(CAL_GREGORIAN,4,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 4) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-05-01&filter[datemax]='.$year.'-05-'.cal_days_in_month(CAL_GREGORIAN,5,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 5) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-06-01&filter[datemax]='.$year.'-06-'.cal_days_in_month(CAL_GREGORIAN,6,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 6) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-07-01&filter[datemax]='.$year.'-07-'.cal_days_in_month(CAL_GREGORIAN,7,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 7) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-08-01&filter[datemax]='.$year.'-08-'.cal_days_in_month(CAL_GREGORIAN,8,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 8) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-09-01&filter[datemax]='.$year.'-09-'.cal_days_in_month(CAL_GREGORIAN,9,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 9) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-10-01&filter[datemax]='.$year.'-10-'.cal_days_in_month(CAL_GREGORIAN,10,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 10) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-11-01&filter[datemax]='.$year.'-11-'.cal_days_in_month(CAL_GREGORIAN,11,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 11) ).'</a>',
            '<a href="reports.php?report=wbDataMine_Invoices&filter[datemin]='.$year.'-12-01&filter[datemax]='.$year.'-12-'.cal_days_in_month(CAL_GREGORIAN,12,$year).'&filter[datefield]='.$filterData['datefield'].'&filter[invstatus]='.$filterData['invstatus'].'">'.$this->curFormat( $this->monthly_net($year, 12) ).'</a>',
            $this->curFormat( $this->yearly_net($year) ),
            $this->curFormat( $this->monthly_avg($year) )
            );
          $res = $this->payment_methods($year);
          foreach($res AS $v)
            $reportLine[] = $this->_currencySymbol . $v;
          $this->_reportData["tablevalues"][] = preg_replace('/^\*+/','',$isPrintFormat ? $this->stripTags($reportLine) : $reportLine);
          $year--;
          $runaway++;
        } while( $runaway < 25 && $runaway < $filterData['numyears'] );

      /************************************************************************************************************
       * Total Row
       ************************************************************************************************************/

        $reportLine = array(
          'Avrg',
          $this->curFormat( $this->yearly_avg(1) ),
          $this->curFormat( $this->yearly_avg(2) ),
          $this->curFormat( $this->yearly_avg(3) ),
          $this->curFormat( $this->yearly_avg(4) ),
          $this->curFormat( $this->yearly_avg(5) ),
          $this->curFormat( $this->yearly_avg(6) ),
          $this->curFormat( $this->yearly_avg(7) ),
          $this->curFormat( $this->yearly_avg(8) ),
          $this->curFormat( $this->yearly_avg(9) ),
          $this->curFormat( $this->yearly_avg(10) ),
          $this->curFormat( $this->yearly_avg(11) ),
          $this->curFormat( $this->yearly_avg(12) ),
          $this->curFormat( $this->yearly_avg() ),
          $this->curFormat( $this->monthly_avg() )
          );
        $res = $this->payment_methods_avg();
        foreach($res AS $v) $reportLine[] = $this->curFormat($v);
        $this->_reportData["tablevalues"][] = $reportLine;

    }

    function monthly_net($year, $month){
      if( isset($this->_monthlyNet[$year]) && isset($this->_monthlyNet[$year][$month]) )
        return $this->_monthlyNet[$year][$month];
      if( !isset($this->_monthlyNet[$year]) )
        $this->_monthlyNet[$year] = array();
      if( $this->_filterData['invstatus'] == 'active' ){
        $res0 = mysql_query("
          SELECT SUM(`inv`.`total`) AS `total`
          FROM `tblinvoices` AS `inv`
          WHERE `inv`.`".$this->_filterData['datefield']."` >= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-".str_pad((int)$month,2,'0',STR_PAD_LEFT)."-01 00:00:00'
            AND `inv`.`".$this->_filterData['datefield']."` <= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-".str_pad((int)$month,2,'0',STR_PAD_LEFT)."-31 23:59:59'
            AND `inv`.`status` IN ('Paid','Unpaid')
          ");
        $oth = mysql_fetch_array($res0);
        $this->_monthlyNet[$year][$month] = ($oth['total'] ? $oth['total'] : '0');
      } else {
        $res0 = mysql_query("
          SELECT SUM(`inv`.`total`) AS `total`
          FROM `tblinvoices` AS `inv`
          WHERE `inv`.`".$this->_filterData['datefield']."` >= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-".str_pad((int)$month,2,'0',STR_PAD_LEFT)."-01 00:00:00'
            AND `inv`.`".$this->_filterData['datefield']."` <= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-".str_pad((int)$month,2,'0',STR_PAD_LEFT)."-31 23:59:59'
            AND `inv`.`status` = '". mysql_real_escape_string($this->_filterData['invstatus']) ."'
          ");
        $oth = mysql_fetch_array($res0);
        $this->_monthlyNet[$year][$month] = ($oth['total'] ? $oth['total'] : '0');
      }
      return $this->_monthlyNet[$year][$month];
    }

    function monthly_avg($year=0){
      $years = array_keys($this->_monthlyNet);
      if( $year ){
        for($i=1;$i<=12;$i++)
          $total += self::monthly_net($year, $i);
        $res = round($total / 12,2);
      }
      else {
        foreach( $years AS $year )
          for($i=1;$i<=12;$i++)
            $total += self::monthly_net($year, $i);
        $res = round($total / (count($years)*12),2);
      }
      return $res;
    }

    function yearly_net($year){
      for($i=1;$i<=12;$i++)
        $total += self::monthly_net($year, $i);
      return $total;
    }

    function yearly_avg($month=0){
      $years = array_keys($this->_monthlyNet);
      foreach( $years AS $year ){
        if( $month )
          $total += self::monthly_net($year, $month);
        else
          for($i=1;$i<=12;$i++)
            $total += self::monthly_net($year, $i);
      }
      return round($total / count($years),2);
    }

    function payment_methods($year){
      if( isset($this->_paymentNet[$year]) )
        return $this->_paymentNet[$year];
      $reportLineCols = array();
      foreach( $this->_payMethods AS $method ){
        if( $this->_filterData['invstatus'] == 'active' ){
          $res0 = mysql_query("
            SELECT SUM(`inv`.`total`) AS `total`
            FROM `tblinvoices` AS `inv`
            WHERE `inv`.`".$this->_filterData['datefield']."` >= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-01-01 00:00:00'
              AND `inv`.`".$this->_filterData['datefield']."` <= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-12-31 23:59:59'
              AND `inv`.`paymentmethod` = '". $method ."'
            ");
        } else {
          $res0 = mysql_query("
            SELECT SUM(`inv`.`total`) AS `total`
            FROM `tblinvoices` AS `inv`
            WHERE `inv`.`".$this->_filterData['datefield']."` >= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-01-01 00:00:00'
              AND `inv`.`".$this->_filterData['datefield']."` <= '".str_pad((int)$year,4,'0',STR_PAD_RIGHT)."-12-31 23:59:59'
              AND `inv`.`paymentmethod` = '". $method ."'
              AND `inv`.`status` = '". $this->_filterData['invstatus'] ."'
            ");
        }
        $num_rows = mysql_num_rows($res0);
        $data = mysql_fetch_array($res0);
        $reportLineCols[] = ($data['total'] ? $data['total'] : '0');
      }
      $this->_paymentNet[$year] = $reportLineCols;
      return $this->_paymentNet[$year];
    }

    function payment_methods_avg(){
      $rows = array();
      $total = array();
      $years = array_keys($this->_paymentNet);
      foreach( $years AS $year ){
        $rows[$year] = self::payment_methods($year);
        for($i=0;$i<count($rows[$year]);$i++)
          $total[$i] += $rows[$year][$i];
      }
      for($i=0;$i<count($total);$i++)
        $total[$i] = round($total[$i] / count($years),2);
      return $total;
    }

  }

/************************************************************************************************************
 * Header & Footer Text
 ************************************************************************************************************/

  $report = new wbDataMine_Yearly_Totals();
  $reportdata = $report->getReportData();
