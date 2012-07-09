<?php
/*********************************************************************
    class.pdf.php

    Ticket PDF Export

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

define('THIS_DIR', str_replace('\\\\', '/', realpath(dirname(__FILE__))) . '/'); //Include path..
define('FPDF_DIR', THIS_DIR . 'fpdf/');
define('FPDF_FONTPATH', FPDF_DIR . 'font/'); //fonts directory.
require (FPDF_DIR . 'fpdf.php');

class Ticket2PDF extends FPDF
{
	
	var $includenotes = false;
	
	var $pageOffset = 0;
	
    var $ticket = null;

	function Ticket2PDF($ticket, $notes=false) {
        global $thisstaff;

        parent::FPDF('P', 'mm', $thisstaff->getDefaultPaperSize());

        $this->ticket = $ticket;

        $this->includenotes = $notes;
        $this->SetMargins(10,10,10);
		$this->AliasNbPages();
		$this->AddPage();
		$this->cMargin = 3;
        $this->_print();
	}

    function getTicket() {
        return $this->ticket;
    }
	
	//report header...most stuff are hard coded for now...
	function Header() {
        global $cfg;

		//Common header
        $this->Ln(2);
		$this->SetFont('Times', 'B', 16);
		$this->Image(FPDF_DIR . 'print-logo.png', null, 10, 0, 20);
		$this->SetX(200, 15);
		$this->Cell(0, 15, "Support Ticket System", 0, 1, 'R', 0);
		//$this->SetY(40);
        $this->SetXY(60, 25);
		$this->SetFont('Arial', 'B', 16);
		$this->Cell(0, 3, 'Ticket #'.$this->getTicket()->getExtId(), 0, 2, 'L');
        $this->SetX($this->lMargin);
        $this->Cell(0, 3, '', "B", 2, 'L');
        $this->SetFont('Arial', 'I',10);
        $this->Cell(0, 5, 'Generated on '.Format::date($cfg->getDateTimeFormat(), Misc::gmtime(), $_SESSION['TZ_OFFSET'], $_SESSION['TZ_DST']), 0, 0, 'L');
        $this->Cell(0, 5, 'Date & Time based on GMT '.$_SESSION['TZ_OFFSET'], 0, 1, 'R');
		$this->Ln(10);
	}
	
	//Page footer baby
	function Footer() {
        global $thisstaff;

		$this->SetY(-15);
        $this->Cell(0, 2, '', "T", 2, 'L');
		$this->SetFont('Arial', 'I', 9);
		$this->Cell(0, 7, 'Ticket printed by '.$thisstaff->getUserName().' on '.date('r'), 0, 0, 'L');
		//$this->Cell(0,10,'Page '.($this->PageNo()-$this->pageOffset).' of {nb} '.$this->pageOffset.' '.$this->PageNo(),0,0,'R');
		$this->Cell(0, 7, 'Page ' . ($this->PageNo() - $this->pageOffset), 0, 0, 'R');
	}

    function WriteText($w, $text, $border) {

        $this->SetFont('Times','',11);
        $this->MultiCell($w, 5, $text, $border, 'L');

    }
    
    function _print() {

        if(!($ticket=$this->getTicket()))
            return;

        $w =(($this->w/2)-$this->lMargin);
        $l = 40;
        $c = $w-$l;
        $this->SetDrawColor(220, 220, 220);
        $this->SetFillColor(244, 250, 255);
        $this->SetX($this->lMargin);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Status', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getStatus(), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Name', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getName(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Priority', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getPriority(), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Email', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getEmail(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Department', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getDeptName(), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Phone', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getPhoneNumber(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Create Date', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, Format::db_datetime($ticket->getCreateDate()), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Source', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, ucfirst($ticket->getSource()), 1, 0, 'L', true);
        $this->Ln(15);

        $this->SetFont('Arial', 'B', 11);
        if($ticket->isOpen()) {
            $this->Cell($l, 7, 'Assigned To', 1, 0, 'L', true);
            $this->SetFont('');
            $this->Cell($c, 7, $ticket->isAssigned()?implode('/', $ticket->getAssignees()):' -- ', 1, 0, 'L', true);
        } else {

            $closedby = 'unknown';
            if(($staff = $ticket->getStaff()))
                $closedby = $staff->getName();

            $this->Cell($l, 7, 'Closed By', 1, 0, 'L', true);
            $this->SetFont('');
            $this->Cell($c, 7, $closedby, 1, 0, 'L', true);
        }

        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Subject', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getSubject(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Last Response', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, Format::db_datetime($ticket->getLastRespDate()), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Help Topic', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, $ticket->getHelpTopic(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        if($ticket->isOpen()) {
            $this->Cell($l, 7, 'Due Date', 1, 0, 'L', true);
            $this->SetFont('');
            $this->Cell($c, 7, Format::db_datetime($ticket->getDueDate()), 1, 0, 'L', true);
        } else {
            $this->Cell($l, 7, 'Close Date', 1, 0, 'L', true);
            $this->SetFont('');
            $this->Cell($c, 7, Format::db_datetime($ticket->getCloseDate()), 1, 0, 'L', true);
        }

        $this->SetFont('Arial', 'B', 11);
        $this->Cell($l, 7, 'Last Message', 1, 0, 'L', true);
        $this->SetFont('');
        $this->Cell($c, 7, Format::db_datetime($ticket->getLastMsgDate()), 1, 1, 'L', true);
        $this->Ln(10);

        //Table header colors (RGB)
        $colors = array('M'=>array(195, 217, 255),
                        'R'=>array(255, 224, 179),
                        'N'=>array(250, 250, 210));
        //Get ticket thread
        if(($entries = $ticket->getThreadWithNotes())) { 
            foreach($entries as $entry) {

                $color = $colors[$entry['thread_type']];

                $this->SetFillColor($color[0], $color[1], $color[2]);
                $this->SetFont('Arial', 'B', 11);
                $this->Cell($w/2, 7, Format::db_datetime($entry['created']), 'LTB', 0, 'L', true);
                $this->SetFont('Arial', '', 10);
                $this->Cell($w, 7, $entry['title'], 'TB', 0, 'L', true);
                $this->Cell($w/2, 7, $entry['poster'], 'TBR', 1, 'L', true);
                $this->SetFont('');
                $text= $entry['body'];
                if($entry['attachments'] 
                        && ($attachments = $ticket->getAttachments($entry['id'], $entry['thread_type']))) {
                    foreach($attachments as $attachment)
                        $files[]= $attachment['name'];
                    
                    $text.="\nFiles Attached: [".implode(', ',$files)."]\n";
                }
                $this->WriteText($w*2, $text, 1);
                $this->Ln(5);
            }
        }

    }	
}
?>
