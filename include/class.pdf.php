<?php
/*********************************************************************
    class.pdf.php

    Ticket PDF Export

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

define('THIS_DIR', str_replace('\\', '/', Misc::realpath(dirname(__FILE__))) . '/'); //Include path..

require_once(INCLUDE_DIR.'mpdf/mpdf.php');

class Ticket2PDF extends mPDF
{

	var $includenotes = false;

	var $pageOffset = 0;

    var $ticket = null;

	function Ticket2PDF($ticket, $psize='Letter', $notes=false) {
        global $thisstaff;


        $this->ticket = $ticket;
        $this->includenotes = $notes;

        parent::__construct('', $psize);

        $this->SetMargins(10,10,10);
		$this->AliasNbPages();
		$this->AddPage();
		$this->cMargin = 3;

        $this->_print();
	}

    function getTicket() {
        return $this->ticket;
    }

    function getLogoFile() {
        global $ost;

        if (!function_exists('imagecreatefromstring')
                || (!($logo = $ost->getConfig()->getClientLogo()))) {
            return INCLUDE_DIR.'fpdf/print-logo.png';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pdf') . '.jpg';
        $img = imagecreatefromstring($logo->getData());
        // Handle transparent images with white background
        $img2 = imagecreatetruecolor(imagesx($img), imagesy($img));
        $white = imagecolorallocate($img2, 255, 255, 255);
        imagefill($img2, 0, 0, $white);
        imagecopy($img2, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imagejpeg($img2, $tmp);
        return $tmp;
    }

	//report header...most stuff are hard coded for now...
	function Header() {
        global $cfg;

		//Common header
        $logo = $this->getLogoFile();
		$this->Image($logo, $this->lMargin, $this->tMargin, 0, 20);
        if (strpos($logo, INCLUDE_DIR) === false)
            unlink($logo);
		$this->SetFont('Arial', 'B', 16);
		$this->SetY($this->tMargin + 20);
        $this->SetX($this->lMargin);
        $this->WriteCell(0, 0, '', "B", 2, 'L');
		$this->Ln(1);
        $this->SetFont('Arial', 'B',10);
        $this->WriteCell(0, 5, $cfg->getTitle(), 0, 0, 'L');
        $this->SetFont('Arial', 'I',10);
        $this->WriteCell(0, 5, Format::date($cfg->getDateTimeFormat(), Misc::gmtime(),
            $_SESSION['TZ_OFFSET'], $_SESSION['TZ_DST'])
            .' GMT '.$_SESSION['TZ_OFFSET'], 0, 1, 'R');
		$this->Ln(5);
	}

	//Page footer baby
	function Footer() {
        global $thisstaff;

		$this->SetY(-15);
        $this->WriteCell(0, 2, '', "T", 2, 'L');
		$this->SetFont('Arial', 'I', 9);
		$this->WriteCell(0, 7, 'Ticket #'.$this->getTicket()->getNumber().' printed by '.$thisstaff->getUserName().' on '.date('r'), 0, 0, 'L');
		//$this->WriteCell(0,10,'Page '.($this->PageNo()-$this->pageOffset).' of {nb} '.$this->pageOffset.' '.$this->PageNo(),0,0,'R');
		$this->WriteCell(0, 7, 'Page ' . ($this->PageNo() - $this->pageOffset), 0, 0, 'R');
	}

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    function WriteText($w, $text, $border) {

        $this->SetFont('Arial','',11);
        $this->MultiCell($w, 7, $text, $border, 'L');

    }

    function WriteHtml() {
        static $filenumber = 1;
        $args = func_get_args();
        $text = &$args[0];
        $self = $this;
        $text = preg_replace_callback('/cid:([\w.-]{32})/',
            function($match) use ($self, &$filenumber) {
                if (!($file = AttachmentFile::lookup($match[1])))
                    return $match[0];
                $key = "__attached_file_".$filenumber++;
                $self->{$key} = $file->getData();
                return 'var:'.$key;
            },
            $text
        );
        call_user_func_array(array('parent', 'WriteHtml'), $args);
    }

    function _print() {

        if(!($ticket=$this->getTicket()))
            return;

        $w =(($this->w/2)-$this->lMargin);
        $l = 35;
        $c = $w-$l;

        // Setup HTML writing and load default thread stylesheet
        $this->WriteHtml(
            '<style>'.file_get_contents(ROOT_DIR.'css/thread.css')
            .'</style>', 1, true, false);

        $this->SetFont('Arial', 'B', 11);
        $this->cMargin = 0;
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(10, 86, 142);
        $this->WriteCell($w, 7,'Ticket #'.$ticket->getNumber(), 0, 0, 'L');
        $this->Ln(7);
        $this->cMargin = 3;
        $this->SetTextColor(0);
        $this->SetDrawColor(220, 220, 220);
        $this->SetFillColor(244, 250, 255);
        $this->SetX($this->lMargin);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Status', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, $ticket->getStatus(), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Name', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, (string)$ticket->getName(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Priority', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, $ticket->getPriority(), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Email', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, $ticket->getEmail(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Department', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, $ticket->getDeptName(), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Phone', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, $ticket->getPhoneNumber(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Create Date', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, Format::db_datetime($ticket->getCreateDate()), 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Source', 1, 0, 'L', true);
        $this->SetFont('');
        $source = ucfirst($ticket->getSource());
        if($ticket->getIP())
            $source.='  ('.$ticket->getIP().')';
        $this->WriteCell($c, 7, $source, 1, 0, 'L', true);
        $this->Ln(12);

        $this->SetFont('Arial', 'B', 11);
        if($ticket->isOpen()) {
            $this->WriteCell($l, 7, 'Assigned To', 1, 0, 'L', true);
            $this->SetFont('');
            $this->WriteCell($c, 7, $ticket->isAssigned()?$ticket->getAssigned():' -- ', 1, 0, 'L', true);
        } else {

            $closedby = 'unknown';
            if(($staff = $ticket->getStaff()))
                $closedby = (string) $staff->getName();

            $this->WriteCell($l, 7, 'Closed By', 1, 0, 'L', true);
            $this->SetFont('');
            $this->WriteCell($c, 7, $closedby, 1, 0, 'L', true);
        }

        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Help Topic', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, $ticket->getHelpTopic(), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'SLA Plan', 1, 0, 'L', true);
        $this->SetFont('');
        $sla = $ticket->getSLA();
        $this->WriteCell($c, 7, $sla?$sla->getName():' -- ', 1, 0, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Last Response', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, Format::db_datetime($ticket->getLastRespDate()), 1, 1, 'L', true);
        $this->SetFont('Arial', 'B', 11);
        if($ticket->isOpen()) {
            $this->WriteCell($l, 7, 'Due Date', 1, 0, 'L', true);
            $this->SetFont('');
            $this->WriteCell($c, 7, Format::db_datetime($ticket->getEstDueDate()), 1, 0, 'L', true);
        } else {
            $this->WriteCell($l, 7, 'Close Date', 1, 0, 'L', true);
            $this->SetFont('');
            $this->WriteCell($c, 7, Format::db_datetime($ticket->getCloseDate()), 1, 0, 'L', true);
        }

        $this->SetFont('Arial', 'B', 11);
        $this->WriteCell($l, 7, 'Last Message', 1, 0, 'L', true);
        $this->SetFont('');
        $this->WriteCell($c, 7, Format::db_datetime($ticket->getLastMsgDate()), 1, 1, 'L', true);

        $this->SetFillColor(255, 255, 255);
        foreach (DynamicFormEntry::forTicket($ticket->getId()) as $form) {
            $idx = 0;
            foreach ($form->getAnswers() as $a) {
                if (in_array($a->getField()->get('name'),
                            array('email','name','subject','phone','priority')))
                    continue;
                $this->SetFont('Arial', 'B', 11);
                if ($idx++ === 0) {
                    $this->Ln(5);
                    $this->SetFillColor(244, 250, 255);
                    $this->WriteCell(($l+$c)*2, 7, $a->getForm()->get('title'),
                        1, 0, 'L', true);
                    $this->SetFillColor(255, 255, 255);
                }
                if ($val = $a->toString()) {
                    $this->Ln(7);
                    $this->WriteCell($l*2, 7, $a->getField()->get('label'), 1, 0, 'L', true);
                    $this->SetFont('');
                    $this->WriteCell($c*2, 7, $val, 1, 0, 'L', true);
                }
            }
        }
        $this->SetFillColor(244, 250, 255);
        $this->Ln(10);

        $this->SetFont('Arial', 'B', 11);
        $this->cMargin = 0;
        $this->SetTextColor(10, 86, 142);
        $this->WriteCell($w, 7,trim($ticket->getSubject()), 0, 0, 'L');
        $this->Ln(7);
        $this->SetTextColor(0);
        $this->cMargin = 3;

        //Table header colors (RGB)
        $colors = array('M'=>array(195, 217, 255),
                        'R'=>array(255, 224, 179),
                        'N'=>array(250, 250, 210));
        //Get ticket thread
        $types = array('M', 'R');
        if($this->includenotes)
            $types[] = 'N';

        if(($entries = $ticket->getThreadEntries($types))) {
            foreach($entries as $entry) {

                $color = $colors[$entry['thread_type']];

                $this->SetFillColor($color[0], $color[1], $color[2]);
                $this->SetFont('Arial', 'B', 11);
                $this->WriteCell($w/2, 7, Format::db_datetime($entry['created']), 'LTB', 0, 'L', true);
                $this->SetFont('Arial', '', 10);
                $this->WriteCell($w, 7, Format::truncate($entry['title'], 50), 'TB', 0, 'L', true);
                $this->WriteCell($w/2, 7, $entry['name'] ?: $entry['poster'], 'TBR', 1, 'L', true);
                $this->SetFont('');
                $text= $entry['body'];
                if($entry['attachments']
                        && ($tentry=$ticket->getThreadEntry($entry['id']))
                        && ($attachments = $tentry->getAttachments())) {
                    $files = array();
                    foreach($attachments as $attachment)
                        $files[]= $attachment['name'];

                    $text.="<div>Files Attached: [".implode(', ',$files)."]</div>";
                }
                $this->WriteHtml('<div class="thread-body">'.$text.'</div>', 2, false, false);
                $this->Ln(5);
            }
        }

        $this->WriteHtml('', 2, false, true);

    }
}
?>
