<?php
@chdir(dirname(__FILE__).'/'); //Change dir.
require('api.inc.php');

if (!osTicket::is_cli())
    die(__('cron.php only supports local cron calls - use http -> api/tasks/cron'));

require_once(INCLUDE_DIR.'class.ticket.php');
Ticket::triggerOverdue();
?>

