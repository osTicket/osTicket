<?php
include_once(INCLUDE_DIR.'staff/login.header.php');
$info = ($_POST && $errors)?Format::htmlchars($_POST):array();
?>
<div id="loginBox">
    <h1 id="logo"><a href="index.php">osTicket Staff Control Panel</a></h1>
    <h3><?php echo Format::htmlchars($msg); ?></h3>
    <form action="login.php" method="post">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="scplogin">
        <fieldset>
            <input type="text" name="userid" id="name" value="<?php echo $info['userid']; ?>" placeholder="username" autocorrect="off" autocapitalize="off">
            <input type="password" name="passwd" id="pass" placeholder="password" autocorrect="off" autocapitalize="off">
        </fieldset>
        <?php if ($show_reset && $cfg->allowPasswordReset()) { ?>
        <h3 style="display:inline"><a href="pwreset.php">Forgot my password</a></h3>
        <?php } ?>
        <input class="submit" type="submit" name="submit" value="Log In">
    </form>
</div>
<div id="copyRights">Copyright &copy; <a href='http://www.osticket.com' target="_blank">osTicket.com</a></div>
</body>
</html>
