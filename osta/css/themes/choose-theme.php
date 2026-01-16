<?php
require_once "../../php/functions.php"; 
require_once( "../../../bootstrap.php");
Bootstrap::loadConfig();
Bootstrap::defineTables(TABLE_PREFIX);
Bootstrap::i18n_prep();
Bootstrap::loadCode();
Bootstrap::connect();

$ost = osTicket::start();
$cfg = $ost->getConfig();
update_config($_POST);

header('Location: ' . $_SERVER['HTTP_REFERER']);
?>