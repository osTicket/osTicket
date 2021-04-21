<?php

// Validate that the plugin.php file works, also load
// Stuff that we need to build plugins from the subdirectory 
// of an osTicket install:

$plugin = include_once(dirname(__DIR__) . '/plugin.php');

define('INCLUDE_DIR', dirname(dirname(__DIR__)) . '/osticket/include/');
define('PLUGIN_TABLE', 'ost_plugin');

// We need database functions to fake with
function db_input() {
    
}

function db_query() {
    return array('ht' => array());
}

function db_fetch_array() { // obviously, tweak if you compile phars:
    return array('ht' => array(), 'install_path' => __DIR__, 'isphar' => FALSE);
}

assert(isset($plugin['plugin']) == TRUE);

$plugin_file  = explode(':', $plugin['plugin']); 
$file = $plugin_file[0];
$class = $plugin_file[1];

assert(file_exists($file));
include_once($file);

assert(class_exists($class));
$attachment_plugin = new $class(1);
