<?php
require('admin.inc.php');
require_once(INCLUDE_DIR."/class.plugin.php");
$plugin = $instance = null;
if ($_REQUEST['id'] ) {
    if (!($plugin=PluginManager::lookup((int) $_REQUEST['id'])))
        $errors['err'] = sprintf(__('%s: Unknown or invalid ID.'),
                __('plugin'));
    elseif ($plugin->isDefunct()) {
        $errors['err'] = sprintf('%s: %s',
                $plugin->getName(), __('Defunct - missing'));
        $plugin = null;
    } elseif ($_REQUEST['xid']) {
        if (!($instance = $plugin->getInstance( (int) $_REQUEST['xid'])))
            $errors['err'] = sprintf(__('%s: Unknown or invalid ID.'),
                    __('Plugin Instance'));
    }

}

if ($_POST) {
    switch(strtolower($_POST['do'])) {
    case 'add-instance':
        if ($plugin && ($instance=$plugin->addInstance($_POST, $errors)))
            $msg = sprintf('%s %s',
                        __('Plugin'),
                        __('Added Successfully'));
        elseif (!$errors['err'])
            $errors['err'] = sprintf(__('Unable to add %s.'),
                     __('Plugin Instance'));
        break;
    case 'update-instance':
         if ($instance && $instance->update($_POST, $errors))
             $msg = sprintf('%s %s',
                      __('Instance'),
                      __('Updated Successfully'));
         elseif (!$errors['err'])
             $errors['err'] = sprintf(__('Unable to update %s.'),
                     __('Plugin Instance'));
        break;
    case 'update':
        if ($plugin) {
            if ($plugin->update($_POST, $errors))
                $msg = sprintf('%s %s',
                        __('Plugin'),
                        __('Updated Successfully'));
            else
                 $errors['err'] = sprintf(__('Unable to update %s.'),
                         __('Plugin'));
        }
        break;
    case 'instances-actions':
        if (!$plugin) {
             $errors['err'] = sprintf(__('Unable to update %s.'),
                         __('Plugin Instances'));
        } elseif (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
            $errors['err'] = sprintf(__('You must select at least one %s.'),
                __('Plugin Instance'));
        } else {
            $count = count($_POST['ids']);
            $criteria = ['id__in' => array_values($_POST['ids'])];
            $flag = PluginInstance::FLAG_ENABLED;
            $instances = $plugin->instances;
            switch(strtolower($_POST['a'])) {
                case 'enable':
                    $instances->filter($criteria)
                        ->update(array(
                            'flags' => SqlExpression::bitor(
                                new SqlField('flags'), $flag)
                        ));
                    break;
                case 'disable':
                    $count = $instances->filter($criteria)
                        ->update(array(
                            'flags' => SqlExpression::bitand(
                                new SqlField('flags'), ~$flag)
                        ));
                    break;
                case 'delete':
                    foreach ($instances->filter($criteria) as $i)
                        $i->delete();
                    break;
                default:
                     $errors['err'] = __('Unknown Action');
            }
        }
        break;
    case 'mass_process':
        if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
            $errors['err'] = sprintf(__('You must select at least %s.'),
                __('one plugin'));
        } else {
            $plugins = Plugin::objects()->filter([
                    'id__in' => array_values($_POST['ids'])]);
            switch(strtolower($_POST['a'])) {
            case 'enable':
                $plugins->update(['isactive' => 1]);
                break;
            case 'disable':
                 $plugins->update(['isactive' => 0]);
                break;
            case 'delete':
                foreach ($plugins as $p)
                    $p->uninstall($errors);
                break;
            }
            // reset cached list
            PluginManager::clearCache();
            //Fixme: address sticky cache
            Http::redirect('plugins.php');
        }
        break;
    case 'install':
        if (($plugin=$ost->plugins->install($_POST['install_path'])))
            $msg = sprintf(__('Successfully installed %s'),
                __('a plugin'));
        break;
    }
}

$page = 'plugins.inc.php';
if ($plugin) {
    // Warn if plugin is nolonger compatible
    if (!$plugin->isCompatible())
        $warn = sprintf('%s <b>%s v%s+</b>',
                __('Plugin Requires'),
                 'osTicket', $plugin->getosTicketVersion());
    $page = 'plugin.inc.php';
    if ($instance || $_REQUEST['a'] == 'add-instance')
        $page = 'plugin-instance.inc.php';
} elseif ($_REQUEST['a']=='add')
    $page = 'plugin-add.inc.php';

$nav->setTabActive('manage');
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
