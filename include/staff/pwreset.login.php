<?php
include_once(INCLUDE_DIR.'staff/login.header.php');
defined('OSTSCPINC') or die('Invalid path');
$info = ($_POST)?Format::htmlchars($_POST):array();
?>

<div id="loginBox">
    <h1 id="logo"><a href="index.php">osTicket Staff Password Reset</a></h1>
    <h3><?php echo Format::htmlchars($msg); ?></h3>

    <form action="pwreset.php" method="post">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="newpasswd"/>
        <input type="hidden" name="token" value="<?php echo $_REQUEST['token']; ?>"/>
        <fieldset>
            <input type="text" name="userid" id="name" value="<?php echo
                $info['userid']; ?>" placeholder="username or email"
                autocorrect="off" autocapitalize="off"/>
        </fieldset>
        <input class="submit" type="submit" name="submit" value="Login"/>
    </form>
</div>

<div id="copyRights">Copyright &copy; <a href='http://www.osticket.com' target="_blank">osTicket.com</a></div>
</body>
</html>
