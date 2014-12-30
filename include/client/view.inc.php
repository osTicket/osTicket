<?php
if (!defined('OSTCLIENTINC') || !$thisclient || !$ticket || !$ticket->checkUserAccess($thisclient)) {
    die('Access Denied!');
}

$info = ($_POST && $errors) ? Format::htmlchars($_POST) : array();

$dept = $ticket->getDept();

if ($ticket->isClosed() && !$ticket->isReopenable()) {
    $warn = __('This ticket is marked as closed and cannot be reopened.');
}

//Making sure we don't leak out internal dept names
if (!$dept || !$dept->isPublic()) {
    $dept = $cfg->getDefaultDept();
}

if ($thisclient && $thisclient->isGuest() && $cfg->isClientRegistrationEnabled()) :
    ?>

    <div id="msg_info">
        <i class="icon-compass icon-2x pull-left"></i>
        <strong><?= __('Looking for your other tickets?'); ?></strong></br>
        <a href="<?= ROOT_PATH; ?>login.php?e=<?= urlencode($thisclient->getEmail()); ?>" style="text-decoration:underline"><?= __('Sign In'); ?></a>
        <?= sprintf(__('or %s register for an account %s for the best experience on our help desk.'), '<a href="account.php?do=create" style="text-decoration:underline">', '</a>'); ?>
    </div>

<?php endif ?>

<table width="800" cellpadding="1" cellspacing="0" border="0" id="ticketInfo">
    <tr>
        <td colspan="2" width="100%">
            <h1>
                <?= sprintf(__('Ticket #%s'), $ticket->getNumber()); ?> &nbsp;
                <a href="tickets.php?id=<?= $ticket->getId(); ?>" title="Reload"><span class="Icon refresh">&nbsp;</span></a>
                <?php if ($cfg->allowClientUpdates() && $thisclient->getId() == $ticket->getUserId()) : // Only ticket owners can edit the ticket details (and other forms) ?>
                    <a class="action-button pull-right" href="tickets.php?a=edit&id=<?= $ticket->getId(); ?>"><i class="icon-edit"></i> Edit</a>
                <?php endif; ?>
            </h1>
        </td>
    </tr>
    <tr>
        <td width="50%">
            <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
                <tr>
                    <th width="100"><?= __('Ticket Status'); ?>:</th>
                    <td><?= $ticket->getStatus(); ?></td>
                </tr>
                <tr>
                    <th><?= __('Department'); ?>:</th>
                    <td><?= Format::htmlchars($dept instanceof Dept ? $dept->getName() : ''); ?></td>
                </tr>
                <tr>
                    <th><?= __('Create Date'); ?>:</th>
                    <td><?= Format::db_datetime($ticket->getCreateDate()); ?></td>
                </tr>
            </table>
        </td>
        <td width="50%">
            <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
                <tr>
                    <th width="100"><?= __('Name'); ?>:</th>
                    <td><?= mb_convert_case(Format::htmlchars($ticket->getName()), MB_CASE_TITLE); ?></td>
                </tr>
                <tr>
                    <th width="100"><?= __('Email'); ?>:</th>
                    <td><?= Format::htmlchars($ticket->getEmail()); ?></td>
                </tr>
                <tr>
                    <th><?= __('Phone'); ?>:</th>
                    <td><?= $ticket->getPhoneNumber(); ?></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <?php
        foreach (DynamicFormEntry::forTicket($ticket->getId()) as $idx => $form) :
            $answers = $form->getAnswers();
            if ($idx > 0 and $idx % 2 == 0) :
                ?>
            </tr><tr>
            <?php endif; ?>
            <td width="50%">
                <table class="infoTable" cellspacing="1" cellpadding="3" width="100%" border="0">
                    <?php
                    foreach ($answers as $answer) :
                        if (in_array($answer->getField()->get('name'), array('name', 'email', 'subject'))) {
                            continue;
                        } elseif ($answer->getField()->get('private')) {
                            continue;
                        }
                        ?>
                        <tr>
                            <th width="100"><?= $answer->getField()->get('label'); ?>:</th>
                            <td><?= $answer->display(); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table></td>
        <?php endforeach; ?>
    </tr>
</table>
<br>
<div class="subject"><?= __('Subject'); ?>: <strong><?= Format::htmlchars($ticket->getSubject()); ?></strong></div>
<div id="ticketThread">
    <?php
    if ($ticket->getThreadCount() && ($thread = $ticket->getClientThread())) :
        $threadType = array('M' => 'message', 'R' => 'response');
        foreach ($thread as $entry) :

            //Making sure internal notes are not displayed due to backend MISTAKES!
            if (!$threadType[$entry['thread_type']]) {
                continue;
            }
            $poster = $entry['poster'];
            if ($entry['thread_type'] == 'R' && ($cfg->hideStaffName() || !$entry['staff_id'])) {
                $poster = ' ';
            }
            ?>
            <table class="thread-entry <?= $threadType[$entry['thread_type']]; ?>" cellspacing="0" cellpadding="1" width="800" border="0">
                <tr><th><div>
                    <?= Format::db_datetime($entry['created']); ?>
                    &nbsp;&nbsp;<span class="textra"></span>
                    <span><?= $poster; ?></span>
                </div>
                </th></tr>
                <tr><td class="thread-body"><div><?= $entry['body']->toHtml(); ?></div></td></tr>
                <?php if ($entry['attachments'] && ($tentry = $ticket->getThreadEntry($entry['id'])) && ($urls = $tentry->getAttachmentUrls()) && ($links = $tentry->getAttachmentsLinks())) : ?>
                    <tr><td class="info"><?= $links; ?></td></tr>
                    <?php
                endif;
                if ($urls) :
                    ?>
                    <script type="text/javascript">
                        $(function () {
                            showImagesInline(<?= JsonDataEncoder::encode($urls); ?>);
                        });
                    </script>
                <?php endif; ?>
            </table>
            <?php
        endforeach;
    endif;
    ?>
</div>
<div class="clear" style="padding-bottom:10px;"></div>
<?php if ($errors['err']) : ?>
    <div id="msg_error"><?= $errors['err']; ?></div>
<?php elseif ($msg) : ?>
    <div id="msg_notice"><?= $msg; ?></div>
<?php elseif ($warn) : ?>
    <div id="msg_warning"><?= $warn; ?></div>
<?php endif; ?>

<?php if (!$ticket->isClosed() || $ticket->isReopenable()) : ?>
    <form id="reply" action="tickets.php?id=<?= $ticket->getId(); ?>#reply" name="reply" method="post" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <h2><?= __('Post a Reply'); ?></h2>
        <input type="hidden" name="id" value="<?= $ticket->getId(); ?>">
        <input type="hidden" name="a" value="reply">
        <table border="0" cellspacing="0" cellpadding="3" style="width:100%">
            <tr>
                <td colspan="2">
                    <?php
                    if ($ticket->isClosed()) {
                        $msg = '<b>' . __('Ticket will be reopened on message post') . '</b>';
                    } else {
                        $msg = __('To best assist you, we request that you be specific and detailed');
                    }
                    ?>
                    <span id="msg"><em><?= $msg; ?> </em></span><font class="error">*&nbsp;<?= $errors['message']; ?></font>
                    <br/>
                    <textarea name="message" id="message" cols="50" rows="9" wrap="soft"
                              data-draft-namespace="ticket.client"
                              data-draft-object-id="<?= $ticket->getId(); ?>"
                              class="richtext ifhtml draft"><?= $info['message']; ?></textarea>
                              <?php
                              if ($messageField->isAttachmentsEnabled()) {
                                  print $attachments->render(true);
                              }
                              ?>
                </td>
            </tr>
        </table>
        <p style="padding-left:165px;">
            <input type="submit" value="<?= __('Post Reply'); ?>">
            <input type="reset" value="<?= __('Reset'); ?>">
            <input type="button" value="<?= __('Cancel'); ?>" onClick="history.go(-1)">
        </p>
    </form>
    <?php

endif;
