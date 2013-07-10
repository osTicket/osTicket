<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
?>
<div id="upgrader">
    <br>
    <h2 style="color:#FF7700;">Configuration file rename required!</h2>
    <div id="main">
            <div id="intro">
             <p>To avoid possible conflicts, please take a minute to rename configuration file as shown below.</p>
            </div>
            <h3>Solution:</h3>
            Rename file <b>include/settings.php</b> to <b>include/ost-config.php</b> and click continue below.
            <ul>
                <li><b>CLI:</b><br><i>mv include/settings.php include/ost-config.php</i></li>
                <li><b>FTP:</b><br> </li>
                <li><b>Cpanel:</b><br> </li>
            </ul>
            <p>Please refer to the <a target="_blank" href="http://osticket.com/wiki/Upgrade_and_Migration">Upgrade Guide</a> for more information.</p>
            <div id="bar">
                <form method="post" action="upgrade.php">
                    <?php csrf_token(); ?>
                    <input type="hidden" name="s" value="prereq">
                    <input class="btn" type="submit" name="submit" value="Continue &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3>Need Help?</h3>
            <p>
            If you are looking for a greater level of support, we provide <u>professional upgrade</u> and commercial support with guaranteed response times and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs. <a target="_blank" href="http://osticket.com/support/professional_services.php">Learn More!</a>
            </p>
    </div>
    <div class="clear"></div>
</div>
