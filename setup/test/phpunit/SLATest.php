<?php
use PHPUnit\Framework\TestCase;
//include_once('main.inc.php'); too many errors

define ('INCLUDE_DIR', 'include/');
define ('SLA_TABLE', 'ost_sla');
include_once(INCLUDE_DIR.'class.orm.php');
include_once(INCLUDE_DIR.'class.variable.php');
include_once(INCLUDE_DIR.'class.signal.php');
include_once(INCLUDE_DIR.'class.sla.php');

class SLATest extends TestCase
{
  
    public function testCalcSLAWithBusinessHours()
    {
        $mysla = new SLA();
        $mydt = new DateTime('2016-02-14 09:00:00');
        $duedt = $mysla->calcSLAWithBusinessHours($mydt)->format('Y-m-d H:i:s');
        $this->assertEquals('2016-02-17 09:30:00',$duedt);
    }

}
