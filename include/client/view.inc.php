<?php
if(!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) die('Access Denied!');

$info=($_POST && $errors)?Format::htmlchars($_POST):array();

$dept = $ticket->getDept();

if ($ticket->isClosed() && !$ticket->isReopenable())
    $warn = sprintf(__('%s is marked as closed and cannot be reopened.'), __('This ticket'));

//Making sure we don't leak out internal dept names
if(!$dept || !$dept->isPublic())
    $dept = $cfg->getDefaultDept();

if ($thisclient && $thisclient->isGuest()
    && $cfg->isClientRegistrationEnabled()) { ?>
    <div id="msg_info">
        <i class="icon-compass icon-2x pull-left"></i>
        <strong><?php echo __('Looking for your other tickets?'); ?></strong><br />
        <a href="<?php echo ROOT_PATH; ?>login.php?e=<?php
            echo urlencode($thisclient->getEmail());
        ?>" style="text-decoration:underline"><?php echo __('Sign In'); ?></a>
        <?php echo sprintf(__('or %s register for an account %s for the best experience on our help desk.'),
            '<a href="account.php?do=create" style="text-decoration:underline">','</a>'); ?>
    </div>
<?php } ?>

<div class="col-md-12">
    <div class="panel panel-primary">
        <div class="panel-body">
            <h1>
                <a href="tickets.php?id=<?php echo $ticket->getId(); ?>" title="<?php echo __('Reload'); ?>"><i class="refresh icon-refresh"></i></a>
                <b>
                <?php $subject_field = TicketForm::getInstance()->getField('subject');
                    echo $subject_field->display($ticket->getSubject()); ?>
                </b>
                <small>#<?php echo $ticket->getNumber(); ?></small>
                <div class="pull-right">
                    <a class="action-button" href="tickets.php?a=print&id=<?php
                        echo $ticket->getId(); ?>"><i class="icon-print"></i> <?php echo __('Print'); ?></a>
                <?php if ($ticket->hasClientEditableFields()
                        // Only ticket owners can edit the ticket details (and other forms)
                        && $thisclient->getId() == $ticket->getUserId()) { ?>
                                <a class="action-button" href="tickets.php?a=edit&id=<?php
                                    echo $ticket->getId(); ?>"><i class="icon-edit"></i> <?php echo __('Edit'); ?></a>
                <?php } ?>
                </div>
            </h1>

            <div class="infoTable">
                <h1 class="headline"><?php echo __('Basic Ticket Information'); ?></h1>
                <div>
                    <div><?php echo __('Ticket Status');?>:</div>
                    <div><?php echo ($S = $ticket->getStatus()) ? $S->getLocalName() : ''; ?></div>
                </div>
                <div>
                    <div><?php echo __('Department');?>:</div>
                    <div><?php echo Format::htmlchars($dept instanceof Dept ? $dept->getName() : ''); ?></div>
                </div>
                <div>
                    <div><?php echo __('Create Date');?>:</div>
                    <div><?php echo Format::datetime($ticket->getCreateDate()); ?></div>
                </div>
            </div>

            <div class="infoTable">
                <div class="headline">
                    <?php echo __('User Information'); ?>
                </div>
                <div>
                    <th width="100"><?php echo __('Name');?>:</th>
                    <td><?php echo mb_convert_case(Format::htmlchars($ticket->getName()), MB_CASE_TITLE); ?></td>
                </div>
                <div>
                    <th width="100"><?php echo __('Email');?>:</th>
                    <td><?php echo Format::htmlchars($ticket->getEmail()); ?></td>
                </div>
                <div>
                    <th><?php echo __('Phone');?>:</th>
                    <td><?php echo $ticket->getPhoneNumber(); ?></td>
                </div>
            </div>
            
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
            foreach ($sections as $i=>$answers) { ?>
                <div class="headline flush-left"><?php echo $form->getTitle(); ?></div>
<?php           foreach ($answers as $A) {
                    list($v, $a) = $A; ?>
                    <div><?php echo $a->getField()->get('label'); ?>:</div>
                    <div><?php echo $v; ?></div>
<?php           }
            } ?>
        </div>
    </div>
</div>
<br>

<?php
$ticket->getThread()->render(
    array('M', 'R'),
    array(
        'mode' => Thread::MODE_CLIENT,
        'html-id' => 'ticketThread'
    )
);
?>

<div class="clear" style="padding-bottom:10px;"></div>
<?php if($errors['err']) { ?>
    <div id="msg_error"><?php echo $errors['err']; ?></div>
<?php }elseif($msg) { ?>
    <div id="msg_notice"><?php echo $msg; ?></div>
<?php }elseif($warn) { ?>
    <div id="msg_warning"><?php echo $warn; ?></div>
<?php }

if (!$ticket->isClosed() || $ticket->isReopenable()) { ?>
<div class="col-md-12">
    <div class="panel panel-primary">
        <div class="panel-body">
            <form id="reply" action="tickets.php?id=<?php echo $ticket->getId();
            ?>#reply" name="reply" method="post" enctype="multipart/form-data">
                <?php csrf_token(); ?>
                <h2><?php echo __('Post a Reply');?></h2>
                <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
                <input type="hidden" name="a" value="reply">
                <div>
                    <p>
                        <em><?php echo __('To best assist you, we request that you be specific and detailed'); ?></em>
                        <font class="error">*&nbsp;<?php echo $errors['message']; ?></font>
                    </p>
                    <textarea name="message" id="message" cols="50" rows="9" wrap="soft"
                        class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
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
                <div class="warning-banner">
                    <?php echo __('Ticket will be reopened on message post'); ?>
                </div>
            <?php } ?>
                <p style="text-align:center">
                    <input type="submit" value="<?php echo __('Post Reply');?>">
                    <input type="reset" value="<?php echo __('Reset');?>">
                    <input type="button" value="<?php echo __('Cancel');?>" onClick="history.go(-1)">
                </p>
            </form>
        </div>
    </div>
</div>
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
