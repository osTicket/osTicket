<?php

/*********************************************************************
 * install.php
 *
 * osTicket Installer.
 *
 * Peter Rotich <peter@osticket.com>
 * Copyright (c)  2006-2013 osTicket
 * http://www.osticket.com
 *
 * Released under the GNU General Public License WITHOUT ANY WARRANTY.
 * See LICENSE.TXT for details.
 *
 * vim: expandtab sw=4 ts=4 sts=4:
 **********************************************************************/

require('setup.inc.php');
require_once SETUP_INC_DIR . 'class.installer.php';
include_once 'SetupController.php';

define('OSTICKET_CONFIGFILE', '../include/ost-config.php');

//Installer instance.
$request         = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$twig            = Bootstrap::twig();
$installer       = new Installer(OSTICKET_CONFIGFILE);
$setupController = new SetupController();

// prepare variable for layout:
$lang    = Internationalization::getCurrentLanguage();
$htmlTag = '';
if ($lang && ($info = Internationalization::getLanguageInfo($lang)) && (@$info['direction'] == 'rtl')) {
    $htmlTag = 'dir="rtl" class="rtl"';
}

$flags = [];
foreach (Internationalization::availableLanguages() as $code => $info) {
    list($lang, $locale) = explode('_', $code);

    $flags[] = [
        'class' => strtolower($locale ?: $info['flag'] ?: $lang),
        'href'  => '?' . urlencode($_GET['QUERY_STRING']) . '&amp;lang=' . $code,
        'title' => Internationalization::getLanguageDescription($code)
    ];
}

$menu = [
    __('Installation Guide')    => 'http://osticket.com/wiki/Installation',
    __('Get Professional Help') => 'http://osticket.com/support'
];

$default = [
    'title'    => __('osTicket Installer'),
    'rootPath' => ROOT_PATH,
    'htmlTag'  => $htmlTag,
    'logo'     => 'logo.png',
    'tagLine'  => sprintf(__('Installing osTicket %s'), $installer->getVersionVerbose()),
    'menu'     => $menu,
    'flags'    => $flags
];

// add variables 
foreach ($default as $key => $value) {
    $twig->addGlobal($key, $value);
}

/*
 * step 1: pre-require
 * step 2: config
 * step 3: install
 * step 4: subscribe
 */
$step = 'prereq';
if (isset($_SESSION['ost_installer']) && !empty($_SESSION['ost_installer']['s'])) {
    $step = $_SESSION['ost_installer']['s'];
}

switch ($step) {
    case 'prereq':
        $response = $setupController->startAction($request, $twig, $installer);
        break;
    case 'config':
        $response = $setupController->configAction($request, $twig, $installer);
        break;
    case 'install':
        $response = $setupController->installAction($request, $twig, $installer);
        break;
    case 'subscribe':
        $response = $setupController->subscribeAction($request, $twig, $installer);
        break;
    case 'done':
        $response = $setupController->doneAction($twig, $installer);
        break;
    default:
        throw new \LogicException(sprintf('Step %s is not a valid step', $step));
        break;
}

echo $response->send();
