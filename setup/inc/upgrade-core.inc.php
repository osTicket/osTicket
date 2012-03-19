<?php
if(!defined('SETUPINC')) die('Kwaheri!');

?>
    <div id="main">
            <h1>osTicket Upgrade</h1>
            <div id="intro">
             <p>Thank you for taking the time to upgrade your osTicket intallation!</p>
             <p>Please don't cancel or close the browser, any errors at this
              stage will be fatal.</p>
            </div>
            <h2>Base upgrade: Step 1 of 2</h2>
            <p>The upgrade wizard will now attempt to upgrade your database and core settings!</p>
            <ul>
                <li>Database enhancements</li>
                <li>New and updated features</li>
                <li>Enhance settings and security</li>
            </ul>
            <div id="bar">
                <form method="post" action="upgrade.php" id="upgrade">
                    <input type="hidden" name="s" value="upgrade">
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
        Please wait... while we upgrade your osTicket installation!
        <div id="udb"><br><b>Smile!</b></div>
    </div>
