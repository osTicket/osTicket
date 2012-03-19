<?php
/*********************************************************************
    attachment.php

    Attachments interface for clients.
    Clients should never see the dir paths.
    
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('secure.inc.php');
//TODO: alert admin on any error on this file.
if(!$thisclient || !$thisclient->isClient() || !$_GET['id'] || !$_GET['ref']) die('Access Denied');

$sql='SELECT attach_id,ref_id,ticket.ticket_id,ticketID,ticket.created,dept_id,file_name,file_key,email FROM '.TICKET_ATTACHMENT_TABLE.
    ' LEFT JOIN '.TICKET_TABLE.' ticket USING(ticket_id) '.
    ' WHERE attach_id='.db_input($_GET['id']);
//valid ID??
if(!($res=db_query($sql)) || !db_num_rows($res)) die('Invalid/unknown file');
list($id,$refid,$tid,$extid,$date,$deptID,$filename,$key,$email)=db_fetch_row($res);

//Still paranoid...:)...check the secret session based hash and email
$hash=MD5($tid*$refid.session_id());
if(!$_GET['ref'] || strcmp($hash,$_GET['ref']) || strcasecmp($thisclient->getEmail(),$email)) die('Access denied: Kwaheri');


//see if the file actually exits.
$month=date('my',strtotime("$date"));
$file=rtrim($cfg->getUploadDir(),'/')."/$month/$key".'_'.$filename;
if(!file_exists($file))
    $file=rtrim($cfg->getUploadDir(),'/')."/$key".'_'.$filename;
    
if(!file_exists($file)) die('Invalid Attachment');

$extension =substr($filename,-3);
switch(strtolower($extension))
{
  case "pdf": $ctype="application/pdf"; break;
  case "exe": $ctype="application/octet-stream"; break;
  case "zip": $ctype="application/zip"; break;
  case "doc": $ctype="application/msword"; break;
  case "xls": $ctype="application/vnd.ms-excel"; break;
  case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
  case "gif": $ctype="image/gif"; break;
  case "png": $ctype="image/png"; break;
  case "jpg": $ctype="image/jpg"; break;
  default: $ctype="application/force-download";
}
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public"); 
header("Content-Type: $ctype");
$user_agent = strtolower ($_SERVER["HTTP_USER_AGENT"]);
if ((is_integer(strpos($user_agent,"msie"))) && (is_integer(strpos($user_agent,"win")))) 
{
  header( "Content-Disposition: filename=".basename($filename).";" );
} else {
  header( "Content-Disposition: attachment; filename=".basename($filename).";" );
}
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($file));
readfile($file);
exit();
?>
