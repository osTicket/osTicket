<?php
include_once(INCLUDE_DIR.'staff/login.header.php');
defined('OSTSCPINC') or die('Invalid path');
$info = ($_POST)?Format::htmlchars($_POST):array();
?>

<div id="brickwall"></div>
<div id="loginBox">
    <div id="blur">
        <div id="background"></div>
    </div>
    <h1 id="logo"><a href="index.php">
        <span class="valign-helper"></span>
        <img src="logo.php?login" alt="osTicket :: <?php echo __('Staff Control Panel');?>" />
    </a></h1>
    <h3><?php echo Format::htmlchars($msg); ?></h3>

    <form action="email2fa.php" method="post">
        <?php csrf_token(); ?>
        <fieldset>
            <input type="text" name="code" id="code" value="<?php echo
                $info['userid']; ?>" placeholder="<?php echo __('Two Factor Authentication Passcode'); ?>"
                autocorrect="off" autocapitalize="off"/>
        </fieldset>
        <input class="submit" type="submit" name="submit" value="Validate"/>
    </form>

    <?php
    $ext_bks = array();
    foreach (StaffAuthenticationBackend::allRegistered() as $bk)
        if ($bk instanceof ExternalAuthentication)
            $ext_bks[] = $bk;

    if (count($ext_bks)) { ?>
    <div class="or">
        <hr/>
    </div><?php
        foreach ($ext_bks as $bk) { ?>
    <div class="external-auth"><?php $bk->renderExternalLink(); ?></div><?php
        }
    } ?>

        <div id="company">
            <div class="content">
                <?php echo __('Copyright'); ?> &copy; <?php echo Format::htmlchars($ost->company) ?: date('Y'); ?>
            </div>
        </div>
    </div>
    <div id="poweredBy"><?php echo __('Powered by'); ?>
        <a href="http://www.osticket.com" target="_blank">
            <img alt="osTicket" src="images/osticket-grey.png" class="osticket-logo">
        </a>
    </div>
