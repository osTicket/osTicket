<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
?>
<h2><?php echo __('osTicket Upgrader');?></h2>
<div id="upgrader">

    <div id="main">
            <div id="intro">
             <p><?php echo __('Thank you for being a loyal osTicket user!');?></p>
             <p><?php echo __("The upgrade wizard will guide you every step of the way in the upgrade process. While we try to ensure that the upgrade process is straightforward and painless, we can't guarantee it will be the case for every user.");?></p>
            </div>
            <h2><?php echo __('Getting ready!');?></h2>
            <p><?php echo __("Before we begin, we'll check your server configuration to make sure you meet the minimum requirements to run the latest version of osTicket.");?></p>
            <h3><?php echo __('Prerequisites');?>: <font color="red"><?php echo $errors['prereq']; ?></font></h3>
            <?php echo __('These items are necessary in order to run the latest version of osTicket.');?>
            <ul class="progress">
                <li class="<?php echo $upgrader->check_php()?'yes':'no'; ?>">
                <?php echo sprintf(__('%s or later'), 'PHP v5.3'); ?> - (<small><b><?php echo PHP_VERSION; ?></b></small>)</li>
                <li class="<?php echo $upgrader->check_mysql()?'yes':'no'; ?>">
                <?php echo __('MySQLi extension for PHP'); ?>- (<small><b><?php
                    echo extension_loaded('mysqli')?__('module loaded'):__('missing!'); ?></b></small>)</li>
                <li class="<?php echo $upgrader->check_mysql_version()?'yes':'no'; ?>">
                <?php echo sprintf(__('%s or later'), 'MySQL v5.0'); ?> - (<small><b><?php echo db_version(); ?></b></small>)</li>
            </ul>
            <h3><?php echo __('Higly Recommended');?>:</h3>
            <?php echo __('We highly recommend that you follow the steps below.');?>
            <ul>
                <li><?php echo __("Back up the current database if you haven't done so already."); ?></li>
                <li><?php echo __("Be patient. The upgrade process will take a couple of seconds."); ?></li>
            </ul>
            <div id="bar">
                <form method="post" action="upgrade.php" id="prereq">
                    <?php csrf_token(); ?>
                    <input type="hidden" name="s" value="prereq">
                    <input class="btn"  type="submit" name="submit" value="<?php echo __('Start Upgrade Now');?> &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3><?php echo __('Upgrade Tips');?></h3>
            <p>1. <?php echo __('Remember to back up your osTicket database');?></p>
            <p>2. <?php echo sprintf(__('Refer to %1$s Upgrade Guide %2$s for the latest tips'), '<a href="http://osticket.com/wiki/Upgrade_and_Migration" target="_blank">', '</a>');?></p>
            <p>3. <?php echo __('If you experience any problems, you can always restore your files/database backup.');?></p>
            <p>4. <?php echo sprintf(__('We can help, feel free to %1$s contact us %2$s for professional help.'), '<a href="http://osticket.com/support/" target="_blank">', '</a>');?></p>

    </div>
    <div class="clear"></div>
</div>

<div id="overlay"></div>
<div id="loading">
    <h4><?php echo __('Doing stuff!');?></h4>
    <?php echo __('Please wait... while we upgrade your osTicket installation!');?>
    <div id="msg"></div>
</div>
