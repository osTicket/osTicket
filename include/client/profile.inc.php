<h1><?php echo __('Manage Your Profile Information'); ?></h1>
<p><?php echo __(
'Use the forms below to update the information we have on file for your account'
); ?>
</p>
<form action="profile.php" method="post">
    <?php csrf_token(); ?>
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-primary">
                <div class="panel-body osticket-group">
                    <?php
                    foreach ($user->getForms() as $f) {
                        $f->render(false);
                    }

                    if ($acct = $thisclient->getAccount()) {
                        $info=$acct->getInfo();
                        $info=Format::htmlchars(($errors && $_POST)?$_POST:$info); ?>
                            <h3><?php echo __('Preferences'); ?></h3>
                            <label><?php echo __('Time Zone');?>:</label>
                            <div class="form-group">
                                <?php
                                $TZ_NAME = 'timezone';
                                $TZ_TIMEZONE = $info['timezone'];
                                include INCLUDE_DIR.'staff/templates/timezone.tmpl.php'; ?>
                                <div class="error"><?php echo $errors['timezone']; ?></div>
                            </div>
                        <?php if ($cfg->getSecondaryLanguages()) { ?>
                                <label><?php echo __('Preferred Language'); ?>:</label>
                                <div class="form-group">
                                    <?php
                                    $langs = Internationalization::getConfiguredSystemLanguages(); ?>
                                    <select name="lang">
                                        <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
                                        <?php foreach($langs as $l) {
                                            $selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : ''; ?>
                                            <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                                            ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
                                        <?php } ?>
                                    </select>
                                    <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
                                </div>
                        <?php }
                            if ($acct->isPasswdResetEnabled()) { ?>
                                <h3><?php echo __('Access Credentials'); ?></h3>
                                <?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
                                    <label><?php echo __('Current Password'); ?>:</label>
                                    <div class="form-group">
                                        <input type="password" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
                                        &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
                                    </div>
                                <?php } ?>
                                <label><?php echo __('New Password'); ?>:</label>
                                <div class="form-group">
                                    <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
                                    &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
                                </div>
                                <label><?php echo __('Confirm New Password'); ?>:</label>
                                <div class="form-group">
                                    <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
                                    &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
                                </div>
                        <?php } ?>
                    <?php } ?>
                </div>
                <div class="panel-footer">
                    <div class="text-center">
                        <input class="btn btn-primary" type="submit" value="<?php echo __('Update') ?>"/>
                        <input class="btn btn-default" type="reset" value="<?php echo __('Reset') ?>"/>
                        <input class="btn btn-default" type="button" value="<?php echo __('Cancel') ?>" onclick="javascript:
                            window.location.href='index.php';"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
