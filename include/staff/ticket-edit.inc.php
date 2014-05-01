<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->canEditTickets() || !$ticket) die('Access Denied');

$info=Format::htmlchars(($errors && $_POST)?$_POST:$ticket->getUpdateInfo());
if ($_POST)
    $info['duedate'] = Format::date($cfg->getDateFormat(),
       strtotime($info['duedate']));
?>
<form action="tickets.php?id=<?php echo $ticket->getId(); ?>&a=edit" method="post" id="save"  enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="update">
 <input type="hidden" name="a" value="edit">
 <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
 <h2>Update Ticket #<?php echo $ticket->getNumber(); ?></h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong>User Information</strong>: Currently selected user</em>
            </th>
        </tr>
    <?php
    if(!$info['user_id'] || !($user = User::lookup($info['user_id'])))
        $user = $ticket->getUser();
    ?>
    <tr><td>User:</td><td>
        <div id="client-info">
            <a href="#" onclick="javascript:
                $.userLookup('ajax.php/users/<?php echo $ticket->getOwnerId(); ?>/edit',
                        function (user) {
                            $('#client-name').text(user.name);
                            $('#client-email').text(user.email);
                        });
                return false;
                "><i class="icon-user"></i>
            <span id="client-name"><?php echo Format::htmlchars($user->getName()); ?></span>
            &lt;<span id="client-email"><?php echo $user->getEmail(); ?></span>&gt;
            </a>
            <a class="action-button" style="float:none;overflow:inherit" href="#"
                onclick="javascript:
                    $.userLookup('ajax.php/tickets/<?php echo $ticket->getId(); ?>/change-user',
                            function(user) {
                                $('input#user_id').val(user.id);
                                $('#client-name').text(user.name);
                                $('#client-email').text('<'+user.email+'>');
                    });
                    return false;
                "><i class="icon-edit"></i> Change</a>
            <input type="hidden" name="user_id" id="user_id"
                value="<?php echo $info['user_id']; ?>" />
        </div>
        </td></tr>
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong>Ticket Information</strong>: Due date overrides SLA's grace period.</em>
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
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['source']; ?></font>
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
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['topicId']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160">
                SLA Plan:
            </td>
            <td>
                <select name="slaId">
                    <option value="0" selected="selected" >&mdash; None &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['slaId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['slaId']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160">
                Due Date:
            </td>
            <td>
                <input class="dp" id="duedate" name="duedate" value="<?php echo Format::htmlchars($info['duedate']); ?>" size="12" autocomplete=OFF>
                &nbsp;&nbsp;
                <?php
                $min=$hr=null;
                if($info['time'])
                    list($hr, $min)=explode(':', $info['time']);

                echo Misc::timeDropdown($hr, $min, 'time');
                ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?>&nbsp;<?php echo $errors['time']; ?></font>
                <em>Time is based on your time zone (GMT <?php echo $thisstaff->getTZoffset(); ?>)</em>
            </td>
        </tr>
    </tbody>
</table>
<table class="form_table dynamic-forms" width="940" border="0" cellspacing="0" cellpadding="2">
        <?php if ($forms)
            foreach ($forms as $form) {
                $form->render(true, false, array('mode'=>'edit','width'=>160,'entry'=>$form));
        } ?>
</table>
<table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong>Internal Note</strong>: Reason for editing the ticket (required) <font class="error">&nbsp;<?php echo $errors['note'];?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea class="richtext no-bar" name="note" cols="21"
                    rows="6" style="width:80%;"><?php echo $info['note'];
                    ?></textarea>
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
<div style="display:none;" class="dialog draggable" id="user-lookup">
    <div class="body"></div>
</div>
<script type="text/javascript">
$('table.dynamic-forms').sortable({
  items: 'tbody',
  handle: 'th',
  helper: function(e, ui) {
    ui.children().each(function() {
      $(this).children().each(function() {
        $(this).width($(this).width());
      });
    });
    ui=ui.clone().css({'background-color':'white', 'opacity':0.8});
    return ui;
  }
});
</script>
