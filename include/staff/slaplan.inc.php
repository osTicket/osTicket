<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info=array();
$qstr='';
if($sla && $_REQUEST['a']!='add'){
    $title='Update SLA Plan';
    $action='update';
    $submit_text='Save Changes';
    $info=$sla->getInfo();
    $info['id']=$sla->getId();
    $qstr.='&id='.$sla->getId();
}else {
    $title='Add New SLA Plan';
    $action='add';
    $submit_text='Add Plan';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $info['enable_priority_escalation']=isset($info['enable_priority_escalation'])?$info['enable_priority_escalation']:1;
    $info['disable_overdue_alerts']=isset($info['disable_overdue_alerts'])?$info['disable_overdue_alerts']:0;
    $qstr.='&a='.urlencode($_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="slas.php?<?php echo $qstr; ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Service Level Agreement</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Tickets are marked overdue on grace period violation.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
              Name:
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
              Grace Period:
            </td>
            <td>
                <input type="text" size="10" name="grace_period" value="<?php echo $info['grace_period']; ?>">
                <em>( in hours )</em>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['grace_period']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Status:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong>Active</strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>>Disabled
                &nbsp;<span class="error">*&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Priority Escalation:
            </td>
            <td>
                <input type="checkbox" name="enable_priority_escalation" value="1" <?php echo $info['enable_priority_escalation']?'checked="checked"':''; ?> >
                    <strong>Enable</strong> priority escalation on overdue tickets.
            </td>
        </tr>
        <tr>
            <td width="180">
                Transient:
            </td>
            <td>
                <input type="checkbox" name="transient" value="1" <?php echo $info['transient']?'checked="checked"':''; ?> >
                SLA can be overridden on ticket transfer or help topic
                change
            </td>
        </tr>
        <tr>
            <td width="180">
                Ticket Overdue Alerts:
            </td>
            <td>
                <input type="checkbox" name="disable_overdue_alerts" value="1" <?php echo $info['disable_overdue_alerts']?'checked="checked"':''; ?> >
                    <strong>Disable</strong> overdue alerts notices.
                    <em>(Override global setting)</em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Admin Notes</strong>: Internal notes.&nbsp;</em>
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
<p style="padding-left:225px;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="slas.php"'>
</p>
</form>
