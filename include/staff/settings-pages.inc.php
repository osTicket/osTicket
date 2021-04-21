<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
$pages = Page::getPages();
?>
<h2><?php echo __('Company Profile'); ?></h2>
<form action="settings.php?t=pages" method="post" class="save"
    enctype="multipart/form-data">
<?php csrf_token(); ?>



<input type="hidden" name="t" value="pages" >

<ul class="clean tabs">
    <li class="active"><a href="#basic-information"><i class="icon-asterisk"></i>
        <?php echo __('Basic Information'); ?></a></li>
    <li><a href="#site-pages"><i class="icon-file"></i>
        <?php echo __('Site Pages'); ?></a></li>
    <li><a href="#logos"><i class="icon-picture"></i>
        <?php echo __('Logos'); ?></a></li>
    <li><a href="#backdrops"><i class="icon-picture"></i>
        <?php echo __('Login Backdrop'); ?></a></li>
</ul>

<div class="tab_content" id="basic-information">
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
    <?php
        $form = $ost->company->getForm();
        $form->addMissingFields();
        $form->render();
    ?>
    </tbody>
</table>
</div>
<div class="hidden tab_content" id="site-pages">
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo sprintf(__(
                'To edit or add new pages go to %s Manage &gt; Site Pages %s'),
                '<a href="pages.php">','</a>'); ?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="required"><?php echo __('Landing Page'); ?>:</td>
            <td>
                <span>
                <select name="landing_page_id">
                    <option value="">&mdash; <?php echo __('Select Landing Page'); ?> &mdash;</option>
                    <?php
                    foreach($pages as $page) {
                        if(strcasecmp($page->getType(), 'landing')) continue;
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $page->getId(),
                                ($config['landing_page_id']==$page->getId())?'selected="selected"':'',
                                $page->getName());
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['landing_page_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#landing_page"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required"><?php echo __('Offline Page'); ?>:</td>
            <td>
                <span>
                <select name="offline_page_id">
                    <option value="">&mdash; <?php echo __('Select Offline Page');
                        ?> &mdash;</option>
                    <?php
                    foreach($pages as $page) {
                        if(strcasecmp($page->getType(), 'offline')) continue;
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $page->getId(),
                                ($config['offline_page_id']==$page->getId())?'selected="selected"':'',
                                $page->getName());
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['offline_page_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#offline_page"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="220" class="required"><?php
                echo __('Default Thank-You Page'); ?>:</td>
            <td>
                <span>
                <select name="thank-you_page_id">
                    <option value="">&mdash; <?php
                        echo __('Select Thank-You Page'); ?> &mdash;</option>
                    <?php
                    foreach($pages as $page) {
                        if(strcasecmp($page->getType(), 'thank-you')) continue;
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $page->getId(),
                                ($config['thank-you_page_id']==$page->getId())?'selected="selected"':'',
                                $page->getName());
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['thank-you_page_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#default_thank_you_page"></i>
                </span>
            </td>
        </tr>
    </tbody>
</table>
</div>
<div class="hidden tab_content" id="logos">
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('System Default Logo'); ?><i class="help-tip icon-question-sign" href="#logos"></i></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">
                <table style="width:100%">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Staff</th>
                            <th>Logo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="radio" name="selected-logo" value="0"
                                       style="margin-left: 1em"
                                       <?php if (!$ost->getConfig()->getClientLogoId())
                                        echo 'checked="checked"'; ?>/>
                            </td>
                            <td>
                                <input type="radio" name="selected-logo-scp" value="0"
                                       style="margin-left: 1em"
                                       <?php if (!$ost->getConfig()->getStaffLogoId())
                                            echo 'checked="checked"'; ?>/>
                            </td>
                            <td>
                                <img src="<?php echo ROOT_PATH; ?>assets/default/images/logo.png"
                                     alt="Default Logo" valign="middle"
                                     style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
                                            margin: 0.5em; height: 5em;
                                            vertical-align: middle"/>
                                <img src="<?php echo ROOT_PATH; ?>scp/images/ost-logo.png"
                                     alt="Default Logo" valign="middle"
                                     style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
                                            margin: 0.5em; height: 5em;
                                            vertical-align: middle"/>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="3">
                                <em><?php echo __('Use a custom logo'); ?>&nbsp;<i class="help-tip icon-question-sign" href="#upload_a_new_logo"></i></em>
                            </th>
                        </tr>
                        <?php
                        $current = $ost->getConfig()->getClientLogoId();
                        $currentScp = $ost->getConfig()->getStaffLogoId();
                        foreach (AttachmentFile::allLogos() as $logo) { ?>
                        <tr>
                            <td>
                                <input type="radio" name="selected-logo"
                                       style="margin-left: 1em" value="<?php
                            echo $logo->getId(); ?>" <?php
                            if ($logo->getId() == $current)
                                echo 'checked="checked"'; ?>/>
                            </td>
                            <td>
                                <input type="radio" name="selected-logo-scp"
                                       style="margin-left: 1em" value="<?php
                            echo $logo->getId(); ?>" <?php
                            if ($logo->getId() == $currentScp)
                                echo 'checked="checked"'; ?>/>
                            </td>
                            <td>
                                <img src="<?php echo $logo->getDownloadUrl(); ?>"
                                     alt="Custom Logo" valign="middle"
                                     style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
                                            margin: 0.5em; height: 5em;
                                            vertical-align: middle;"/>
                                <?php if ($logo->getId() != $current && $logo->getId() != $currentScp) { ?>
                                <label class="checkbox inline">
                                    <input type="checkbox" name="delete-logo[]" value="<?php
                                    echo $logo->getId(); ?>"/> <?php echo __('Delete'); ?>
                                </label>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <b><?php echo __('Upload a new logo'); ?>:</b>
                <input type="file" name="logo[]" size="30" value="" />
                <font class="error"><br/><?php echo $errors['logo']; ?></font>
            </td>
        </tr>
    </tbody>
</table>
</div>

<div class="hidden tab_content" id="backdrops">
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('System Default Backdrop'); ?><i
                class="help-tip icon-question-sign" href="#backdrops"></i></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="2">
                <table style="width:100%">
                    <thead>
                        <tr>
                            <th>Staff</th>
                            <th>Backdrop</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="radio" name="selected-backdrop" value="0"
                                       style="margin-left: 1em"
                                       <?php if (!$ost->getConfig()->getStaffLogoId())
                                            echo 'checked="checked"'; ?>/>
                            </td>
                            <td>
                                <img src="<?php echo ROOT_PATH; ?>scp/images/login-headquarters.jpg"
                                     alt="Default Backdrop" valign="middle"
                                     style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
                                            margin: 0.5em; height: 6em;
                                            vertical-align: middle"/>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <em><?php echo __('Use a custom backdrop');
                                ?>&nbsp;<i class="help-tip icon-question-sign" href="#upload_a_new_backdrop"></i></em>
                            </th>
                        </tr>
                        <?php
                        $current = $ost->getConfig()->getStaffLoginBackdropId();
                        foreach (AttachmentFile::allBackdrops() as $logo) { ?>
                        <tr>
                            <td>
                                <input type="radio" name="selected-backdrop"
                                       style="margin-left: 1em" value="<?php
                            echo $logo->getId(); ?>" <?php
                            if ($logo->getId() == $current)
                                echo 'checked="checked"'; ?>/>
                            </td>
                            <td>
                                <img src="<?php echo $logo->getDownloadUrl(); ?>"
                                     alt="Custom Backdrop" valign="middle"
                                     style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
                                            margin: 0.5em; height: 6em;
                                            vertical-align: middle;"/>
                                <?php if ($logo->getId() != $current) { ?>
                                <label class="checkbox inline">
                                    <input type="checkbox" name="delete-backdrop[]" value="<?php
                                    echo $logo->getId(); ?>"/> <?php echo __('Delete'); ?>
                                </label>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <b><?php echo __('Upload a new backdrop'); ?>:</b>
                <input type="file" name="backdrop[]" size="30" value="" />
                <font class="error"><br/><?php echo $errors['backdrop']; ?></font>
            </td>
        </tr>
    </tbody>
</table>
</div>

<p style="text-align:center;">
    <input class="button" type="submit" name="submit-button" value="<?php
    echo __('Save Changes'); ?>">
    <input class="button" type="reset" name="reset" value="<?php
    echo __('Reset Changes'); ?>">
</p>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected image', 'selected images', 2)); ?></strong></font>
        <br/><br/><?php echo __('Deleted data CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel'); ?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!'); ?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>

<script type="text/javascript">
$(function() {
    $('#save input:submit.button').bind('click', function(e) {
        var formObj = $('#save');
        if ($('input:checkbox:checked', formObj).length) {
            e.preventDefault();
            $('.dialog#confirm-action').undelegate('.confirm');
            $('.dialog#confirm-action').delegate('input.confirm', 'click', function(e) {
                e.preventDefault();
                $('.dialog#confirm-action').hide();
                $('#overlay').hide();
                formObj.submit();
                return false;
            });
            $('#overlay').show();
            $('.dialog#confirm-action .confirm-action').hide();
            $('.dialog#confirm-action p#delete-confirm')
            .show()
            .parent('div').show().trigger('click');
            return false;
        }
        else return true;
    });
});
</script>
