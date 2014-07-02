<?php
include_once(INCLUDE_DIR.'staff/login.header.php');
defined('OSTSCPINC') or die('Invalid path');
$info = ($_POST && $errors)?Format::htmlchars($_POST):array();
?>

<div id="loginBox">
    <h1 id="logo"><a href="index.php">osTicket <?php echo __('Agent Password Reset'); ?></a></h1>
    <h3><?php echo __('A confirmation email has been sent'); ?></h3>
    <h3 style="color:black;"><em><?php echo __(
    'A password reset email was sent to the email on file for your account.  Follow the link in the email to reset your password.'
    ); ?>
    </em></h3>

    <form action="index.php" method="get">
        <input class="submit" type="submit" name="submit" value="Login"/>
    </form>
</div>

<div id="copyRights">Copyright &copy; <a href='http://www.osticket.com' target="_blank">osTicket.com</a></div>
</body>
</html>
