<?php
/*********************************************************************
    helptopics.php

    Help Topics.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.topic.php');
require_once(INCLUDE_DIR.'class.dynamic_forms.php');

$topic=null;
if($_REQUEST['id'] && !($topic=Topic::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('help topic'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$topic){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('help topic'));
            }elseif($topic->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s'),
                    __('this help topic'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Error updating %s. Try again!'),
                    __('this help topic'));
            }
            break;
        case 'create':
            if(($id=Topic::create($_POST,$errors))){
                $msg=sprintf(__('Successfully added %s'), Format::htmlchars($_POST['topic']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf(__('Unable to add %s. Correct error(s) below and try again.'),
                    __('this help topic'));
            }
            break;
        case 'mass_process':
            switch(strtolower($_POST['a'])) {
            case 'sort':
                // Pass
                break;
            default:
                if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids']))
                    $errors['err'] = sprintf(__('You must select at least %s'),
                        __('one help topic'));
            }
            if (!$errors) {
                $count=count($_POST['ids']);

                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=1 '
                            .' WHERE topic_id IN ('.implode(',', db_input($_POST['ids'])).')';

                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully enabled %s'),
                                    _N('selected help topic', 'selected help topics', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s enabled'), $num, $count,
                                    _N('selected help topic', 'selected help topics', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to enable %s.'),
                                _N('selected help topic', 'selected help topics', $count));
                        }
                        break;
                    case 'disable':
                        $sql='UPDATE '.TOPIC_TABLE.' SET isactive=0 '
                            .' WHERE topic_id IN ('.implode(',', db_input($_POST['ids'])).')'
                            .' AND topic_id <> '.db_input($cfg->getDefaultTopicId());
                        if(db_query($sql) && ($num=db_affected_rows())) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully diabled %s'),
                                    _N('selected help topic', 'selected help topics', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected help topic', 'selected help topics', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected help topic', 'selected help topics', $count));
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($t=Topic::lookup($v)) && $t->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s'),
                                _N('selected help topic', 'selected elp topics', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected help topic', 'selected help topics', $count));
                        elseif(!$errors['err'])
                            $errors['err']  = sprintf(__('Unable to delete %s'),
                                _N('selected help topic', 'selected help topics', $count));

                        break;
                    case 'sort':
                        try {
                            $cfg->setTopicSortMode($_POST['help_topic_sort_mode']);
                            if ($cfg->getTopicSortMode() == 'm') {
                                foreach ($_POST as $k=>$v) {
                                    if (strpos($k, 'sort-') === 0
                                            && is_numeric($v)
                                            && ($t = Topic::lookup(substr($k, 5))))
                                        $t->setSortOrder($v);
                                }
                            }
                            $msg = __('Successfully set sorting configuration');
                        }
                        catch (Exception $ex) {
                            $errors['err'] = __('Unable to set sorting mode');
                        }
                        break;
                    default:
                        $errors['err']=__('Unknown action - get technical help.');
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
    if ($id or $topic) {
        if (!$id) $id=$topic->getId();
    }
}

$page='helptopics.inc.php';
$tip_namespace = 'manage.helptopic';
if($topic || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='helptopic.inc.php';
}

$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
