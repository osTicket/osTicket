<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

//See if we need to switch the mode of upgrade...e.g from ajax (default) to manual
if(($mode = $ost->get_var('m', $_GET)) &&  $mode!=$upgrader->getMode()) {
    //Set Persistent mode/
    $upgrader->setMode($mode);
    //Log warning about ajax calls - most likely culprit is AcceptPathInfo directive.
    if($mode=='manual')
        $ost->logWarning('Ajax calls are failing',
                'Make sure your server has AcceptPathInfo directive set to "ON" or get technical help');
}

$action=$upgrader->getNextAction();
?>
    <h2>Migrate to osTicket <?php echo THIS_VERSION; ?></h2>
<div id="upgrader">
    <div id="main">
            <div id="intro">
             <p>Thank you for taking the time to upgrade your osTicket intallation!</p>
             <p><strong>Please don't cancel or close the browser; any errors
             at this stage will be fatal.</strong></p>
            </div>
            <h2 id="task">Applying updates to database stream:
            <?php echo $upgrader->getCurrentStream()->name; ?></h2>
            <p>In order to upgrade to this version of osTicket, a database
            migration is required. This upgrader will automatically apply
            the database patches shipped with osTicket since your last
            upgrade.</p>
            <p>The upgrade wizard will now attempt to upgrade your database and core settings!
            Below is a summary of the database patches to be applied.
            </p>
            <?php echo $upgrader->getUpgradeSummary(); ?>
            <div id="bar">
                <form method="post" action="upgrade.php" id="upgrade">
                    <?php csrf_token(); ?>
                    <input type="hidden" name="s" value="upgrade">
                    <input type="hidden" id="mode" name="m" value="<?php echo $upgrader->getMode(); ?>">
                    <input type="hidden" name="sh" value="<?php echo $upgrader->getSchemaSignature(); ?>">
                    <input class="btn"  type="submit" name="submit" value="Upgrade Now!">
                </form>
            </div>
    </div>
    <div id="sidebar">
            <h3>Upgrade Tips</h3>
            <p>1. Be patient. The process will take a couple of minutes.</p>
            <p>2. If you experience any problems, you can always restore your files/database backup.</p>
            <p>3. We can help! Feel free to <a href="http://osticket.com/support/" target="_blank">contact us </a> for professional help.</p>
    </div>
    <div class="clear"></div>
    <div id="upgrading">
        <h4 id="action"><?php echo $action; ?></h4>
        Please wait... while we upgrade your osTicket installation!
        <div id="msg" style="font-weight: bold;padding-top:10px;">
            <?php echo sprintf("%s - Relax!", $thisstaff->getFirstName()); ?>
        </div>
    </div>
</div>
<div class="clear"></div>
