<?php
if(!defined('SETUPINC')) die('Kwaheri!');
$msg = $_SESSION['ost_upgrader']['msg'];
?>    
<div id="main">
    <h1>Attachments Migration</h1>
    <p>We're almost done! We're now migrating attachments to the database, it might take a while dending on the number of files in your database.<p>
    <p style="color:#FF7700;font-weight:bold;">We have to migrate files in batches for technical reasons.</p>
    <p>Please don't cancel or close the browser.</p>
            
    <div id="bar">
        <form method="post" action="upgrade.php" id="attachments">
            <input type="hidden" name="s" value="cleanup">
            <input class="btn"  type="submit" name="submit" value="Next Batch">
        </form>
        
    </div>
</div>
<div id="sidebar">
    <h3>Upgrade Tips</h3>
    <p>1. Be patient the process will take a few minutes.</p>
    <p>2. If you experience any problems, you can always restore your files/dabase backup.</p>
    <p>3. We can help, feel free to <a href="http://osticket.com/support/" target="_blank">contact us </a> for professional help.</p>    
</div>    
<div id="overlay"></div>
    <div id="loading">
        <h4>Moving attachments</h4>
        <br>
        Please wait... while we migrate attachments!
        <br><br>
        <div id="msg" style="font-weight: bold;"><?php echo Format::htmlchars($msg); ?></div>
    </div>
