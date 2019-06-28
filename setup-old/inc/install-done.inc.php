<?php if(!defined('SETUPINC')) die('Kwaheri!');
$url=URL;

?>
    <div id="main">
        <h1 style="color:green;"><?php echo __('Congratulations!');?></h1>
        <div id="intro">
        <p><?php echo __('Your osTicket installation has been completed successfully. Your next step is to fully configure your new support ticket system for use, but before you get to it please take a minute to cleanup.');?></p>

        <h2><?php echo __('Config file permission');?>:</h2>
        <?php echo __('Change permission of ost-config.php to remove write access as shown below.');?>
        <ul>
            <li><b><?php echo __('CLI');?></b>:<br><i>chmod 0644  include/ost-config.php</i></li>
            <li><b><?php echo __('Windows PowerShell');?></b>:<br><i>icacls include\ost-config.php /reset</i></li>
            <li><b><?php echo __('FTP');?></b>:<br><?php echo __('Using WS_FTP this would be right hand clicking on the file, selecting chmod, and then remove write access');?></li>
            <li><b><?php echo __('Cpanel');?></b>:<br><?php echo __('Click on the file, select change permission, and then remove write access.');?></li>
        </ul>
        </div>
        <p><?php echo __('Below, you\'ll find some useful links regarding your installation.');?></p>
        <table border="0" cellspacing="0" cellpadding="5" width="580" id="links">
            <tr>
                    <td width="50%">
                        <strong><?php echo __('Your osTicket URL');?>:</strong><Br>
                        <a href="<?php echo $url; ?>"><?php echo $url; ?></a>
                    </td>
                    <td width="50%">
                        <strong><?php echo __('Your Staff Control Panel');?>:</strong><Br>
                        <a href="../scp/admin.php"><?php echo $url; ?>scp</a>
                    </td>
                </tr>
                <tr>
                    <td width="50%">
                        <strong><?php echo __('osTicket Forums');?>:</strong><Br>
                        <a href="#">http://osticket.com/forum/</a>
                    </td>
                    <td width="50%">
                        <strong><?php echo __('osTicket Community Wiki');?>:</strong><Br>
                        <a href="#">http://osticket.com/wiki/</a>
                    </td>
                </tr>
            </table>
            <p><b>PS</b>: <?php echo __("Don't just make customers happy, make happy customers!");?></p>
    </div>
    <div id="sidebar">
            <h3><?php echo __("What's Next?");?></h3>
            <p><b><?php echo __('Post-Install Setup');?></b>: <?php echo sprintf(__('You can now log in to %1$s Admin Panel %2$s with the username and password you created during the install process. After a successful log in, you can proceed with post-install setup.'), '<a href="../scp/admin.php" target="_blank">','</a>'); echo sprintf(__('For complete and upto date guide see %1$s osTicket wiki %2$s'), '<a href="http://osticket.com/wiki/Post-Install_Setup_Guide" target="_blank">', '</a>');?></p>

            <p><b><?php echo __('Commercial Support Available');?></b>: <?php echo __("Don't let technical problems impact your osTicket implementation. Get guidance and hands-on expertise to address unique challenges and make sure your osTicket runs smoothly, efficiently, and securely.");?> <a target="_blank" href="http://osticket.com/support"><?php echo __('Learn More!');?></a></p>
   </div>
