<?php
if (!defined('OSTSCPINC')
        || !$ticket
        || !($ticket->checkStaffPerm($thisstaff, Ticket::PERM_EDIT)))
    die('Access Denied');

$info=Format::htmlchars(($errors && $_POST)?$_POST:$ticket->getUpdateInfo(), true);
if ($_POST)
    // Reformat duedate to the display standard (but don't convert to local
    // timezone)
    $info['duedate'] = Format::date(strtotime($info['duedate']), false, false, 'UTC');
?>
<form action="tickets.php?id=<?php echo $ticket->getId(); ?>&a=edit" method="post" class="save"  enctype="multipart/form-data">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="update">
    <input type="hidden" name="a" value="edit">
    <input type="hidden" name="id" value="<?php echo $ticket->getId(); ?>">
    <div style="margin-bottom:20px; padding-top:5px;">
        <div class="pull-left flush-left">
            <h2><?php echo sprintf(__('Update Ticket #%s'),$ticket->getNumber());?></h2>
        </div>
    </div>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong><?php echo __('User Information'); ?></strong>: <?php echo __('Currently selected user'); ?></em>
                </th>
            </tr>
        <?php
    if(!$info['user_id'] || !($user = User::lookup($info['user_id'])))
        $user = $ticket->getUser();
    ?>
    <tr><td><?php echo __('User'); ?>:</td><td>
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
            <a class="inline action-button" style="overflow:inherit" href="#"
                onclick="javascript:
                    $.userLookup('ajax.php/tickets/<?php echo $ticket->getId(); ?>/change-user',
                            function(user) {
                                $('input#user_id').val(user.id);
                                $('#client-name').text(user.name);
                                $('#client-email').text('<'+user.email+'>');
                    });
                    return false;
                "><i class="icon-edit"></i> <?php echo __('Change'); ?></a>
            <input type="hidden" name="user_id" id="user_id"
                value="<?php echo $info['user_id']; ?>" />
        </div>
        </td></tr>
    <tbody>
        <tr>
            <th colspan="2">
            <em><strong><?php echo __('Ticket Information'); ?></strong>: <?php echo __("Due date overrides SLA's grace period."); ?></em>
            </th>
        </tr>
        <tr>
            <td width="160" class="required">
                <?php echo __('Ticket Source');?>:
            </td>
            <td>
                <select name="source">
                    <option value="" selected >&mdash; <?php
                        echo __('Select Source');?> &mdash;</option>
                    <?php
                    $source = $info['source'] ?: 'Phone';
                    foreach (Ticket::getSources() as $k => $v) {
                        echo sprintf('<option value="%s" %s>%s</option>',
                                $k,
                                ($source == $k ) ? 'selected="selected"' : '',
                                $v);
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['source']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                <?php echo __('Help Topic');?>:
            </td>
            <td>
                <select name="topicId">
                    <option value="" selected >&mdash; <?php echo __('Select Help Topic');?> &mdash;</option>
                    <?php
                    if ($topics=$thisstaff->getTopicNames()) {
                      if($ticket->topic_id && !array_key_exists($ticket->topic_id, $topics)) {
                        $topics[$ticket->topic_id] = $ticket->topic;
                        $errors['topicId'] = sprintf(__('%s selected must be active'), __('Help Topic'));
                      }
                        foreach($topics as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['topicId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>

                <?php
                if (!$info['topicId'] && $cfg->requireTopicToClose()) {
                ?><i class="icon-warning-sign help-tip warning"
                    data-title="<?php echo __('Required to close ticket'); ?>"
                    data-content="<?php echo __('Data is required in this field in order to close the related ticket'); ?>"
                ></i><?php
                } ?>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['topicId']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160">
                <?php echo __('SLA Plan');?>:
            </td>
            <td>
                <select name="slaId">
                    <option value="0" selected="selected" >&mdash; <?php echo __('None');?> &mdash;</option>
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
                <?php echo __('Due Date');?>:
            </td>
            <td>
                <?php
                $duedateField = Ticket::duedateField('duedate', $info['duedate']);
                $duedateField->render();
                ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?></font>
                <em><?php echo __('Time is based on your time zone');?>
                    (<?php echo $cfg->getTimezone($thisstaff); ?>)</em>
            </td>
        </tr>
    </tbody>
</table>
<table class="form_table dynamic-forms" width="940" border="0" cellspacing="0" cellpadding="2">
        <?php if ($forms)
            foreach ($forms as $form) {
                $form->render(array('staff'=>true,'mode'=>'edit','width'=>160,'entry'=>$form));
        } ?>
</table>
<table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Note');?></strong>: <?php echo __('Reason for editing the ticket (optional)');?> <font class="error">&nbsp;<?php echo $errors['note'];?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea class="richtext no-bar" name="note" cols="21"
                    rows="6" style="width:80%;"><?php echo Format::viewableImages($info['note']);
                    ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo __('Save');?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="tickets.php?id=<?php echo $ticket->getId(); ?>"'>
</p>
</form>
<div style="display:none;" class="dialog draggable" id="user-lookup">
    <div class="body"></div>
</div>
<script type="text/javascript">
+(function() {
  var I = setInterval(function() {
    if (!$.fn.sortable)
      return;
    clearInterval(I);
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
  }, 20);
})();
</script>
