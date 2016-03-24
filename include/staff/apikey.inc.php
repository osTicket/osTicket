<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info=$qs = array();
if($api && $_REQUEST['a']!='add'){
    $title=__('Update API Key');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$api->getHashtable();
    $qs += array('id' => $api->getId());
}else {
    $title=__('Add New API Key');
    $action='add';
    $submit_text=__('Add Key');
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="apikeys.php?<?php echo Http::build_query($qs); ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo $title; ?>
    <?php if (isset($info['ipaddr'])) { ?><small>
    — <?php echo $info['ipaddr']; ?></small>
    <?php } ?>
    <i class="help-tip icon-question-sign" href="#api_key"></i>
    </h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('API Key is auto-generated. Delete and re-add to change the key.');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="150" class="required">
                <?php echo __('Status');?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong><?php echo __('Active');?></strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>><?php echo __('Disabled');?>
                &nbsp;<span class="error">*&nbsp;</span>
            </td>
        </tr>
        <?php if($api){ ?>
        <tr>
            <td width="150">
                <?php echo __('IP Address');?>:
            </td>
            <td>
                <span>
                <?php echo $api->getIPAddr(); ?>
                <i class="help-tip icon-question-sign" href="#ip_addr"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="150">
                <?php echo __('API Key');?>:
            </td>
            <td><?php echo $api->getKey(); ?> &nbsp;</td>
        </tr>
        <?php }else{ ?>
        <tr>
            <td width="150" class="required">
               <?php echo __('IP Address');?>:
            </td>
            <td>
                <span>
                <input type="text" size="30" name="ipaddr" value="<?php echo $info['ipaddr']; ?>"i
                    autofocus>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['ipaddr']; ?></span>
                <i class="help-tip icon-question-sign" href="#ip_addr"></i>
                </span>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Services');?>:</strong> <?php echo __('Check applicable API services enabled for the key.');?></em>
            </th>
        </tr>
        <tr>
            <td colspan=2 style="padding-left:5px">
                <label>
                    <input type="checkbox" name="can_create_tickets" value="1" <?php echo $info['can_create_tickets']?'checked="checked"':''; ?> >
                    <?php echo __('Can Create Tickets <em>(XML/JSON/EMAIL)</em>');?>
                </label>
            </td>
        </tr>
        <tr>
            <td colspan=2 style="padding-left:5px">
                <label>
                    <input type="checkbox" name="can_exec_cron" value="1" <?php echo $info['can_exec_cron']?'checked="checked"':''; ?> >
                    <?php echo __('Can Execute Cron');?>
                </label>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Notes');?></strong>: <?php echo __("be liberal, they're internal");?></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="apikeys.php"'>
</p>
</form>
