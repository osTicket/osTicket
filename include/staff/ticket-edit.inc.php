<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->canEditTickets() || !$ticket) die('Access Denied');

$info=Format::htmlchars(($errors && $_POST)?$_POST:$ticket->getUpdateInfo());
?>
<form action="tickets.php?id=<?php echo $ticket->getId(); ?>&a=edit" method="post" id="save"  enctype="multipart/form-data">
 <input type="hidden" name="do" value="update">
 <input type="hidden" name="a" value="edit">
 <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
 <h2>Update Ticket# <?php echo $ticket->getExtId(); ?></h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>Ticket Update</h4>
                <em><strong>User Information</strong>: Make sure the email address is valid.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="160" class="required">
                Full Name:
            </td>
            <td>
                <input type="text" size="45" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                Email Address:
            </td>
            <td>
                <input type="text" size="45" name="email" value="<?php echo $info['email']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="160">
                Phone Number:
            </td>
            <td>
                <input type="text" size="18" name="phone" value="<?php echo $info['phone']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone']; ?></span>
                Ext <input type="text" size="5" name="phone_ext" value="<?php echo $info['phone_ext']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone_ext']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Ticket Information</strong>: Due date overwrites SLA's grace period.</em>
            </th>
        </tr>
        <tr>
            <td width="160" class="required">
                Ticket Source:
            </td>
            <td>
                <select name="source">
                    <option value="" selected >&mdash; Select Source &mdash;</option>
                    <option value="Phone" <?php echo ($info['source']=='Phone')?'selected="selected"':''; ?>>Phone</option>
                    <option value="Email" <?php echo ($info['source']=='Email')?'selected="selected"':''; ?>>Email</option>
                    <option value="Web"   <?php echo ($info['source']=='Web')?'selected="selected"':''; ?>>Web</option>
                    <option value="API"   <?php echo ($info['source']=='API')?'selected="selected"':''; ?>>API</option>
                    <option value="Other" <?php echo ($info['source']=='Other')?'selected="selected"':''; ?>>Other</option>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['source']?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                Help Topic:
            </td>
            <td>
                <select name="topicId">
                    <option value="" selected >&mdash; Select Help Topic &mdash;</option>
                    <?php
                    if($topics=Topic::getHelpTopics()) {
                        foreach($topics as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['topicId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['topicId']?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                Priority Level:
            </td>
            <td>
                <select name="priorityId">
                    <option value="" selected >&mdash; Select Priority &mdash;</option>
                    <?php
                    if($priorities=Priority::getPriorities()) {
                        foreach($priorities as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['priorityId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">*&nbsp;<?=$errors['priorityId']?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                SLA:
            </td>
            <td>
                <select name="slaId">
                    <option value="" selected >&mdash; Select SLA &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['slaId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">*&nbsp;<?=$errors['slaId']?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                Subject:
            </td>
            <td>
                 <input type="text" name="subject" size="60" value="<?=$info['subject']?>">
                 &nbsp;<font class="error">*&nbsp;<?=$errors['subject']?></font>
            </td>
        </tr>
        <tr>
            <td width="160">
                Due Date:
            </td>
            <td>
                <input id="duedate" name="duedate" value="<?php echo Format::htmlchars($info['duedate']); ?>" size="10"
                    onclick="event.cancelBubble=true;calendar(this);" autocomplete=OFF>
                <a href="#" onclick="event.cancelBubble=true;calendar(getObj('duedate')); return false;"><img src='images/cal.png'border=0 alt=""></a>
                &nbsp;&nbsp;
                <?php
                $min=$hr=null;
                if($info['time'])
                    list($hr,$min)=explode(':',$info['time']);
                    echo Misc::timeDropdown($hr,$min,'time');
                ?>
                &nbsp;<font class="error">&nbsp;<?=$errors['duedate']?>&nbsp;<?php echo $errors['time']; ?></font>
                <em>Time is based on your time zone (GM <?php echo $thisstaff->getTZoffset(); ?>)</em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Internal Note</strong>: Reason for editing the ticket (required) <font class="error">&nbsp;<?php echo $errors['note'];?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea name="note" cols="21" rows="6" style="width:80%;"><?php echo $info['note']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input type="submit" name="submit" value="Save">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="tickets.php?id=<?php echo $ticket->getId(); ?>"'>
</p>
</form>
