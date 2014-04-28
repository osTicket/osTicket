<?php
if(!defined('SETUPINC')) die('Kwaheri!');
?>
    <div id="main">
            <h1 style="color:#FF7700;">Configuration file missing!</h1>
            <div id="intro">
             <p>osTicket installer requires ability to write to the configuration file, <b>include/ost-config.php</b>. A template copy is located in the include directory (<b>include/ost-sampleconfig.php</b>).
             </p>
            </div>
            <h3>Solution: <font color="red"><?php echo $errors['err']; ?></font></h3>
            Rename the sample file <b>include/ost-sampleconfig.php</b> to <b>ost-config.php</b> and click continue below.
            <ul>
                <li><b>CLI:</b><br><i>cp include/ost-sampleconfig.php include/ost-config.php</i></li>
                <li><b>FTP:</b><br> </li>
                <li><b>Cpanel:</b><br> </li>
            </ul>
            <p>If sample config file is missing - please make sure you uploaded all files in 'upload' folder or refer to the <a target="_blank" href="http://osticket.com/wiki/Installation">Installation Guide</a></p>
            <div id="bar">
                <form method="post" action="install.php">
                    <input type="hidden" name="s" value="config">
                    <input class="btn" type="submit" name="submit" value="Continue &raquo;">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3>Need Help?</h3>
            <p>
            If you are looking for a greater level of support, we provide <u>professional installation services</u> and commercial support with guaranteed response times, and access to the core development team. We can also help customize osTicket or even add new features to the system to meet your unique needs. <a target="_blank" href="http://osticket.com/support">Learn More!</a>
            </p>
    </div>
