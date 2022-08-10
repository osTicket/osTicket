<?php
if(!defined('SETUPINC')) die('Kwaheri!');
?>
    <div id="main">
            <h1 style="color:#FF7700;"><?php echo __('Configuration file missing!');?></h1>
            <div id="intro">
             <p><?php echo __('osTicket installer requires ability to write to the configuration file, <b>include/ost-config.php</b>. A template copy is located in the include directory (<b>include/ost-sampleconfig.php</b>).');?>
             </p>
            </div>
            <h3><?php echo __('Solution');?>: <font color="red"><?php echo $errors['err']; ?></font></h3>
            <?php echo __('Rename the sample file <b>include/ost-sampleconfig.php</b> to <b>ost-config.php</b> and click continue below.');?>
            <ul>
                <li><b><?php echo __('CLI');?>:</b><br><i>cp include/ost-sampleconfig.php include/ost-config.php</i></li>
                <li><b><?php echo __('Windows PowerShell');?>:</b><br><i>Copy-Item -Path include\ost-sampleconfig.php -Destination include\ost-config.php</i></li>
                <li><b><?php echo __('FTP');?>:</b><br> </li>
                <li><b><?php echo __('Cpanel');?>:</b><br> </li>
            </ul>
            <p><?php echo sprintf(__('If sample config file is missing - please make sure you uploaded all files in \'upload\' folder or refer to the %1$s Installation Guide %2$s'),'<a target="_blank" href="https://docs.osticket.com/en/latest/Getting%20Started/Installation.html">','</a>');?></p>
            <div id="bar">
                <form method="post" action="install.php">
                    <input type="hidden" name="s" value="config">
                    <input class="btn" type="submit" name="submit" value="<?php echo __('Continue');?> &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3><?php echo __('Need Help?');?></h3>
            <p>
            <?php echo __('If you are looking for a greater level of support, we provide <u>professional installation services</u> and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs.');?> <a target="_blank" href="https://osticket.com/support"><?php echo __('Learn More!');?></a>
            </p>
    </div>
