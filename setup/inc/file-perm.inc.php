<?php
if(!defined('SETUPINC')) die('Kwaheri!');
?>
    <div id="main">
            <h1 style="color:#FF7700;"><?php echo __('Configuration file is not writable');?></h1>
            <div id="intro">
            <p> <?php
            echo sprintf(
                 __('osTicket installer requires ability to write to the configuration file %s'),
                 '<b style="white-space:nowrap">include/ost-config.php</b>');?>
             </p>
            </div>
            <h3><?php echo __('Solution');?>: <font color="red"><?php echo $errors['err']; ?></font></h3>
            <?php echo __('Please follow the instructions below to give read and write access to the web server user.');?>
            <ul>
                <li><b><?php echo __('CLI');?></b>:<br><i class="ltr">chmod 0666  include/ost-config.php</i></li>
                <li><b><?php echo __('Windows PowerShell');?></b>:<br><?php echo __('Add "Full Access" permission for the "Everyone" user'); ?><br>
                <i class="ltr">icacls include\ost-config.php /grant 'Everyone:F'</i></li>
                <li><b><?php echo __('FTP');?></b>:<br><?php echo __('Using WS_FTP this would be right hand clicking on the file, selecting chmod, and then giving all permissions to the file.');?></li>
                <li><b><?php echo __('Cpanel');?></b>:<br><?php echo __('Click on the file, select change permission, and then giving all permissions to the file.');?></li>
            </ul>

            <p><i><?php echo __("Don't worry! We'll remind you to take away the write access post-install");?></i>.</p>
            <div id="bar">
                <form method="post" action="install.php">
                    <input type="hidden" name="s" value="config">
                    <button class="btn"  type="submit" name="submit"><?php echo __('Done? Continue');?> &raquo;</button>
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3><?php echo __('Need Help?');?></h3>
            <p>
            <?php echo __('If you are looking for a greater level of support, we provide <u>professional installation services</u> and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs.');?> <a target="_blank" href="https://osticket.com/support"><?php echo __('Learn More!');?></a>
            </p>
    </div>
