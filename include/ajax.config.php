<?php
/*********************************************************************
    ajax.content.php

    AJAX interface for content fetching...allowed methods.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('!');
	    
class ConfigAjaxAPI extends AjaxController {

    //config info UI might need.
    function scp_ui() {
        global $thisstaff, $cfg;

        $config=array('ticket_lock_time'=>($cfg->getLockTime()*3600),
                      'max_attachments'=>$cfg->getMaxFileUploads(),
                      'max_file_size'=>$cfg->getMaxFileSize());

        return $this->json_encode($config);
    }
}
?>
