<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);
?>
<h1>Sign In</h1>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
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
</form>
<br>
<p>
If this is your first time contacting us or you've lost the ticket number, please <a href="open.php">open a new ticket</a>.
</p>
