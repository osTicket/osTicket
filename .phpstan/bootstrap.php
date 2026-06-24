<?php

define('TABLE_PREFIX', '%');

//Bootstrap::loadConfig();
Bootstrap::defineTables(TABLE_PREFIX);
Bootstrap::loadCode();

//--------------------------------------------------------------------------------
// Define functions from Bootstrap::i18n_prep()
//--------------------------------------------------------------------------------
function mb_str_wc($str) {}

//--------------------------------------------------------------------------------
// Define functions from Internationalization::bootstrap()
//--------------------------------------------------------------------------------
function _N($msgid, $plural, $n) {}
function _S($msgid) {}
function _NS($msgid, $plural, $count) {}
function _P($context, $msgid) {}
function _NP($context, $singular, $plural, $n) {}
function _L($msgid, $locale) {}
function _NL($msgid, $plural, $n, $locale) {}

//--------------------------------------------------------------------------------
// Include some other important files...
//--------------------------------------------------------------------------------
include_once INCLUDE_DIR . 'api.cron.php'; //For LocalCronApiController
include_once INCLUDE_DIR . 'ajax.tickets.php'; //For AjaxController(s)
include_once INCLUDE_DIR . 'class.app.php'; //For Application
include_once INCLUDE_DIR . 'class.avatar.php'; //For RandomAvatar
include_once INCLUDE_DIR . 'class.captcha.php'; //For Captcha
include_once INCLUDE_DIR . 'class.cli.php'; //For Module

//--------------------------------------------------------------------------------
// Dummy class for AuditEntry...
//--------------------------------------------------------------------------------
class AuditEntry extends VerySimpleModel {

	static $show_view_audits;

	static function getTableInfo($objectId, $export=false, $type='') {}

	static function getDescription($event, $export=false, $userType='') {}

}