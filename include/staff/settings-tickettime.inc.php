<?php
// Strobe Technologies Ltd | 22/06/2016 | Ticket Time Menu allowing you to enable and disable options / views

if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
?>
<h2><?php echo __('Ticket Time Settings');?></h2>
<form action="settings.php?t=tickettime" method="post" id="save">
<?php csrf_token(); ?>
<input type="hidden" name="t" value="tickettime" >
<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo __('Ticket Time Settings');?></h4>
                <em><?php echo __("Enabling these options allow you to add time to you tickets.");?></em>
				<em><b><?php echo __('General Settings'); ?></b></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180"><?php echo __('Client Time Status'); ?>:</td>
            <td>
                <input type="checkbox" name="isclienttime" value="1" <?php echo $config['isclienttime']?'checked="checked"':''; ?>>
                <?php echo __('Enable Client Time View'); ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['isclienttime']; ?></font>
                <i class="help-tip icon-question-sign" href="#client_time"></i>
            </td>
        </tr>
		<tr>
            <td width="180"><?php echo __('Thread Ticket Time');?>:</td>
            <td>
                <input type="checkbox" name="isthreadtime" value="1" <?php echo $config['isthreadtime']?'checked="checked"':''; ?> >
                <?php echo __('Enable Adding Time to Tickets via Threads'); ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['isthreadtime']; ?></font>
                <i class="help-tip icon-question-sign" href="#thread_time"></i>
            </td>
        </tr>
		<tr>
			<th colspan="2">
				<em><b><?php echo __('Thread Settings'); ?></b></em>
            </th>
		</tr>
		<tr>
            <td width="180"><?php echo __('Ticket Timer');?>:</td>
            <td>
                <input type="checkbox" name="isthreadtimer" value="1" <?php echo $config['isthreadtimer']?'checked="checked"':''; ?> >
                <?php echo __('Enable Timer to Tickets Threads'); ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['isthreadtimer']; ?></font>
                <i class="help-tip icon-question-sign" href="#thread_timer"></i>
            </td>
        </tr>
		<tr>
            <td width="180"><?php echo __('Time Billable');?>:</td>
            <td>
                <input type="checkbox" name="isthreadbill" value="1" <?php echo $config['isthreadbill']?'checked="checked"':''; ?> >
                <?php echo __('Enable Thread Time to be Billed'); ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['isthreadbill']; ?></font>
                <i class="help-tip icon-question-sign" href="#thread_bill"></i>
            </td>
        </tr>
		<tr>
            <td width="180"><?php echo __('Billable as Default');?>:</td>
            <td>
                <input type="checkbox" name="isthreadbilldefault" value="1" <?php echo $config['isthreadbilldefault']?'checked="checked"':''; ?> >
                <?php echo __('Time to be Billed as default'); ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['isthreadbilldefault']; ?></font>
                <i class="help-tip icon-question-sign" href="#thread_billdefault"></i>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:210px;">
    <input class="button" type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
    <input class="button" type="reset" name="reset" value="<?php echo __('Reset Changes'); ?>">
</p>
</form>
