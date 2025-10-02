<?php
if (!defined('OSTCLIENTINC')) die('Access Denied!');
$info = array();
if ($thisclient && $thisclient->isValid()) {
    $info = array(
        'name' => $thisclient->getName(),
        'email' => $thisclient->getEmail(),
        'phone' => $thisclient->getPhoneNumber()
    );
}

$info = ($_POST && $errors) ? Format::htmlchars($_POST) : $info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId', $_GET) && preg_match('/^\d+$/', $_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}

$forms = array();
if ($info['topicId'] && ($topic = Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F->getForm();
    }
}

?>
<h1><?php echo __('<h1 class="text-center alg-text-h1 alg-text-dark">Open a New Ticket</h1>'); ?></h1>
<p class="text-center"><?php echo __('<span class="alg-text-p text-center">Please fill in the form below to open a new ticket</span>.'); ?></p>
<div class="alg-container">
    <form id="ticketForm" class="alg-rounded-large alg-shadow alg-bg-background-100 d-flex jus align-items-center flex-column px-5 py-3" style="box-shadow: rgba(0, 0, 0, 0.15) 1.95px 1.95px 2.6px;" method="post" action="open.php" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="open">.
        <table cellpadding="1" class="alg-open-ticket-inputs w-100 alg-input-container" cellspacing="0" border="0" style="margin-left: 0%">
            <tbody>
                <?php
                if (!$thisclient) {
                    $uform = UserForm::getUserForm()->getForm($_POST);
                    if ($_POST) $uform->isValid();
                    $uform->render(array('staff' => false, 'mode' => 'create'));
                } else { ?>

                    <tr>
                        <td><?php echo __('Email'); ?>:</td>
                        <td><?php
                            echo $thisclient->getEmail(); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo __('Client'); ?>:</td>
                        <td><?php
                            echo Format::htmlchars($thisclient->getName()); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tbody>
                <tr>
                    <td colspan="2">
                        <hr />
                        <div class="form-header" style="margin-bottom:0.5em">
                            <b class="alg-text-p"><?php echo __('Help Topic'); ?></b>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <select id="topicId" class="alg-topic-id" name="topicId" onchange="javascript:
                    var data = $(':input[name]', '#dynamic-form').serialize();
                    $.ajax(
                      'ajax.php/form/help-topic/' + this.value,
                      {
                        data: data,
                        dataType: 'json',
                        success: function(json) {
                          $('#dynamic-form').empty().append(json.html);
                          $(document.head).append(json.media);
                        }
                      });">
                            <option class="" value="" selected="selected">&mdash; <?php echo __('Select a Help Topic'); ?> &mdash;</option>
                            <?php
                            if ($topics = Topic::getPublicHelpTopics()) {
                                foreach ($topics as $id => $name) {
                                    echo sprintf(
                                        '<option value="%d" %s>%s</option>',
                                        $id,
                                        ($info['topicId'] == $id) ? 'selected="selected"' : '',
                                        $name
                                    );
                                }
                            } ?>
                        </select>
                        <font class="error">*&nbsp;<?php echo $errors['topicId']; ?></font>
                    </td>
                </tr>
            </tbody>
            <tbody id="dynamic-form">
                <?php
                $options = array('mode' => 'create');
                foreach ($forms as $form) {
                    include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
                } ?>
            </tbody>
            <tbody>
                <?php
                if ($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
                    if ($_POST && $errors && !$errors['captcha'])
                        $errors['captcha'] = __('Please re-enter the text again');
                ?>
                    <tr class="captchaRow">
                        <td class="required"><?php echo __('CAPTCHA Text'); ?>:</td>
                        <td>
                            <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
                            &nbsp;&nbsp;
                            <input id="captcha" type="text" name="captcha" size="6" autocomplete="off">
                            <em><?php echo __('Enter the text shown on the image.'); ?></em>
                            <font class="error">*&nbsp;<?php echo $errors['captcha']; ?></font>
                        </td>
                    </tr>
                <?php
                } ?>
                <tr>
                    <td colspan=2>&nbsp;</td>
                </tr>
            </tbody>
        </table>
        <hr />
        <p class="buttons alg-text-p w-100" style="text-align:center;">
            <input type="submit" class="my-1 px-3 text-white py-2 alg-bg-secondary-50" value="<?php echo __('Create Ticket'); ?>">
            <input type="reset" class="my-1 px-3 text-white py-2 alg-bg-secondary-100" name="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" class="my-1 px-3 py-2" name="cancel" value="<?php echo __('Cancel'); ?>" onclick="javascript:
            $('.richtext').each(function() {
                var redactor = $(this).data('redactor');
                if (redactor && redactor.opts.draftDelete)
                    redactor.plugin.draft.deleteDraft();
            });
            window.location.href='index.php';">
        </p>
    </form>
</div>