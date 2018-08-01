<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.plugin.php");

if($_REQUEST['id'] && !($plugin=Plugin::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
        __('plugin'));

if($_POST) {
    switch(strtolower($_POST['do'])) {
    case 'update':
        if ($plugin) {
            $plugin->getConfig()->commit($errors);
        }
        break;
    case 'mass_process':
        if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
            $errors['err'] = sprintf(__('You must select at least %s.'),
                __('one plugin'));
        } else {
            $count = count($_POST['ids']);
            switch(strtolower($_POST['a'])) {
            case 'enable':
                foreach ($_POST['ids'] as $id) {
                    if ($p = Plugin::lookup($id)) {
                        if (!$p->enable())
                            $errors['err'] = sprintf(
                                __('Unable to enable %s'),
                                $p->getName());
                    }
                }
                break;
            case 'disable':
                foreach ($_POST['ids'] as $id) {
                    if ($p = Plugin::lookup($id)) {
                        $p->disable();
                    }
                }
                break;
            case 'delete':
                foreach ($_POST['ids'] as $id) {
                    if ($p = Plugin::lookup($id)) {
                        $p->uninstall();
                    }
                }
                break;
            }
        }
        break;
    case 'install':
        if ($ost->plugins->install($_POST['install_path']))
            $msg = sprintf(__('Successfully installed %s'),
                __('a plugin'));
        break;
    }
}

$page = 'plugins.inc.php';
if ($plugin)
    $page = 'plugin.inc.php';
elseif ($_REQUEST['a']=='add')
    $page = 'plugin-add.inc.php';

$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
