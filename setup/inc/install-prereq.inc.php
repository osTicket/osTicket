<?php
if(!defined('SETUPINC')) die('Kwaheri!');

?>

    <div id="main">
            <h2><?php echo __('Thank You for Choosing osTicket!');?></h2>
            <div id="intro">
             <p><?php echo __('We are delighted you have chosen osTicket for your customer support ticketing system!');?></p>
            <p><?php echo __("The installer will guide you every step of the way in the installation process. You're minutes away from your awesome customer support system!");?></p>
            </div>
            <h2><?php echo __('Prerequisites');?>.</h3>
            <p><?php echo __("Before we begin, we'll check your server configuration to make sure you meet the minimum requirements to install and run osTicket.");?></p>
            <h3><?php echo __('Required');?>: <font color="red"><?php echo $errors['prereq']; ?></font></h3>
            <?php echo __('These items are necessary in order to install and use osTicket.');?>
            <ul class="progress">
                <li class="<?php echo $installer->check_php()?'yes':'no'; ?>">
                <?php echo sprintf(__('%s or greater'), '<span class="ltr">PHP v5.3</span>');?> &mdash; <small class="ltr">(<b><?php echo PHP_VERSION; ?></b>)</small></li>
                <li class="<?php echo $installer->check_mysql()?'yes':'no'; ?>">
                <?php echo __('MySQLi extension for PHP');?> &mdash; <small><b><?php
                    echo extension_loaded('mysqli')?__('module loaded'):__('missing!'); ?></b></small></li>
            </ul>
            <h3><?php echo __('Recommended');?>:</h3>
            <?php echo __('You can use osTicket without these, but you may not be able to use all features.');?>
            <ul class="progress">
                <li class="<?php echo extension_loaded('gd')?'yes':'no'; ?>">Gdlib <?php echo __('extension');?></li>
                <li class="<?php echo extension_loaded('imap')?'yes':'no'; ?>">PHP IMAP <?php echo __('extension');?> &mdash; <em><?php
                    echo __('Required for mail fetching');?></em></li>
                <li class="<?php echo extension_loaded('xml') ?'yes':'no'; ?>">PHP XML <?php echo __('extension');?> <?php
                    echo __('(for XML API)');?></li>
                <li class="<?php echo extension_loaded('dom') ?'yes':'no'; ?>">PHP XML-DOM <?php echo __('extension');?> <?php
                    echo __('(for HTML email processing)');?></li>
                <li class="<?php echo extension_loaded('json')?'yes':'no'; ?>">PHP JSON <?php echo __('extension');?> <?php
                    echo __('(faster performance)');?></li>
                <li class="<?php echo extension_loaded('mbstring')?'yes':'no'; ?>">Mbstring <?php echo __('extension');?> &mdash; <?php
                    echo __('recommended for all installations');?></li>
                <li class="<?php echo extension_loaded('phar')?'yes':'no'; ?>">Phar <?php echo __('extension');?> &mdash; <?php
                    echo __('recommended for plugins and language packs');?></li>
            </ul>
            <div id="bar">
                <form method="post" action="install.php">
                    <input type="hidden" name="s" value="prereq">
                    <input class="btn"  type="submit" name="submit" value="<?php echo __('Continue');?> &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3><?php echo __('Need Help?');?></h3>
            <p>
            <?php echo __('If you are looking for a greater level of support, we provide <u>professional installation services</u> and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs.');?> <a target="_blank" href="http://osticket.com/support"><?php echo __('Learn More!');?></a>
            </p>
    </div>
