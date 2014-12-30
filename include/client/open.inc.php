<?php
if (!defined('OSTCLIENTINC')) {
    die('Access Denied!');
}
$info = array();
if ($thisclient && $thisclient->isValid()) {
    $info = array('name' => $thisclient->getName(),
        'email' => $thisclient->getEmail(),
        'phone' => $thisclient->getPhoneNumber());
}

$info = ($_POST && $errors) ? Format::htmlchars($_POST) : $info;

$form = null;
if (!$info['topicId']) {
    $info['topicId'] = $cfg->getDefaultTopicId();
}

if ($info['topicId'] && ($topic = Topic::lookup($info['topicId']))) {
    $form = $topic->getForm();
    if ($_POST && $form) {
        $form = $form->instanciate();
        $form->isValidForClient();
    }
}
?>
<h1><?= __('Open a New Ticket'); ?></h1>
<p><?= __('Please fill in the form below to open a new ticket.'); ?></p>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="open">
    <table width="800" cellpadding="1" cellspacing="0" border="0">
        <tbody>
            <tr>
                <td class="required"><?= __('Help Topic'); ?>:</td>
                <td>
                    <select id="topicId" name="topicId" onchange="javascript:
                                    var data = $(':input[name]', '#dynamic-form').serialize();
                            $.ajax(
                                    'ajax.php/form/help-topic/' + this.value,
                                    {
                                        data: data,
                                        dataType: 'json',
                                        success: function (json) {
                                            $('#dynamic-form').empty().append(json.html);
                                            $(document.head).append(json.media);
                                        }
                                    });">
                        <option value="" selected="selected">&mdash; <?= __('Select a Help Topic'); ?> &mdash;</option>
                        <?php
                        if ($topics = Topic::getPublicHelpTopics()) :
                            foreach ($topics as $id => $name) {
                                echo sprintf('<option value="%d" %s>%s</option>', $id, ($info['topicId'] == $id) ? 'selected="selected"' : '', $name);
                            }
                        else :
                            ?>
                            <option value="0" ><?= __('General Inquiry'); ?></option>
                        <?php endif; ?>
                    </select>
                    <font class="error">*&nbsp;<?= $errors['topicId']; ?></font>
                </td>
            </tr>
            <?php
            if (!$thisclient) :
                $uform = UserForm::getUserForm()->getForm($_POST);
                if ($_POST) {
                    $uform->isValid();
                }
                $uform->render(false);

            else :
                ?>
                <tr><td colspan="2"><hr /></td></tr>
                <tr><td><?= __('Email'); ?>:</td><td><?= $thisclient->getEmail(); ?></td></tr>
                <tr><td><?= __('Client'); ?>:</td><td><?= $thisclient->getName(); ?></td></tr>
            <?php endif; ?>
        </tbody>
        <tbody id="dynamic-form">
            <?php
            if ($form) {
                include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
            }
            ?>
        </tbody>
        <tbody><?php
            $tform = TicketForm::getInstance()->getForm($_POST);
            if ($_POST) {
                $tform->isValid();
            }
            $tform->render(false);
            ?>
        </tbody>
        <tbody>
            <?php
            if ($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) :
                if ($_POST && $errors && !$errors['captcha']) {
                    $errors['captcha'] = __('Please re-enter the text again');
                }
                ?>
                <tr class="captchaRow">
                    <td class="required"><?= __('CAPTCHA Text'); ?>:</td>
                    <td>
                        <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
                        &nbsp;&nbsp;
                        <input id="captcha" type="text" name="captcha" size="6" autocomplete="off">
                        <em><?= __('Enter the text shown on the image.'); ?></em>
                        <font class="error">*&nbsp;<?= $errors['captcha']; ?></font>
                    </td>
                </tr>
            <?php endif; ?>
            <tr><td colspan=2>&nbsp;</td></tr>
        </tbody>
    </table>
    <hr/>
    <p style="text-align:center;">
        <input type="submit" value="<?= __('Create Ticket'); ?>">
        <input type="reset" name="reset" value="<?= __('Reset'); ?>">
        <input type="button" name="cancel" value="<?= __('Cancel'); ?>" onclick="javascript:
                        $('.richtext').each(function () {
                    var redactor = $(this).data('redactor');
                    if (redactor && redactor.opts.draftDelete)
                        redactor.deleteDraft();
                });
                window.location.href = 'index.php';">
    </p>
</form>
