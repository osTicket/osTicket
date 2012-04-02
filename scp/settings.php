<?php
/*********************************************************************
    settings.php

    Handles all admin settings.
    
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('admin.inc.php');
$errors=array();
$SettingOptions=array('general'=>'General Settings',
               'dates'=>'Date and Time Options',
               'tickets'=>'Ticket Settings and Options',
               'emails'=>'Email Settings',
               'attachments'=>'Attachments Settings',
               'kb'=>'Knowledgebase Settings',
               'autoresponders'=>'Autoresponder Settings',
               'alerts'=>'Alerts and Notices Settings');

//Handle a POST.
if($_POST && !$errors){
    $errors=array();
    if($cfg && $cfg->updateSettings($_POST,$errors)){
        $msg=Format::htmlchars($SettingOptions[$_POST['t']]).' Updated Successfully';
        $cfg->reload();
    }elseif(!$errors['err']){
        $errors['err']='Unable to update system settings - correct any errors below and try again';
    }
}

$target=($_REQUEST['t'] && $SettingOptions[$_REQUEST['t']])?$_REQUEST['t']:'general';

$nav->setTabActive('settings');
require(STAFFINC_DIR.'header.inc.php');
?>
<h2>System Preferences and Settings - <span>osTicket (v<?php echo $cfg->getVersion(); ?>)</span></h2>
<div style="padding-top:10px;padding-bottom:5px;">
    <form method="get" action="settings.php">
    Setting Option: 
    <select id="setting_options" name="t" style="width:300px;">
        <option value="">&mdash; Select Setting Group &mdash;</option>
        <?php
        foreach($SettingOptions as $k=>$v) {
            $sel=($target==$k)?'selected="selected"':'';
            echo sprintf('<option value="%s" %s>%s</option>',$k,$sel,$v);
        }
        ?>
    </select>
    <input type="submit" value="Go">
    </form>
</div>
<?php
$config=($errors && $_POST)?Format::input($_POST):Format::htmlchars($cfg->getConfig());
include_once(STAFFINC_DIR."settings-$target.inc.php");
include_once(STAFFINC_DIR.'footer.inc.php');
?>
