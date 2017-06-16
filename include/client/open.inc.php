<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId',$_GET) && preg_match('/^\d+$/',$_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F;
    }
} ?>

<h1><?php echo __('Open a New Ticket');?></h1>
<p><?php echo __('Please fill in the form below to open a new ticket.');?></p>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="open">
    <?php
    if (!$thisclient) {
        $uform = UserForm::getUserForm()->getForm($_POST); ?>
        <div class="panel panel-primary">
            <div class="panel-body">
                <?php if ($_POST) $uform->isValid(); ?>
                <div class="osticket-group">
                    <?php $uform->render(false); ?>
                </div>
            </div>
        </div>
    <?php
    } else { ?>
        <div class="panel panel-primary">
            <div class="panel-body">
                <div class="form-group">
                    <?php echo __('Email'); ?>:
                    <?php echo $thisclient->getEmail(); ?>
                </div>
                <div class="form-group">
                    <?php echo __('Client'); ?>:
                    <?php echo Format::htmlchars($thisclient->getName()); ?>
                </div>
            </div>
        </div>
    <?php
    } ?>
    <div class="panel panel-primary">
        <div class="panel-body">
            <div class="form-group">
                <div class="form-header">
                    <b>
                        <?php echo __('Help Topic'); ?>
                        <p class="error inl-blk">*&nbsp;<?php echo $errors['topicId']; ?></p>
                    </b>
                </div>
            </div>

            <div class="form-group">
                <select class="form-control" id="topicId" name="topicId" onchange="javascript:
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
                    <option value="" selected="selected">&mdash; <?php echo __('Select a Help Topic');?> &mdash;</option>
                    <?php
                    if($topics=Topic::getPublicHelpTopics()) {
                        foreach($topics as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                        }
                    } else { ?>
                        <option value="0" ><?php echo __('General Inquiry');?></option>
                    <?php
                    } ?>
                </select>
            </div>

            <div id="dynamic-form">
                <?php foreach ($forms as $form) {
                    include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
                } ?>
            </div>
            <?php
            if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
                if($_POST && $errors && !$errors['captcha'])
                    $errors['captcha']=__('Please re-enter the text again');
                ?>
                <div class="captchaRow form-group">
                    <div class="required"><?php echo __('CAPTCHA Text');?>:</div>
                    <p>
                        <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
                        &nbsp;&nbsp;
                        <input id="captcha" type="text" name="captcha" size="6" autocomplete="off">
                        <em><?php echo __('Enter the text shown on the image.');?></em>
                        <p class="error">*&nbsp;<?php echo $errors['captcha']; ?></p>
                    </p>
                </div>
            <?php
            } ?>
            <p>&nbsp;</p>
        </div>
        <div class="panel-footer">
            <div class="text-center">
                <input class="btn btn-primary" type="submit" value="<?php echo __('Create Ticket');?>">
                <input class="btn btn-default" type="reset" name="reset" value="<?php echo __('Reset');?>">
                <input class="btn btn-default" type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick="javascript:
                    $('.richtext').each(function() {
                        var redactor = $(this).data('redactor');
                        if (redactor && redactor.opts.draftDelete)
                            redactor.deleteDraft();
                    });
                    window.location.href='index.php';">
            </div>
        </div>
    </div>
</form>
