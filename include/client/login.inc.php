<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);

$content = Page::lookup(Page::getIdByType('banner-client'));

?>
<h1><?php echo Format::display($content->getName()); ?></h1>
<p><?php echo Format::viewableImages($content->getBody()); ?></p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div style="width:40%;display:table-cell;box-shadow: 12px 0 15px -15px rgba(0,0,0,0.4);padding-left: 2em;">
    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    <br>
    <div>
        <label for="username">Username:</label>
        <input id="username" type="text" name="luser" size="30" value="<?php echo $email; ?>">
    </div>
    <div>
        <label for="passwd">Password:</label>
        <input id="passwd" type="password" name="lpasswd" size="30" value="<?php echo $passwd; ?>"></td>
    </div>
    <p>
        <input class="btn" type="submit" value="Sign In">
<?php if ($suggest_pwreset) { ?>
        <a style="padding-top:4px;display:inline-block;" href="pwreset.php">Forgot My Password</a>
<?php } ?>
    </p>
    </div>
    <div style="display:table-cell;padding-left: 2em;">
<?php if ($cfg && $cfg->isClientRegistrationEnabled()) { ?>
        Not yet registered? <a href="account.php?do=create">Create an account</a>
<?php } ?>
    </div>
</div>
</form>
<br>
<p>
<?php if ($cfg && !$cfg->isClientLoginRequired()) { ?>
If this is your first time contacting us or you've lost the ticket number, please <a href="open.php">open a new ticket</a>.
<?php } ?>
</p>
