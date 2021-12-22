<?php
/*********************************************************************
    filters.php

    Email Filters

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
include_once(INCLUDE_DIR.'class.filter.php');
require_once(INCLUDE_DIR.'class.canned.php');

$filter=null;
if($_REQUEST['id'] && !($filter=Filter::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid'), __('ticket filter'));

/* NOTE: Banlist has its own interface*/
if($filter && $filter->isSystemBanlist())
    Http::redirect('banlist.php');

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$filter){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('ticket filter'));
            }elseif($filter->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s.'), __('this ticket filter'));
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this ticket filter')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'add':
            $filter = new Filter();
            if ($filter->update($_POST, $errors)) {
                $msg=sprintf(__('Successfully updated %s.'), __('this ticket filter'));
                $type = array('type' => 'created');
                Signal::send('object.created', $filter, $type);
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err'] = sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this ticket filter')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s to process.'),
                    __('one ticket filter'));
            } else {
                $count=count($_POST['ids']);
                switch(strtolower($_POST['a'])) {
                    case 'enable':
                        $num = 0;
                        foreach (Filter::objects()->filter([
                            'id__in' => $_POST['ids'],
                        ]) as $F) {
                            $F->isactive = 1;
                            if ($F->save())
                                $num++;
                        }

                        if ($num) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully enabled %s'),
                                    _N('selected ticket filter', 'selected ticket filters', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s enabled'), $num, $count,
                                    _N('selected ticket filter', 'selected ticket filters', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to enable %s'),
                                _N('selected ticket filter', 'selected ticket filters', $count));
                        }
                        break;
                    case 'disable':
                        $num = 0;
                        foreach (Filter::objects()->filter([
                            'id__in' => $_POST['ids'],
                        ]) as $F) {
                            $F->isactive = 0;
                            if ($F->save())
                                $num++;
                        }

                        if ($num) {
                            if($num==$count)
                                $msg = sprintf(__('Successfully disabled %s'),
                                    _N('selected ticket filter', 'selected ticket filters', $count));
                            else
                                $warn = sprintf(__('%1$d of %2$d %3$s disabled'), $num, $count,
                                    _N('selected ticket filter', 'selected ticket filters', $count));
                        } else {
                            $errors['err'] = sprintf(__('Unable to disable %s'),
                                _N('selected ticket filter', 'selected ticket filters', $count));
                        }
                        break;
                    case 'delete':
                        $i=0;
                        foreach($_POST['ids'] as $k=>$v) {
                            if(($f=Filter::lookup($v)) && !$f->isSystemBanlist() && $f->delete())
                                $i++;
                        }

                        if($i && $i==$count)
                            $msg = sprintf(__('Successfully deleted %s.'),
                                _N('selected ticket filter', 'selected ticket filters', $count));
                        elseif($i>0)
                            $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                                _N('selected ticket filter', 'selected ticket filters', $count));
                        elseif(!$errors['err'])
                            $errors['err'] = sprintf(__('Unable to delete %s.'),
                                 _N('selected ticket filter', 'selected ticket filters', $count));
                        break;
                    default:
                        $errors['err']=sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
                }
            }
            break;
        default:
            $errors['err']=__('Unknown action');
            break;
    }
}

$page='filters.inc.php';
$tip_namespace = 'manage.filter';
if($filter || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
  if($filter) {
    foreach ($filter->getActions() as $A) {
      $config = JsonDataParser::parse($A->configuration);
      if($A->type == 'dept')
        $dept = Dept::lookup($config['dept_id']);

      if($A->type == 'topic')
        $topic = Topic::lookup($config['topic_id']);
    }
  }

  if($dept && !$dept->isActive())
    $warn = sprintf(__('%s is assigned a %s that is not active.'), __('Ticket Filter'), __('Department'));

  if($topic && !$topic->isActive())
    $warn = sprintf(__('%s is assigned a %s that is not active.'), __('Ticket Filter'), __('Help Topic'));

    $page='filter.inc.php';
}

$nav->setTabActive('manage');
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
