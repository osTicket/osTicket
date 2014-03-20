<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);
?>
<h1>Sign In</h1>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div style="width:40%;display:table-cell">
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
    </p>
    </div>
<?php if ($cfg && $cfg->isClientRegistrationEnabled()) { ?>
    <div style="display:table-cell;box-shadow: -9px 0 15px -12px rgba(0,0,0,0.3);padding-left: 2em;">
        Not yet registered? <a href="account.php?do=create">Create an account</a>
    </div>
<?php } ?>
</div>
</form>
<br>
<p>
<?php if ($cfg && !$cfg->isClientLoginRequired()) { ?>
If this is your first time contacting us or you've lost the ticket number, please <a href="open.php">open a new ticket</a>.
<?php } ?>
</p>
