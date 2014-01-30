<?php
if(!defined('SETUPINC')) die('Kwaheri!');

?>

    <div id="main">
            <h1>Thank You for Choosing osTicket!</h1>
            <div id="intro">
             <p>We are delighted you have chosen osTicket for your customer support ticketing system!</p>
            <p>The installer will guide you every step of the way in the installation process. You're minutes away from your awesome customer support system!</p>
            </div>
            <h2>Prerequisites.</h3>
            <p>Before we begin, we'll check your server configuration to make sure you meet the minimum requirements to install and run osTicket.</p>
            <h3>Required: <font color="red"><?php echo $errors['prereq']; ?></font></h3>
            These items are necessary in order to install and use osTicket.
            <ul class="progress">
                <li class="<?php echo $installer->check_php()?'yes':'no'; ?>">
                PHP v5.3 or greater - (<small><b><?php echo PHP_VERSION; ?></b></small>)</li>
                <li class="<?php echo $installer->check_mysql()?'yes':'no'; ?>">
                MySQLi extension for PHP - (<small><b><?php echo extension_loaded('mysqli')?'module loaded':'missing!'; ?></b></small>)</li>
            </ul>
            <h3>Recommended:</h3>
            You can use osTicket without these, but you may not be able to use all features.
            <ul class="progress">
                <li class="<?php echo extension_loaded('gd')?'yes':'no'; ?>">Gdlib extension</li>
                <li class="<?php echo extension_loaded('imap')?'yes':'no'; ?>">PHP IMAP extension. <em>Required for mail fetching</em></li>
                <li class="<?php echo extension_loaded('xml') ?'yes':'no'; ?>">PHP XML extension (for XML API)</li>
                <li class="<?php echo extension_loaded('dom') ?'yes':'no'; ?>">PHP XML-DOM extension (for HTML email processing)</li>
                <li class="<?php echo extension_loaded('json')?'yes':'no'; ?>">PHP JSON extension (faster performance)</li>
                <li class="<?php echo extension_loaded('gettext')?'yes':'no'; ?>">Gettext is used for translations (faster performance)</li>
                <li class="<?php echo extension_loaded('mbstring')?'yes':'no'; ?>">Mbstring is <b>strongly</b> recommended for all installations</li>
            </ul>
            <div id="bar">
                <form method="post" action="install.php">
                    <input type="hidden" name="s" value="prereq">
                    <input class="btn"  type="submit" name="submit" value="Continue &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3>Need Help?</h3>
            <p>
            If you are looking for a greater level of support, we provide <u>professional installation services</u> and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs. <a target="_blank" href="http://osticket.com/support/professional_services.php">Learn More!</a>
            </p>
    </div>
