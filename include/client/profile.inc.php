<div class="subnav">

    <div class="float-left subnavtitle">
                          
    Manage Your Profile Information                        
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
   &nbsp;
      </div>   
   <div class="clearfix"></div> 
</div>

<div class="card-box">
<div class="row">

<form action="profile.php" method="post">
  <?php csrf_token(); ?>
<table class="padded">
<?php
foreach ($user->getForms() as $f) {
    $f->render(false);
}
if ($acct = $thisclient->getAccount()) {
    $info=$acct->getInfo();
    $info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<tr>
    <td colspan="2">
        <div><hr><h3><?php echo __('Preferences'); ?></h3>
        </div>
    </td>
</tr>
    <tr>
        <td class="text-nowrap">
            <?php echo __('Time Zone');?>:
        </td>
        <td>
            <?php
            $TZ_NAME = 'timezone';
            $TZ_TIMEZONE = $info['timezone'];
            include INCLUDE_DIR.'staff/templates/timezone.tmpl.php'; ?>
            <div class="error"><?php echo $errors['timezone']; ?></div>
        </td>
    </tr>

<?php 
      if ($acct->isPasswdResetEnabled()) { ?>
<tr>
    <td colspan="2">
        <div><hr><h3><?php echo __('Access Credentials'); ?></h3></div>
    </td>
</tr>
<?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
<tr>
    <td class="text-nowrap">
        <?php echo __('Current Password'); ?>:
    </td>
    <td>
        <input class="form-control" type="password" size="18" name="cpasswd" value="<?php echo $info['cpasswd']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['cpasswd']; ?></span>
    </td>
</tr>
<?php } ?>
<tr>
    <td class="text-nowrap">
        <?php echo __('New Password'); ?>:
    </td>
    <td>
        <input class="form-control" type="password" size="18" name="passwd1" value="<?php echo $info['passwd1']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
    </td>
</tr>
<tr>
    <td class="text-nowrap">
        <?php echo __('Confirm New Password'); ?>:&nbsp;
    </td>
    <td>
        <input class="form-control" type="password" size="18" name="passwd2" value="<?php echo $info['passwd2']; ?>">
        &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
    </td>
</tr>
<?php } ?>
<?php } ?>
</table>
<hr>
<p style="text-align: center;">
    <input type="submit" class="btn btn-success" value="Update"/>
    <input type="reset" class="btn btn-warning" value="Reset"/>
    <input type="button" class="btn btn-default" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
</div>
</div>