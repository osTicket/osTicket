<html>
    <?php require_once('setup.inc.php'); ?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    </head>
    <body>
        <div id="t1">
            <b><?= __('Helpdesk Name'); ?></b>
            <p><?= __('The name of your support system e.g [Company Name] Support'); ?></p>
        </div>
        <div id="t2">
            <b><?= __('Default System Email'); ?></b>
            <p><?= __('Default email address e.g support@yourcompany.com - you can add more later!'); ?></p>
        </div>
        <div id="t3">
            <b><?= __('First Name'); ?></b>
            <p><?= __("Admin's first name"); ?></p>
        </div>
        <div id="t4">
            <b><?= __('Last Name'); ?></b>
            <p><?= __("Admin's last name"); ?></p>
        </div>
        <div id="t5">
            <b><?= __('Email Address'); ?></b>
            <p><?= __("Admin's personal email address. Must be different from system's default email."); ?></p>
        </div>
        <div id="t6">
            <b><?= __('Username'); ?></b>
            <p><?= __("Admin's login name. Must be at least three (3) characters."); ?></p>
        </div>
        <div id="t7">
            <b><?= __('Password'); ?></b>
            <p><?= __("Admin's password.  Must be five (5) characters or more."); ?></p>
        </div>
        <div id="t8">
            <b><?= __('Confirm Password'); ?></b>
            <p><?= __("Retype admin's password. Must match."); ?></p>
        </div>
        <div id="t9">
            <b><?= __('MySQL Table Prefix.'); ?></b>
            <p><?= __('osTicket requires table prefix in order to avoid possible table conflicts in a shared database.'); ?></p>
        </div>
        <div id="t10">
            <b><?= __('MySQL Hostname'); ?></b>
            <p><?= __("Most hosts use 'localhost' for local database hostname. Check with your host if localhost fails. Default port set in php.ini is assumed."); ?></p>
        </div>
        <div id="t11">
            <b><?= __('MySQL Database'); ?></b>
            <p><?= __('Name of the database osTicket will use.'); ?></p>
        </div>
        <div id="t12">
            <b><?= __('MySQL Username'); ?></b> 
            <p><?= __('The MySQL user must have full rights to the database.'); ?></p>
        </div>
        <div id="t13">
            <b><?= __('MySQL Password'); ?></b>
            <p><?= __('MySQL password associated with above user.'); ?></p>
        </div>
    </body>
</html>