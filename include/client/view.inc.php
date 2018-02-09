<?php
if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

$info=($_POST && $errors)?Format::htmlchars($_POST):array();

$dept = $ticket->getDept();

if ($ticket->isClosed() && !$ticket->isReopenable())
    $warn = sprintf(__('%s is marked as closed and cannot be reopened.'), __('This ticket'));

//Making sure we don't leak out internal dept names
if(!$dept || !$dept->isPublic())
    $dept = $cfg->getDefaultDept();

?>
<div class="subnav">


    <div class="float-left subnavtitle" id="ticketviewtitle">
                        
        <a href="tickets.php?id=<?php echo $ticket->getId(); ?>" title="<?php echo __('Reload'); ?>"><i class="icon-refresh"></i>
            <?php echo sprintf(__('Ticket #%s'), $ticket->getNumber()); ?></a>
                
            <span  class=""> - <span style="color: <?php echo $ticket->isOpen() ? '#51c351;' : '#f00;'; ?>">
            <?php echo sprintf(__('%s'), $ticket->getStatus()); ?></span></span>
            
            - <?php $subject_field = TicketForm::getInstance()->getField('subject');
            echo $subject_field->display($ticket->getSubject()); ?> 
    </div>
                     
 <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">

   <a class="btn btn-icon waves-effect waves-light btn-light" id="tickets-helptopic" data-placement="bottom" data-toggle="tooltip" title="" href="tickets.php?a=print&id=<?php
        echo $ticket->getId(); ?>" data-original-title="Print Ticket"><i class="fa fa-print"></i></a>
   <a class="btn btn-icon waves-effect waves-light btn-light" id="tickets-helptopic" data-placement="bottom" data-toggle="tooltip" title="" href="tickets.php?a=edit&id=<?php
                     echo $ticket->getId(); ?>" data-original-title="Edit Ticket"><i class="fa fa-edit"></i></a>
    <a class="btn btn-light btn-sm waves-effect" data-placement="bottom"data-toggle="tooltip" title="<?php echo __('Tickets'); ?>"
    href="tickets.php<?php ?>"><i class="icon-list-alt"></i></a>

</div>
<div class="clearfix"></div>
</div>
<div class="card-box">


<div class="row">
    <div class="col-md-6 boldlabels">
		<div>
			<label><?php echo __('Ticket Status');?>:</label>
			<?php echo ($S = $ticket->getStatus()) ? $S->display() : ''; ?>
		</div>
        <div>
			<label><?php echo __('Assigned to');?>:</label>
			<?php echo Format::htmlchars($ticket->getStaff()); ?>
		</div>
		<div>
			<label><?php echo __('Create Date');?>:</label>
			<?php echo Format::datetime($ticket->getCreateDate()); ?>
		</div>
        <div>
			<label><?php echo __('Help Topic');?>:</label>
			<?php echo Format::htmlchars($ticket->getHelpTopic()); ?>
		</div>
    </div>
	<div class="col-md-6 boldlabels">
		
		<div>
			<label><?php echo __('Name');?>:</label>
			<?php echo mb_convert_case(Format::htmlchars($ticket->getName()), MB_CASE_TITLE); ?>
		</div>
		<div>
			<label><?php echo __('Email');?>:</label>
			<?php echo Format::htmlchars($ticket->getEmail()); ?>
		</div>
		<div>
			<label><?php echo __('Phone');?>:</label>
			<?php echo $ticket->getPhoneNumber(); ?>
		 </div> 
	 </div>
</div>

<div class="row">
<div class="col-md-12">
<!-- Custom Data -->
<?php
$sections = array();
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $i=>$form) {
    // Skip core fields shown earlier in the ticket view
    $answers = $form->getAnswers()->exclude(Q::any(array(
        'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
        'field__name__in' => array('subject', 'priority'),
        Q::not(array('field__flags__hasbit' => DynamicFormField::FLAG_CLIENT_VIEW)),
    )));
    // Skip display of forms without any answers
    foreach ($answers as $j=>$a) {
        if ($v = $a->display())
            $sections[$i][$j] = array($v, $a);
    }
}
foreach ($sections as $i=>$answers) {
    ?>
        <div class="col-md-4 row">
        <div><h3><?php echo $form->getTitle(); ?></h3></div>
        <?php foreach($answers as $A) {
            list($v, $a) = $A; ?>
            <div>
                <label><?php
    echo $a->getField()->get('label')
                ?>:</label>
               <?php
    echo $v;
                ?>
            
            <?php } ?>
			</div>
    <?php
    $idx++;
} ?></br>
	</div>
</div>
</div>
<div class="card-box">
<div class="col-md-12">

<?php
    $ticket->getThread()->render(array('M', 'R'), array(
                'mode' => Thread::MODE_CLIENT,
                'html-id' => 'ticketThread')
            );

if (!$ticket->isClosed() || $ticket->isReopenable()) { ?>
<div id="ReponseTabs" class="ClientReponseTabs">
    <ul class="nav nav-pills" id="ticket_tabs">
		<li class="nav-item">
            <a class="nav-link active" id="ticket-thread-tab" href="#reply" data-toggle="tab">Post Reply</a>
		</li>
    </ul>
    <div class="tab-content clearfix">
        <div class="tab-pane active" id="reply">
            <form id="reply" action="tickets.php?id=<?php echo $ticket->getId();
            ?>#reply" name="reply" method="post" enctype="multipart/form-data">
                <?php csrf_token(); ?>
                <h2><?php echo __('Post a Reply');?></h2>
                <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
                <input type="hidden" name="a" value="reply">
                <div>
                    <p><em><?php
                     echo __('To best assist you, we request that you be specific and detailed'); ?></em>
                    <font class="error alert">*&nbsp;<?php echo $errors['message']; ?></font>
                    </p>
                    <textarea name="message" id="message" cols="50" rows="9" wrap="soft" placeholder="Start writing your response here."
                                                    
                        class="form-control requiredfield <?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                            ?> draft" <?php
            list($draft, $attrs) = Draft::getDraftAndDataAttrs('ticket.client', $ticket->getId(), $info['message']);
            echo $attrs; ?>><?php echo $draft ?: $info['message'];
                        ?></textarea>
                <?php
                if ($messageField->isAttachmentsEnabled()) {
                    print $attachments->render(array('client'=>true));
                } ?>
                </div>
            <?php if ($ticket->isClosed()) { ?>
                <div class="alert alert-info">
                    <?php echo __('Ticket will be reopened on message post'); ?>
                </div>
            <?php } ?>
                <p >
                    <input  class="btn btn-success" type="submit" value="<?php echo __('Post Reply');?>">
                    <input  class="btn btn-warning" type="reset" value="<?php echo __('Reset');?>">
                    <input  class="btn btn-danger" type="button" value="<?php echo __('Cancel');?>" onClick="location.href='/tickets.php'">
                </p>
            </form>
</div></div></div>
<div class="clearfix"></div></div>
<?php
} ?>
<script type="text/javascript">
<?php
// Hover support for all inline images
$urls = array();
foreach (AttachmentFile::objects()->filter(array(
    'attachments__thread_entry__thread__id' => $ticket->getThreadId(),
    'attachments__inline' => true,
)) as $file) {
    $urls[strtolower($file->getKey())] = array(
        'download_url' => $file->getDownloadUrl(),
        'filename' => $file->name,
    );
} ?>
showImagesInline(<?php echo JsonDataEncoder::encode($urls); ?>);
</script>
