<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info=array();
$qstr='';
if($group && $_REQUEST['a']!='add'){
    $title='Update Group';
    $action='update';
    $submit_text='Save Changes';
    $info=$group->getInfo();
    $info['id']=$group->getId();
    $info['depts']=$group->getDepartments();
    $qstr.='&id='.$group->getId();
}else {
    $title='Add New Group';
    $action='create';
    $submit_text='Create Group';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $info['can_create_tickets']=isset($info['can_create_tickets'])?$info['can_create_tickets']:1;
    $qstr.='&a='.$_REQUEST['a'];
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="groups.php?<?php echo $qstr; ?>" method="post" id="save" name="group">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>User Group</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><strong>Group Information</strong>: Disabled group will limit staff members access. Admins are exempted.</em>
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
                Status:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong>Active</strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>><strong>Disabled</strong>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['status']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Group Permissions</strong>: Applies to all group members&nbsp;</em>
            </th>
        </tr>
        <tr><td>Can <b>Create</b> Tickets</td>
            <td>
                <input type="radio" name="can_create_tickets"  value="1"   <?php echo $info['can_create_tickets']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_create_tickets"  value="0"   <?php echo !$info['can_create_tickets']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to open tickets on behalf of clients.</i>
            </td>
        </tr>
        <tr><td>Can <b>Edit</b> Tickets</td>
            <td>
                <input type="radio" name="can_edit_tickets"  value="1"   <?php echo $info['can_edit_tickets']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_edit_tickets"  value="0"   <?php echo !$info['can_edit_tickets']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to edit tickets.</i>
            </td>
        </tr>
        <tr><td>Can <b>Post Reply</b></td>
            <td>
                <input type="radio" name="can_post_ticket_reply"  value="1"   <?php echo $info['can_post_ticket_reply']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_post_ticket_reply"  value="0"   <?php echo !$info['can_post_ticket_reply']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to post a ticket reply.</i>
            </td>
        </tr>
        <tr><td>Can <b>Close</b> Tickets</td>
            <td>
                <input type="radio" name="can_close_tickets"  value="1" <?php echo $info['can_close_tickets']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_close_tickets"  value="0" <?php echo !$info['can_close_tickets']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to close tickets. Staff can still post a response.</i>
            </td>
        </tr>
        <tr><td>Can <b>Assign</b> Tickets</td>
            <td>
                <input type="radio" name="can_assign_tickets"  value="1" <?php echo $info['can_assign_tickets']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_assign_tickets"  value="0" <?php echo !$info['can_assign_tickets']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to assign tickets to staff members.</i>
            </td>
        </tr>
        <tr><td>Can <b>Transfer</b> Tickets</td>
            <td>
                <input type="radio" name="can_transfer_tickets"  value="1" <?php echo $info['can_transfer_tickets']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_transfer_tickets"  value="0" <?php echo !$info['can_transfer_tickets']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to transfer tickets between departments.</i>
            </td>
        </tr>
        <tr><td>Can <b>Delete</b> Tickets</td>
            <td>
                <input type="radio" name="can_delete_tickets"  value="1"   <?php echo $info['can_delete_tickets']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_delete_tickets"  value="0"   <?php echo !$info['can_delete_tickets']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to delete tickets (Deleted tickets can't be recovered!)</i>
            </td>
        </tr>
        <tr><td>Can Ban Emails</td>
            <td>
                <input type="radio" name="can_ban_emails"  value="1" <?php echo $info['can_ban_emails']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_ban_emails"  value="0" <?php echo !$info['can_ban_emails']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to add/remove emails from banlist via ticket interface.</i>
            </td>
        </tr>
        <tr><td>Can Manage Premade</td>
            <td>
                <input type="radio" name="can_manage_premade"  value="1" <?php echo $info['can_manage_premade']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_manage_premade"  value="0" <?php echo !$info['can_manage_premade']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to add/update/disable/delete canned responses and attachments.</i>
            </td>
        </tr>
        <tr><td>Can Manage FAQ</td>
            <td>
                <input type="radio" name="can_manage_faq"  value="1" <?php echo $info['can_manage_faq']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_manage_faq"  value="0" <?php echo !$info['can_manage_faq']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to add/update/disable/delete knowledgebase categories and FAQs.</i>
            </td>
        </tr>
        <tr><td>Can View Staff Stats.</td>
            <td>
                <input type="radio" name="can_view_staff_stats"  value="1" <?php echo $info['can_view_staff_stats']?'checked="checked"':''; ?> />Yes
                &nbsp;&nbsp;
                <input type="radio" name="can_view_staff_stats"  value="0" <?php echo !$info['can_view_staff_stats']?'checked="checked"':''; ?> />No
                &nbsp;&nbsp;<i>Ability to view stats of other staff members in allowed departments.</i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Department Access</strong>: Check all departments the group members are allowed to access.&nbsp;&nbsp;&nbsp;<a id="selectAll" href="#deptckb">Select All</a>&nbsp;&nbsp;<a id="selectNone" href="#deptckb">Select None</a>&nbsp;&nbsp;</em>
            </th>
        </tr>
        <?php
         $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name';
         if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id,$name) = db_fetch_row($res)){
                $ck=($info['depts'] && in_array($id,$info['depts']))?'checked="checked"':'';
                echo sprintf('<tr><td colspan=2>&nbsp;&nbsp;<input type="checkbox" class="deptckb" name="depts[]" value="%d" %s>%s</td></tr>',$id,$ck,$name);
            }
         }
        ?>
        <tr>
            <th colspan="2">
                <em><strong>Admin Notes</strong>: Internal notes viewable by all admins.&nbsp;</em>
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
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="groups.php"'>
</p>
</form>
