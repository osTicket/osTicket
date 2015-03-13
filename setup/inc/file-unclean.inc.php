<?php
if(!defined('SETUPINC')) die('Kwaheri!');
?>
    <div id="main">
            <h1 style="color:#FF7700;"><?php echo __('osTicket is already installed?');?></h1>
            <div id="intro">
             <p><?php echo sprintf(__('Configuration file already changed - which could mean osTicket is already installed or the config file is currupted. If you are trying to upgrade osTicket, then go to %s Admin Panel %s.'), '<a href="../scp/admin.php" >', '</a>');?></p>

             <p><?php echo __('If you believe this is in error, please try replacing the config file with a unchanged template copy and try again or get technical help.');?></p>
             <p><?php echo sprintf(__('Refer to the %s Installation Guide %s on the wiki for more information.'), '<a target="_blank" href="http://osticket.com/wiki/Installation">', '</a>');?></p>
            </div>
    </div>
    <div id="sidebar">
            <h3><?php echo __('Need Help?');?></h3>
            <p>
            <?php echo __('We provide <u>professional installation services</u> and commercial support with guaranteed response times, and access to the core development team.');?> <a target="_blank" href="http://osticket.com/support"><?php echo __('Learn More!');?></a>
            </p>
    </div>
