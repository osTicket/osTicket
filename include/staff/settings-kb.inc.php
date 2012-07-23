<form action="settings.php?t=kb" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="kb" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Knowledgebase Settings</h4>
                <em>Disabling knowledgebase disables user's knowledgebase interface.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180">Knowledgebase Status:</td>
            <td>
              <input type="checkbox" name="enable_kb" value="1" <?php echo $config['enable_kb']?'checked="checked"':''; ?>>
              Enable Knowledgebase&nbsp;<em>(Client Interface)</em>
              &nbsp;<font class="error">&nbsp;<?php echo $errors['enable_kb']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="180">Premade Responses:</td>
            <td>
                <input type="checkbox" name="enable_premade" value="1" <?php echo $config['enable_premade']?'checked="checked"':''; ?> >
                Enable premade/canned responses&nbsp;<em>(Available on ticket reply)</em>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['enable_premade']; ?></font>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:210px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
</p>
</form>
