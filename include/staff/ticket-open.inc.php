<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->canCreateTickets()) die('Access Denied');
$info=array();
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="tickets.php?a=open" method="post" id="save"  enctype="multipart/form-data">
 <input type="hidden" name="do" value="create">
 <input type="hidden" name="a" value="open">
 <h2>Open New Ticket</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4>New Ticket</h4>
                <em><strong>User Information</strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="160" class="required">
                Email Address:
            </td>
            <td>
                <input type="text" size="30" name="email" id="email" class="typeahead" value="<?php echo $info['email']; ?>" 
                    autocomplete="off" autocorrect="off" autocapitalize="off">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['email']; ?></span>
            <?php 
            if($cfg->notifyONNewStaffTicket()) { ?>
               &nbsp;&nbsp;&nbsp;
               <input type="checkbox" name="alertuser" <?php echo (!$errors || $info['alertuser'])? 'checked="checked"': ''?>>Send alert to user.
            <?php 
             } ?>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                Full Name:
            </td>
            <td>
                <input type="text" size="30" name="name" id="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="160">
                Phone Number:
            </td>
            <td>
                <input type="text" size="18" name="phone" id="phone" value="<?php echo $info['phone']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone']; ?></span>
                Ext <input type="text" size="5" name="phone_ext" id="phone_ext" value="<?php echo $info['phone_ext']; ?>">
                &nbsp;<span class="error">&nbsp;<?php echo $errors['phone_ext']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Ticket Information</strong>:</em>
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
                    <option value="Other" <?php echo ($info['source']=='Other')?'selected="selected"':''; ?>>Other</option>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['source']?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                Department:
            </td>
            <td>
                <select name="deptId">
                    <option value="" selected >&mdash; Select Department &mdash;</option>
                    <?php
                    if($depts=Dept::getDepartments()) {
                        foreach($depts as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['deptId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?=$errors['deptId']?></font>
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
                Subject:
            </td>
            <td>
                 <input type="text" name="subject" size="35" value="<?=$info['subject']?>">
                 &nbsp;<font class="error">*&nbsp;<?=$errors['subject']?></font>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Issue summary</strong>: Detailed summary of the reason(s) of opening the ticket. <font class="error">*&nbsp;<?=$errors['issue']?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea name="issue" cols="21" rows="8" style="width:80%;"><?php echo $info['issue']; ?></textarea>
                <br><em>The user will be able to see the summary and any associated responses.</em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Response</strong>: Optional response to the above issue.</em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
            <?php
            if(($cannedResponses=Canned::getCannedResponses())) {
                ?>
                <div>
                    Canned Response:&nbsp;
                    <select id="cannedResp" name="cannedResp">
                        <option value="0" selected="selected">&mdash; Select a canned response &mdash;</option>
                        <?php
                        foreach($cannedResponses as $id =>$title) {
                            echo sprintf('<option value="%d">%s</option>',$id,$title);
                        }
                        ?>
                    </select>
                    &nbsp;&nbsp;&nbsp;
                    <label><input type='checkbox' value='1' name="append" id="append" checked="checked">Append</label>
                </div>
            <?php
            } ?>
                <textarea name="response" id="response" cols="21" rows="8" style="width:80%;"><?php echo $info['response']; ?></textarea>
            <?php
            if($cfg->allowAttachments()) { ?>
                <br><em><b>Attachments:</b> Response required when files are attached.</em>
                <div id="canned_attachments">
                    <?php
                    if($info['cannedattachments']) {
                        foreach($info['cannedattachments'] as $k=>$id) {
                            if(!($file=AttachmentFile::lookup($id))) continue;
                            $hash=$file->getHash().md5($file->getId().session_id().$file->getHash());
                            echo sprintf('<label><input type="checkbox" name="cannedattachments[]" id="f%d" value="%d" checked="checked">
                                        <a href="file.php?h=%s">%s</a>&nbsp;&nbsp;</label>&nbsp;',
                                        $file->getId(), $file->getId() , $hash, $file->getName());
                        }
                    }
                    ?>
                </div>
                <div id="uploads"></div>
                <div class="file_input">
                    <input type="file" class="multifile" name="attachments[]" size="30" value="" />
                </div>
            <?
            } ?>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <em><strong>Internal Note</strong>: Optional internal note (recommended on assignment) <font class="error">&nbsp;<?php echo $errors['note'];?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea name="note" cols="21" rows="6" style="width:80%;"><?php echo $info['note']; ?></textarea>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Ticket Options</strong>: Due date, when set, overwrites SLA grace period.</em>
            </th>
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
            <td width="160">
                Priority:
            </td>
            <td>
                <select name="priorityId">
                    <option value="0" selected >&mdash; System Default &mdash;</option>
                    <?php
                    if($priorities=Priority::getPriorities()) {
                        foreach($priorities as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['priorityId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">&nbsp;<?=$errors['priorityId']?></font>
            </td>
        </tr>
        <?php
        if($thisstaff->canAssignTickets()) { ?>
        <tr>
            <td width="160">Assign To:</td>
            <td>
                <select id="assignId" name="assignId">
                    <option value="0" selected="selected">&mdash; Select Staff Member OR a Team &mdash;</option>
                    <?php
                    if(($users=Staff::getAvailableStaffMembers())) {
                        echo '<OPTGROUP label="Staff Members ('.count($users).')">';
                        foreach($users as $id => $name) {
                            $k="s$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }
                    
                    if(($teams=Team::getActiveTeams())) {
                        echo '<OPTGROUP label="Teams ('.count($teams).')">';
                        foreach($teams as $id => $name) {
                            $k="t$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>&nbsp;<span class='error'>&nbsp;<?php echo $errors['assignId']; ?></span>
            </td>
        </tr>
        <?php
        } ?>
        <?php
        if($thisstaff->canCloseTickets()) { ?>
        <tr>
            <td width="160">
                Ticket Status:
            </td>
            <td>
                <input type="checkbox" name="ticket_state" value="closed" <?php echo $info['ticket_state']?'checked="checked"':''; ?>>
                <b>Close On Response</b>&nbsp;<em>(Only applicable if response is entered)</em>
            </td>
        </tr>
        <?php
        } ?>
        <tr>
            <td>Signature:</td>
            <td>
                <?php
                $info['signature']=$info['signature']?$info['signature']:$thisstaff->getDefaultSignatureType();
                ?>
                <label><input type="radio" name="signature" value="none" checked="checked"> None</label>
                <?php
                if($thisstaff->getSignature()) {?>
                    <label><input type="radio" name="signature" value="mine"
                        <?php echo ($info['signature']=='mine')?'checked="checked"':''; ?>> My signature</label>
                <?php
                } ?>
                <label><input type="radio" name="signature" value="dept"
                    <?php echo ($info['signature']=='dept')?'checked="checked"':''; ?>>
                    Dept. Signature (if set)</label>
                <span style="padding-left:25px;">
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:250px;">
    <input type="submit" name="submit" value="Open">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="departments.php"'>
</p>
</form>
