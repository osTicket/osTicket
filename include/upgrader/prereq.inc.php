<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
?>
<h2>osTicket Upgrader</h2>
<div id="upgrader">
    
    <div id="main">
            <div id="intro">
             <p>Thank you for being a loyal osTicket user!</p>
             <p>The upgrade wizard will guide you every step of the way through the upgrade process. While we try to ensure that the upgrade process is straightforward and painless, we can't guarantee this will be the case for every user.</p>
            </div>
            <h2>Getting ready!</h2>
            <p>Before we begin, we'll check your server configuration to make sure you meet the minimum requirements to run the latest version of osTicket.</p>
            <h3>Prerequisites: <font color="red"><?php echo $errors['prereq']; ?></font></h3>
            These items are necessary in order to run the latest version of osTicket.
            <ul class="progress">
                <li class="<?php echo $upgrader->check_php()?'yes':'no'; ?>">
                PHP v5.3 or greater - (<small><b><?php echo PHP_VERSION; ?></b></small>)</li>
                <li class="<?php echo $upgrader->check_mysql()?'yes':'no'; ?>">
                MySQLi extension for PHP - (<small><b><?php echo extension_loaded('mysqli')?'module loaded':'missing!'; ?></b></small>)</li>
                <li class="<?php echo $upgrader->check_mysql_version()?'yes':'no'; ?>">
                MySQL v5.0 or greater - (<small><b><?php echo db_version(); ?></b></small>)</li>
            </ul>
            <h3>Highly Recommended:</h3>
            We highly recommend that you follow the steps below.
            <ul>
                <li>Back up the current database if you haven't done so already.</li>
                <li>Be patient. The upgrade process will take a couple of seconds.</li>
            </ul>
            <div id="bar">
                <form method="post" action="upgrade.php" id="prereq">
                    <?php csrf_token(); ?>
                    <input type="hidden" name="s" value="prereq">
                    <input class="btn"  type="submit" name="submit" value="Start Upgrade Now &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3>Upgrade Tips</h3>
            <p>1. Remember to back up your osTicket database</p>
            <p>2. Refer to the <a href="http://osticket.com/wiki/Upgrade_and_Migration" target="_blank">Upgrade Guide</a> for the latest tips</a>
            <p>3. If you experience any problems, you can always restore your files/dabase backup.</p>
            <p>4. We can help! Feel free to <a href="http://osticket.com/support/" target="_blank">contact us </a> for professional help.</p>

    </div>
    <div class="clear"></div>
</div>
    
<div id="overlay"></div>
<div id="loading">
    <h4>Doing stuff!</h4>
    Please wait... while we upgrade your osTicket installation!
    <div id="msg"></div>
</div>
