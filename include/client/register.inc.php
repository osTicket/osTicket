<?php
$info = $_POST;
if (!isset($info['timezone_id']))
    $info += array(
        'timezone_id' => $cfg->getDefaultTimezoneId(),
        'dst' => $cfg->observeDaylightSaving(),
        'backend' => null,
    );
if (isset($user) && $user instanceof ClientCreateRequest) {
    $bk = $user->getBackend();
    $info = array_merge($info, array(
        'backend' => $bk::$id,
        'username' => $user->getUsername(),
    ));
}
$info = Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<h1>Account Registration</h1>
<p>
Use the forms below to create or update the information we have on file for
your account
</p>
<form action="account.php" method="post">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" value="<?php echo Format::htmlchars($_REQUEST['do']
    ?: ($info['backend'] ? 'import' :'create')); ?>" />
<table width="800" class="padded">
<tbody>
<?php
    $cf = $user_form ?: UserForm::getInstance();
    $cf->render(false);
?>
<tr>
    <td colspan="2">
        <div><hr><h3>Preferences</h3>
        </div>
    </td>
</tr>
    <td>Time Zone:</td>
    <td>
        <select name="timezone_id" id="timezone_id">
            <?php
            $sql='SELECT id, offset,timezone FROM '.TIMEZONE_TABLE.' ORDER BY id';
            if(($res=db_query($sql)) && db_num_rows($res)){
                while(list($id,$offset, $tz)=db_fetch_row($res)){
                    $sel=($info['timezone_id']==$id)?'selected="selected"':'';
                    echo sprintf('<option value="%d" %s>GMT %s - %s</option>',$id,$sel,$offset,$tz);
                }
            }
            ?>
        </select>
        &nbsp;<span class="error"><?php echo $errors['timezone_id']; ?></span>
    </td>
</tr>
<tr>
    <td width="180">
       Daylight Saving:
    </td>
    <td>
        <input type="checkbox" name="dst" value="1" <?php echo $info['dst']?'checked="checked"':''; ?>>
        Observe daylight saving
        <em>(Current Time: <strong><?php echo Format::date($cfg->getDateTimeFormat(),Misc::gmtime(),$info['tz_offset'],$info['dst']); ?></strong>)</em>
    </td>
</tr>
<tr>
    <td colspan=2">
        <div><hr><h3>Access Credentials</h3></div>
    </td>
</tr>
<?php if ($info['backend']) { ?>
<tr>
    <td width="180">
        Login With:
    </td>
    <td>
        <input type="hidden" name="backend" value="<?php echo $info['backend']; ?>"/>
        <input type="hidden" name="username" value="<?php echo $info['username']; ?>"/>
<?php foreach (UserAuthenticationBackend::allRegistered() as $bk) {
    if ($bk::$id == $info['backend']) {
        echo $bk::$name;
        break;
    }
} ?>
    </td>
</tr>
<?php } else { ?>
<tr>
    <td width="180">
        Create a Password:
    </td>
    <td>
        <input type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
    </td>
</tr>
<tr>
    <td width="180">
        Confirm New Password:
    </td>
    <td>
        <input type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
    </td>
</tr>
<?php } ?>
</tbody>
</table>
<hr>
<p style="text-align: center;">
    <input type="submit" value="Register"/>
    <input type="button" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>

