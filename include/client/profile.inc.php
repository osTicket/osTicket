<h1><?= __('Manage Your Profile Information'); ?></h1>
<p><?php
    echo __(
            'Use the forms below to update the information we have on file for your account'
    );
    ?>
</p>
<form action="profile.php" method="post">
    <?php csrf_token(); ?>
    <table width="800" class="padded">
        <?php
        foreach ($user->getForms() as $f) {

            $f->render(false);
        }
        if ($acct = $thisclient->getAccount()) :
            $info = Format::htmlchars(($errors && $_POST) ? $_POST : $acct->getInfo() );
            ?>
            <tr>
                <td colspan="2"><div><hr><h3><?= __('Preferences'); ?></h3></div></td>
            </tr>
            <td><?= __('Time Zone'); ?>:</td>
            <td>
                <select name="timezone_id" id="timezone_id">
                    <option value="0">&mdash; <?= __('Select Time Zone'); ?> &mdash;</option>
                    <?php
                    $sql = 'SELECT id, offset,timezone FROM ' . TIMEZONE_TABLE . ' ORDER BY id';
                    if (($res = db_query($sql)) && db_num_rows($res)) {
                        while (list($id, $offset, $tz) = db_fetch_row($res)) {
                            $sel = ($info['timezone_id'] == $id) ? 'selected="selected"' : '';
                            echo sprintf('<option value="%d" %s>GMT %s - %s</option>', $id, $sel, $offset, $tz);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?= $errors['timezone_id']; ?></span>
            </td>
            </tr>
            <tr>
                <td width="180"><?= __('Daylight Saving') ?>:</td>
                <td>
                    <input type="checkbox" name="dst" value="1" <?= $info['dst'] ? 'checked="checked"' : ''; ?>>
                    <?= __('Observe daylight saving'); ?>
                    <em>(<?php __('Current Time'); ?>:
                        <strong><?= Format::date($cfg->getDateTimeFormat(), Misc::gmtime(), $info['tz_offset'], $info['dst']); ?></strong>)</em>
                </td>
            </tr>
            <tr>
                <td width="180"><?= __('Preferred Language'); ?>:</td>
                <td>
                    <?php $langs = Internationalization::availableLanguages(); ?>
                    <select name="lang">
                        <option value="">&mdash; <?= __('Use Browser Preference'); ?> &mdash;</option>
                        <?php
                        foreach ($langs as $l) :
                            $selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : '';
                            ?>
                            <option value=" <?= $l['code']; ?>" <?= $selected;
                            ?>><?= Internationalization::getLanguageDescription($l['code']); ?></option>
                                <?php endforeach; ?>
                    </select>
                    <span class="error">&nbsp;<?= $errors['lang']; ?></span>
                </td>
            </tr>
            <?php if ($acct->isPasswdResetEnabled()) : ?>
                <tr>
                    <td colspan=2"><div><hr><h3><?= __('Access Credentials'); ?></h3></div></td>
                </tr>
                <?php if (!isset($_SESSION['_client']['reset-token'])) : ?>
                    <tr>
                        <td width="180"><?= __('Current Password'); ?>:/td>
                        <td>
                            <input type="password" size="18" name="cpasswd" value="<?= $info['cpasswd']; ?>">
                            &nbsp;<span class="error">&nbsp;<?= $errors['cpasswd']; ?></span>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td width="180"><?= __('New Password'); ?>:</td>
                    <td>
                        <input type="password" size="18" name="passwd1" value="<?= $info['passwd1']; ?>">
                        &nbsp;<span class="error">&nbsp;<?= $errors['passwd1']; ?></span>
                    </td>
                </tr>
                <tr>
                    <td width="180"><?= __('Confirm New Password'); ?>:</td>
                    <td>
                        <input type="password" size="18" name="passwd2" value="<?= $info['passwd2']; ?>">
                        &nbsp;<span class="error">&nbsp;<?= $errors['passwd2']; ?></span>
                    </td>
                </tr>
                <?php
            endif;
        endif;
        ?> 
    </table>
    <hr>
    <p style="text-align: center;">
        <input type="submit" value="Update"/>
        <input type="reset" value="Reset"/>
        <input type="button" value="Cancel" onclick="javascript: window.location.href = 'index.php';"/>
    </p>
</form>
