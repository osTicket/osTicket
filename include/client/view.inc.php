<?php
if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

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
                Ticket #<?php echo $ticket->getNumber(); ?> &nbsp;
                <a href="view.php?id=<?php echo $ticket->getId(); ?>" title="Reload"><span class="Icon refresh">&nbsp;</span></a>
<?php if ($cfg->allowClientUpdates()) { ?>
                <a class="action-button" href="tickets.php?a=edit&id=<?php
                     echo $ticket->getId(); ?>"><i class="icon-edit"></i> Edit</a>
<?php } ?>
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
                    <td><?php echo Format::htmlchars($dept instanceof Dept ? $dept->getName() : ''); ?></td>
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
                   <td><?php echo ucfirst(Format::htmlchars($ticket->getName())); ?></td>
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
    <tr>
<?php
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $idx=>$form) {
    $answers = $form->getAnswers();
    if ($idx > 0 and $idx % 2 == 0) { ?>
        </tr><tr>
    <?php } ?>
    <td width="50%">
        <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
    <?php foreach ($answers as $answer) {
        if (in_array($answer->getField()->get('name'), array('name', 'email', 'subject')))
            continue;
        elseif ($answer->getField()->get('private'))
            continue;
        ?>
        <tr>
        <th width="100"><?php echo $answer->getField()->get('label');
            ?>:</th>
        <td><?php echo $answer->display(); ?></td>
        </tr>
    <?php } ?>
    </table></td>
<?php } ?>
</tr>
</table>
<br>
<h2>Subject:<?php echo Format::htmlchars($ticket->getSubject()); ?></h2>
<br>
<span class="Icon thread">Ticket Thread</span>
<div id="ticketThread">
<?php
if($ticket->getThreadCount() && ($thread=$ticket->getClientThread())) {
    $threadType=array('M' => 'message', 'R' => 'response');
    foreach($thread as $entry) {
        if ($entry['body'] == '-')
            $entry['body'] = '(EMPTY)';

        //Making sure internal notes are not displayed due to backend MISTAKES!
        if(!$threadType[$entry['thread_type']]) continue;
        $poster = $entry['poster'];
        if($entry['thread_type']=='R' && ($cfg->hideStaffName() || !$entry['staff_id']))
            $poster = ' ';
        ?>
        <table class="thread-entry <?php echo $threadType[$entry['thread_type']]; ?>" cellspacing="0" cellpadding="1" width="800" border="0">
            <tr><th><?php echo Format::db_datetime($entry['created']); ?> &nbsp;&nbsp;<span class="textra"></span><span><?php echo $poster; ?></span></th></tr>
            <tr><td class="thread-body"><div><?php echo Format::viewableImages(Format::display($entry['body'])); ?></div></td></tr>
            <?php
            if($entry['attachments']
                    && ($tentry=$ticket->getThreadEntry($entry['id']))
                    && ($urls = $tentry->getAttachmentUrls())
                    && ($links=$tentry->getAttachmentsLinks())) { ?>
                <script type="text/javascript">
                    $(function() { showImagesInline(<?php echo
                        JsonDataEncoder::encode($urls); ?>); });
                </script>
                <tr><td class="info"><?php echo $links; ?></td></tr>
            <?php
            } ?>
        </table>
    <?php
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
<form id="reply" action="tickets.php?id=<?php echo $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
    <?php csrf_token(); ?>
    <h2>Post a Reply</h2>
    <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
    <input type="hidden" name="a" value="reply">
    <table border="0" cellspacing="0" cellpadding="3" style="width:100%">
        <tr>
            <td colspan="2">
                <?php
                if($ticket->isClosed()) {
                    $msg='<b>Ticket will be reopened on message post</b>';
                } else {
                    $msg='To best assist you, please be specific and detailed';
                }
                ?>
                <span id="msg"><em><?php echo $msg; ?> </em></span><font class="error">*&nbsp;<?php echo $errors['message']; ?></font>
                <br/>
                <textarea name="message" id="message" cols="50" rows="9" wrap="soft"
                    data-draft-namespace="ticket.client"
                    data-draft-object-id="<?php echo $ticket->getId(); ?>"
                    class="richtext ifhtml draft"><?php echo $info['message']; ?></textarea>
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
                    <input class="multifile" type="file" name="attachments[]" size="30" value="" />
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
