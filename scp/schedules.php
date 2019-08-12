<?php
require('admin.inc.php');
require_once INCLUDE_DIR.'class.schedule.php';
require_once INCLUDE_DIR.'class.businesshours.php';

$errors = array();
$schedule = null;
if ($_REQUEST['id'])
    $schedule = Schedule::lookup($_REQUEST['id']);

if ($_POST) {
    switch (strtolower($_REQUEST['do'])) {
        case 'update':
            if (!$schedule)
                $errors['err']=sprintf(__('%s: Unknown or invalid ID.'),
                    __('Schedule'));
            elseif ($schedule->update($_POST, $errors))
                $msg = sprintf(__('Successfully updated %s.'),
                    $schedule->getName());
            else
                $errors['err'] = sprintf(__('Unable to update %s.'),
                        __('this schedule'));
            break;
        case 'mass_process':
            if (!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one schedule'));
            } else {
                $count = count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=Schedule::lookup($v)) && $t->delete())
                                $i++;
                        }
                        if ($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected schedule', 'selected schedules', $count));
                        elseif ($i > 0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected schedule', 'selected schedules', $count));
                        elseif (!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s. They may be in use.'),
                                _N('selected schedule', 'selected schedules', $count));
                        break;
                }
            }
            break;
    }
}

if ($redirect)
    Http::redirect($redirect);

$page='schedules.inc.php';
if ($schedule)
    $page = 'schedule.inc.php';


$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="manage.schedule" />',
        "$('#content').data('tipNamespace', 'manage.schedule');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
