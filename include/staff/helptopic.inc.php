<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info=array();
$qstr='';
if($topic && $_REQUEST['a']!='add') {
    $title='Update Help Topic';
    $action='update';
    $submit_text='Save Changes';
    $info=$topic->getInfo();
    $info['id']=$topic->getId();
    $info['pid']=$topic->getPid();
    $qstr.='&id='.$topic->getId();
} else {
    $title='Add New Help Topic';
    $action='create';
    $submit_text='Add Topic';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $info['form_id'] = Topic::FORM_USE_PARENT;
    $qstr.='&a='.$_REQUEST['a'];
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="helptopics.php?<?php echo $qstr; ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Help Topic</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Help Topic Information&nbsp;<i class="help-tip icon-question-sign" href="#help_topic_information"></i></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
               Topic:
            </td>
            <td>
                <input type="text" size="30" name="topic" value="<?php echo $info['topic']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['topic']; ?></span> <i class="help-tip icon-question-sign" href="#topic"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Status:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>>Active
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>>Disabled
                &nbsp;<span class="error">*&nbsp;</span> <i class="help-tip icon-question-sign" href="#status"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Type:
            </td>
            <td>
                <input type="radio" name="ispublic" value="1" <?php echo $info['ispublic']?'checked="checked"':''; ?>>Public
                <input type="radio" name="ispublic" value="0" <?php echo !$info['ispublic']?'checked="checked"':''; ?>>Private/Internal
                &nbsp;<span class="error">*&nbsp;</span> <i class="help-tip icon-question-sign" href="#type"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Parent Topic:
            </td>
            <td>
                <select name="topic_pid">
                    <option value="">&mdash; Top-Level Topic &mdash;</option><?php
                    $topics = Topic::getAllHelpTopics();
                    while (list($id,$topic) = each($topics)) {
                        if ($id == $info['topic_id'])
                            continue; ?>
                        <option value="<?php echo $id; ?>"<?php echo ($info['topic_pid']==$id)?'selected':''; ?>><?php echo $topic; ?></option>
                    <?php
                    } ?>
                </select> <i class="help-tip icon-question-sign" href="#parent_topic"></i>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['pid']; ?></span>
            </td>
        </tr>

        <tr><th colspan="2"><em>New ticket options</em></th></tr>
        <tr>
           <td><strong>Custom Form</strong>:</td>
           <td><select name="form_id">
                <option value="0" <?php
if ($info['form_id'] == '0') echo 'selected="selected"';
                    ?>>&mdash; None &mdash;</option>
                <option value="<?php echo Topic::FORM_USE_PARENT; ?>"  <?php
if ($info['form_id'] == Topic::FORM_USE_PARENT) echo 'selected="selected"';
                    ?>>&mdash; Use Parent Form &mdash;</option>
               <?php foreach (DynamicForm::objects()->filter(array('type'=>'G')) as $group) { ?>
                <option value="<?php echo $group->get('id'); ?>"
                       <?php if ($group->get('id') == $info['form_id'])
                            echo 'selected="selected"'; ?>>
                       <?php echo $group->get('title'); ?>
                   </option>
               <?php } ?>
               </select>
               &nbsp;<span class="error">&nbsp;<?php echo $errors['form_id']; ?></span>
               <i class="help-tip icon-question-sign" href="#custom_form"></i>
           </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Department:
            </td>
            <td>
                <select name="dept_id">
                    <option value="0">&mdash; System Default &mdash;</option>
                    <?php
                    $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' dept ORDER by dept_name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['dept_id'] && $id==$info['dept_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['dept_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#department"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Priority:
            </td>
            <td>
                <select name="priority_id">
                    <option value="">&mdash; System Default &mdash;</option>
                    <?php
                    $sql='SELECT priority_id,priority_desc FROM '.PRIORITY_TABLE.' pri ORDER by priority_urgency DESC';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['priority_id'] && $id==$info['priority_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['priority_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#priority"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                SLA Plan:
            </td>
            <td>
                <select name="sla_id">
                    <option value="0">&mdash; Department's Default &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['sla_id']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['sla_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#sla_plan"></i>
            </td>
        </tr>
        <tr>
            <td width="180">Thank-you Page:</td>
            <td>
                <select name="page_id">
                    <option value="">&mdash; System Default &mdash;</option>
                    <?php
                    if(($pages = Page::getActiveThankYouPages())) {
                        foreach($pages as $page) {
                            if(strcasecmp($page->getType(), 'thank-you')) continue;
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $page->getId(),
                                    ($info['page_id']==$page->getId())?'selected="selected"':'',
                                    $page->getName());
                        }
                    }
                    ?>
                </select>&nbsp;<font class="error"><?php echo $errors['page_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#thank_you_page"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Auto-assign To:
            </td>
            <td>
                <select name="assign">
                    <option value="0">&mdash; Unassigned &mdash;</option>
                    <?php
                    if (($users=Staff::getStaffMembers())) {
                        echo '<OPTGROUP label="Staff Members">';
                        foreach ($users as $id => $name) {
                            $name = new PersonsName($name);
                            $k="s$id";
                            $selected = ($info['assign']==$k || $info['staff_id']==$id)?'selected="selected"':'';
                            ?>
                            <option value="<?php echo $k; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>

                        <?php
                        }
                        echo '</OPTGROUP>';
                    }
                    $sql='SELECT team_id, name, isenabled FROM '.TEAM_TABLE.' ORDER BY name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        echo '<OPTGROUP label="Teams">';
                        while (list($id, $name, $isenabled) = db_fetch_row($res)){
                            $k="t$id";
                            $selected = ($info['assign']==$k || $info['team_id']==$id)?'selected="selected"':'';

                            if (!$isenabled)
                                $name .= ' (disabled)';
                            ?>
                            <option value="<?php echo $k; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['assign']; ?></span>
                <i class="help-tip icon-question-sign" href="#auto_assign_to"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Auto-response:
            </td>
            <td>
                <input type="checkbox" name="noautoresp" value="1" <?php echo $info['noautoresp']?'checked="checked"':''; ?> >
                    <strong>Disable</strong> new ticket auto-response
                    <i class="help-tip icon-question-sign" href="#ticket_auto_response"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Admin Notes</strong>: Internal notes about the help topic.</em>
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
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="helptopics.php"'>
</p>
</form>
