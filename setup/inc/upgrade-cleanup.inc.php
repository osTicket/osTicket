<?php
if(!defined('SETUPINC')) die('Kwaheri!');

?>
    <div id="main">
            <h1>osTicket Upgrade</h1>
            <div id="intro">
             <p>We're almost done! Please don't cancel or close the browser, any errors at this stage will be fatal.</p>
            </div>
            <h2>Cleanup: Step 2 of 2</h2>
            <p>The upgrade wizard will now attempt to do post upgrade cleanup. It might take a while dending on the size of your database. </p>
            <ul>
                <li>Setting Changes</li>
                <li>Attachment Migration</li>
                <li>Database Optimization</li>
            </ul>
            <div id="bar">
                <form method="post" action="upgrade.php" id="cleanup">
                    <input type="hidden" name="s" value="cleanup">
                    <input class="btn"  type="submit" name="submit" value="Do It Now!">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3>Upgrade Tips</h3>
            <p>1. Be patient the process will take a couple of seconds.</p>
            <p>2. If you experience any problems, you can always restore your files/dabase backup.</p>
            <p>3. We can help, feel free to <a href="http://osticket.com/support/" target="_blank">contact us </a> for professional help.</p>
    </div>

    <div id="overlay"></div>
    <div id="loading">
        <h4>Doing serious stuff!</h4>
        <br>
        Please wait... while we do post-upgrade cleanup!
        <br><br>
        <div id="msg" style="font-weight: bold;"></div>
    </div>
