<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
//Destroy the upgrader - we're done! 
$_SESSION['ost_upgrader']=null;
?> 
<div id="upgrader">
    <div id="main">
        <h1 style="color:green;">Upgrade Completed!</h1>
        <div id="intro">
        <p>Congratulations! osTicket upgrade has completed successfully.</p>
        <p>Please refer to <a href="http://osticket.com/wiki/Release_Notes" target="_blank">Release Notes</a> for more information about changes and/or new features.</p>
        </div>
        <p>Once again, thank you for choosing osTicket.</p>
        <p>Please feel free to <a target="_blank" href="http://osticket.com/support/">let us know</a> of any other improvements and features you would like to see in osTicket, so that we may add them in the future as we continue to develop better and better versions of osTicket.</p>
        <p>We take user feedback seriously and we're dedicated to making changes based on your input.</p>
        <p>Good luck.<p>
        <p>osTicket Team.</p>
        <br>
        <p><b>PS</b>: Don't just make customers happy, make happy customers!</p>
    </div>
    <div id="sidebar">
            <h3>What's Next?</h3>
            <p><b>Post-upgrade</b>: You can now go to <a href="scp/settings.php" target="_blank">Admin Panel</a> to enable the system and explore the new features. For complete and up-to-date release notes, see <a href="http://osticket.com/wiki/Release_Notes" target="_blank">osTicket wiki</a></p>
            <p><b>Stay up to date</b>: It's important to keep your osTicket installation up to date. Get announcements, security updates and alerts delivered directly to you! 
            <a target="_blank" href="http://osticket.com/support/subscribe.php">Get in the loop</a> today and stay informed!</p>
            <p><b>Commercial support available</b>: Get guidance and hands-on expertise to address unique challenges and make sure your osTicket runs smoothly, efficiently, and securely. <a target="_blank" href="http://osticket.com/support/commercial_support.php.php">Learn More!</a></p>
   </div>
   <div class="clear"></div>
</div>
