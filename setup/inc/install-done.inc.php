<?php if(!defined('SETUPINC')) die('Kwaheri!');
$url=URL;

?>    
    <div id="main">
        <h1 style="color:green;">Congratulations!</h1>
        <div id="intro">
        <p>Your osTicket installation has been completed successfully. Your next step is to fully configure your new support ticket system for use, but before you get to it please take a minute to cleanup.</p>
        
        <h2>Config file permission:</h2>
        Change permission of ost-config.php to remove write access as shown below.
        <ul>
            <li><b>CLI</b>:<br><i>chmod 0644  include/ost-config.php</i></li>
            <li><b>FTP</b>:<br>Using WS_FTP this would be right hand clicking on the file, selecting chmod, and then remove write access</li>
            <li><b>Cpanel</b>:<br>Click on the file, select change permission, and then remove write access.</li>
        </ul>
        </div>
        <p>Below, you'll find some useful links regarding your installation.</p>
        <table border="0" cellspacing="0" cellpadding="5" width="580" id="links">
            <tr>
                    <td width="50%">
                        <strong>Your osTicket URL:</strong><Br>
                        <a href="<?php echo $url; ?>"><?php echo $url; ?></a>
                    </td>
                    <td width="50%">
                        <strong>Your Staff Control Panel:</strong><Br>
                        <a href="../scp/admin.php"><?php echo $url; ?>scp</a>
                    </td>
                </tr>
                <tr>
                    <td width="50%">
                        <strong>osTicket Forums:</strong><Br>
                        <a href="#">http://osticket.com/forum/</a>
                    </td>
                    <td width="50%">
                        <strong>osTicket Community Wiki:</strong><Br>
                        <a href="#">http://osticket.com/wiki/</a>
                    </td>
                </tr>
            </table>
            <p><b>PS</b>: Don't just make customers happy, make happy customers!</p>
    </div>
    <div id="sidebar">
            <h3>What's Next?</h3>
            <p><b>Post-Install Setup</b>: You can now log in to <a href="../scp/admin.php" target="_blank">Admin Panel</a> with the username and password you created during the install process. After a successful log in, you can proceed with post-install setup. For complete and upto date guide see <a href="http://osticket.com/wiki/Post-Install_Setup_Guide" target="_blank">osTicket wiki</a></p>

            <p><b>Commercial Support Available</b>: Don't let technical problems impact your osTicket implementation. Get guidance and hands-on expertise to address unique challenges and make sure your osTicket runs smoothly, efficiently, and securely. <a target="_blank" href="http://osticket.com/commercial-support">Learn More!</a></p>
   </div>
