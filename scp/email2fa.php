<?php
require_once('../main.inc.php');
if(!defined('INCLUDE_DIR')) die('Fatal Error. Kwaheri!');

// Bootstrap gettext translations. Since no one is yet logged in, use the
// system or browser default
TextDomain::configureForUser();

require_once(INCLUDE_DIR.'class.staff.php');
require_once(INCLUDE_DIR.'class.csrf.php');

$tpl = INCLUDE_DIR.'staff/email2fa.php';
$msg = $_SESSION['_staff']['auth']['msg']
    ?: __('Enter the authentication code sent to your email below.');

Email2FA::email2faLogin($_POST);

define("OSTSCPINC",TRUE); //Make includes happy!
include_once($tpl);
