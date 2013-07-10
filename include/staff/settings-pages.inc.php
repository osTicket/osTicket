<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
$pages = Page::getPages();
?>
<h2>Site Pages</h2>
<form action="settings.php?t=pages" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="pages" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Pages</h4>
                <em>To edit or add new pages go to <a href="pages.php">Manage > Site Pages</a></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220" class="required">Default Landing Page:</td>
            <td>
                <select name="landing_page_id">
                    <option value="">&mdash; Select Landing Page &mdash;</option>
                    <?php
                    foreach($pages as $page) {
                        if(strcasecmp($page->getType(), 'landing')) continue;
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $page->getId(),
                                ($config['landing_page_id']==$page->getId())?'selected="selected"':'',
                                $page->getName());
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['landing_page_id']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">Default Offline Page:</td>
            <td>
                <select name="offline_page_id">
                    <option value="">&mdash; Select Offline Page &mdash;</option>
                    <?php
                    foreach($pages as $page) {
                        if(strcasecmp($page->getType(), 'offline')) continue;
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $page->getId(),
                                ($config['offline_page_id']==$page->getId())?'selected="selected"':'',
                                $page->getName());
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['offline_page_id']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">Default Thank-You Page:</td>
            <td>
                <select name="thank-you_page_id">
                    <option value="">&mdash; Select Thank-You Page &mdash;</option>
                    <?php
                    foreach($pages as $page) {
                        if(strcasecmp($page->getType(), 'thank-you')) continue;
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $page->getId(),
                                ($config['thank-you_page_id']==$page->getId())?'selected="selected"':'',
                                $page->getName());
                    } ?>
                </select>&nbsp;<font class="error">*&nbsp;<?php echo $errors['thank-you_page_id']; ?></font>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
