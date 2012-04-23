<?php
if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkClientAccess($thisclient)) die('Access Denied!');

$info=($_POST && $errors)?Format::htmlchars($_POST):array();

$dept = $ticket->getDept();
//Making sure we don't leak out internal dept names
if(!$dept || !$dept->isPublic())
    $dept = $cfg->getDefaultDept();

?>
<table width="800" cellpadding="1" cellspacing="0" border="0" id="ticketInfo">
    <tr>
        <td colspan="2" width="100%">
            <h1>
                Ticket #<?php echo $ticket->getExtId(); ?> &nbsp;
                <a href="view.php?id=<?php echo $ticket->getExtId(); ?>" title="Reload"><span class="Icon refresh">&nbsp;</span></a>
            </h1>
        </td>
    </tr> 
    <tr>
        <td width="50%">   
            <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
                <tr>
                    <th width="100">Ticket Status:</th>
                    <td><?php echo ucfirst($ticket->getStatus()); ?></td>
                </tr>
                <tr>
                    <th>Department:</th>
                    <td><?php echo Format::htmlchars($dept->getName()); ?></td>
                </tr>
                <tr>
                    <th>Create Date:</th>
                    <td><?php echo Format::db_datetime($ticket->getCreateDate()); ?></td>
                </tr>
           </table>
       </td>
       <td width="50%">
           <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
               <tr>
                   <th width="100">Name:</th>
                   <td><?php echo ucfirst($ticket->getName()); ?></td>
               </tr>
               <tr>
                   <th width="100">Email:</th>
                   <td><?php echo Format::htmlchars($ticket->getEmail()); ?></td>
               </tr>
               <tr>
                   <th>Phone:</th>
                   <td><?php echo $ticket->getPhoneNumber(); ?></td>
               </tr>
            </table>
       </td>
    </tr>
</table>
<br>
<h2>Subject:<?php echo Format::htmlchars($ticket->getSubject()); ?></h2>
<br>
<span class="Icon thread">Ticket Thread</span>
<div id="ticketThread">
<?php    
if($ticket->getThreadCount() && ($messages = $ticket->getMessages())) {
     
    foreach($messages as $message) {?>
    
        <table class="message" cellspacing="0" cellpadding="1" width="800" border="0">
        
            <tr><th><?php echo Format::db_datetime($message['created']); ?></th></tr>
            
            <tr><td><?php echo Format::display($message['body']); ?></td></tr>
            
            <?php
            
            if($message['attachments'] && ($links=$ticket->getAttachmentsLinks($message['id'],'M'))) { ?>
            
                <tr><td class="info"><?php echo $links; ?></td></tr>
                
            <?php
            
            } ?>
            
        </table>
        <?php
        if($message['responses'] && ($responses=$ticket->getResponses($message['id']))) {
           foreach($responses as $resp) {
               $staff=$cfg->hideStaffName()?'staff':Format::htmlchars($resp['staff_name']);
               ?>
               <table class="response" cellspacing="0" cellpadding="1" width="100%" border="0">
                <tr>
                    <th><?php echo Format::db_datetime($resp['created']);?>&nbsp;-&nbsp;<?php echo $staff; ?></th>
                </tr>
                <tr><td><?php echo Format::display($resp['body']); ?></td></tr>
                <?php
                if($resp['attachments'] && ($links=$ticket->getAttachmentsLinks($resp['id'],'R'))) {?>
                 <tr><td class="info"><?php echo $links; ?></td></tr>
                <?php
                 }?>
                </table>
            <?
           }
       }
    }
}
?>
</div>
<div class="clear" style="padding-bottom:10px;"></div>
<?php if($errors['err']) { ?>
    <div id="msg_error"><?php echo $errors['err']; ?></div>
<?php }elseif($msg) { ?>
    <div id="msg_notice"><?php echo $msg; ?></div>
<?php }elseif($warn) { ?>
    <div id="msg_warning"><?php echo $warn; ?></div>
<?php } ?>
<form id="reply" action="tickets.php?id=<?php echo $ticket->getExtId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
    <h2>Post a Reply</h2>
    <input type="hidden" name="id" value="<?php echo $ticket->getExtId(); ?>">
    <input type="hidden" name="a" value="reply">
    <table border="0" cellspacing="0" cellpadding="3" width="800">
        <tr>
            <td width="160">
                <label>Message:</label>
            </td>
            <td width="640">
                <?php
                if($ticket->isClosed()) {
                    $msg='<b>Ticket will be reopened on message post</b>';
                } else {
                    $msg='To best assist you, please be specific and detailed';
                }
                ?>
                <span id="msg"><em><?php echo $msg; ?> </em></span><font class="error">*&nbsp;<?php echo $errors['message']; ?></font><br/>
                <textarea name="message" id="message" cols="50" rows="9" wrap="soft"><?php echo $info['message']; ?></textarea>
            </td>
        </tr>
        <?php
        if($cfg->allowOnlineAttachments()) { ?>
        <tr>
            <td width="160">
                <label for="attachment">Attachments:</label>
            </td>
            <td width="640" id="reply_form_attachments" class="attachments">
                <div class="uploads">
                </div>
                <div class="file_input">
                    <input type="file" name="attachments[]" size="30" value="" />
                </div>
            </td>
        </tr>
        <?php
        } ?>
    </table>
    <p style="padding-left:165px;">
        <input type="submit" value="Post Reply">
        <input type="reset" value="Reset">
        <input type="button" value="Cancel" onClick="history.go(-1)">
    </p>
</form>
