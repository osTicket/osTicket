<?php
if(!($maxfileuploads=ini_get('max_file_uploads')))
    $maxfileuploads=DEFAULT_MAX_FILE_UPLOADS;

?>
<form action="settings.php?t=attachments" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="attachments" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Attachments Settings</h4>
                <em> Before enabling attachments make sure you understand PHP file upload settings and security issues related to file upload.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180">Allow Attachments:</td>
            <td>
              <input type="checkbox" name="allow_attachments" <?php echo $config['allow_attachments']?'checked="checked"':''; ?>><b>Allow Attachments</b>
                &nbsp; <em>(Global Setting)</em>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['allow_attachments']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Emailed Attachments:</td>
            <td>
                <input type="checkbox" name="allow_email_attachments" <?php echo $config['allow_email_attachments']?'checked="checked"':''; ?>> Accept emailed files
                    &nbsp;<font class="error">&nbsp;<?php echo $errors['allow_email_attachments']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Online Attachments:</td>
            <td>
                <input type="checkbox" name="allow_online_attachments" <?php echo $config['allow_online_attachments']?'checked="checked"':''; ?> >
                    Allow web upload &nbsp;&nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="allow_online_attachments_onlogin" <?php echo $config['allow_online_attachments_onlogin'] ?'checked="checked"':''; ?> >
                    Limit to authenticated users only. <em>(User must be logged in to upload files)</em>
                    <font class="error">&nbsp;<?php echo $errors['allow_online_attachments']; ?></font>
            </td>
        </tr>
        <tr>
            <td>Max. User File Uploads:</td>
            <td>
                <select name="max_user_file_uploads">
                    <?php
                    for($i = 1; $i <=$maxfileuploads; $i++) {
                        ?>
                        <option <?php echo $config['max_user_file_uploads']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>">
                            <?php echo $i; ?>&nbsp;<?php echo ($i>1)?'files':'file'; ?></option>
                        <?php
                    } ?>
                </select>
                <em>(Number of files the user is allowed to upload simultaneously)</em>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['max_user_file_uploads']; ?></font>
            </td>
        </tr>
        <tr>
            <td>Max. Staff File Uploads:</td>
            <td>
                <select name="max_staff_file_uploads">
                    <?php
                    for($i = 1; $i <=$maxfileuploads; $i++) {
                        ?>
                        <option <?php echo $config['max_staff_file_uploads']==$i?'selected="selected"':''; ?> value="<?php echo $i; ?>">
                            <?php echo $i; ?>&nbsp;<?php echo ($i>1)?'files':'file'; ?></option>
                        <?php
                    } ?>
                </select>
                <em>(Number of files the staff is allowed to upload simultaneously)</em>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['max_staff_file_uploads']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Maximum File Size:</td>
            <td>
                <input type="text" name="max_file_size" value="<?php echo $config['max_file_size']; ?>"> in bytes.
                    <em>(Max <?php echo Format::file_size(ini_get('upload_max_filesize')); ?>)</em>
                    <font class="error">&nbsp;<?php echo $errors['max_file_size']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Ticket Response Files:</td>
            <td>
                <input type="checkbox" name="email_attachments" <?php echo $config['email_attachments']?'checked="checked"':''; ?> >Email attachments to the user
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Accepted File Types</strong>: Limit the type of files users are allowed to upload.
                <font class="error">&nbsp;<?php echo $errors['allowed_filetypes']; ?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan="2">
                <em>Enter allowed file extensions separated by a comma. e.g .doc, .pdf. To accept all files enter wildcard <b><i>.*</i></b>&nbsp;i.e dotStar (NOT Recommended).</em><br>
                <textarea name="allowed_filetypes" cols="21" rows="4" style="width: 65%;" wrap="hard" ><?php echo $config['allowed_filetypes']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:210px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
