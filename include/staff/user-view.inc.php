<?php
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($user)) die('Invalid path');

$account = $user->getAccount();
$org = $user->getOrganization();


?>
<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="50%" class="has_bottom_border">
             <h2><a href="users.php?id=<?php echo $user->getId(); ?>"
             title="Reload"><i class="icon-refresh"></i> <?php echo Format::htmlchars($user->getName()); ?></a></h2>
        </td>
        <td width="50%" class="right_align has_bottom_border">
            <span class="action-button pull-right" data-dropdown="#action-dropdown-more">
                <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i> <?php echo __('More'); ?></span>
            </span>
            <a id="user-delete" class="action-button pull-right user-action"
            href="#users/<?php echo $user->getId(); ?>/delete"><i class="icon-trash"></i>
            <?php echo __('Delete User'); ?></a>
            <?php
            if ($account) { ?>
            <a id="user-manage" class="action-button pull-right user-action"
            href="#users/<?php echo $user->getId(); ?>/manage"><i class="icon-edit"></i>
            <?php echo __('Manage Account'); ?></a>
            <?php
            } else { ?>
            <a id="user-register" class="action-button pull-right user-action"
            href="#users/<?php echo $user->getId(); ?>/register"><i class="icon-edit"></i>
            <?php echo __('Register'); ?></a>
            <?php
            } ?>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
              <ul>
                <?php
                if ($account) {
                    if (!$account->isConfirmed()) {
                        ?>
                    <li><a class="confirm-action" href="#confirmlink"><i
                        class="icon-envelope"></i>
                        <?php echo __('Send Activation Email'); ?></a></li>
                    <?php
                    } else { ?>
                    <li><a class="confirm-action" href="#pwreset"><i
                        class="icon-envelope"></i>
                        <?php echo __('Send Password Reset Email'); ?></a></li>
                    <?php
                    } ?>
                    <li><a class="user-action"
                        href="#users/<?php echo $user->getId(); ?>/manage/access"><i
                        class="icon-lock"></i>
                        <?php echo __('Manage Account Access'); ?></a></li>
                <?php

                } ?>
                <li><a href="#ajax.php/users/<?php echo $user->getId();
                    ?>/forms/manage" onclick="javascript:
                    $.dialog($(this).attr('href').substr(1), 201);
                    return false"
                    ><i class="icon-paste"></i>
                    <?php echo __('Manage Forms'); ?></a></li>

              </ul>
            </div>
        </td>
    </tr>
</table>
<table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <tr>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th width="150"><?php echo __('Name'); ?>:</th>
                    <td><b><a href="#users/<?php echo $user->getId();
                    ?>/edit" class="user-action"><i
                    class="icon-edit"></i>&nbsp;<?php echo
                    Format::htmlchars($user->getName()->getOriginal());
                    ?></a></td>
                </tr>
                <tr>
                    <th><?php echo __('Email'); ?>:</th>
                    <td>
                        <span id="user-<?php echo $user->getId(); ?>-email"><?php echo $user->getEmail(); ?></span>
                    </td>
                </tr>
                <tr>
                    <th><?php echo __('Organization'); ?>:</th>
                    <td>
                        <span id="user-<?php echo $user->getId(); ?>-org">
                        <?php
                            if ($org)
                                echo sprintf('<a href="#users/%d/org" class="user-action">%s</a>',
                                        $user->getId(), $org->getName());
                            else
                                echo sprintf('<a href="#users/%d/org"
                                        class="user-action">Add Organization</a>',
                                        $user->getId());
                        ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th width="150"><?php echo __('Status'); ?>:</th>
                    <td> <span id="user-<?php echo $user->getId();
                    ?>-status"><?php echo $user->getAccountStatus(); ?></span></td>
                </tr>
                <tr>
                    <th><?php echo __('Created'); ?>:</th>
                    <td><?php echo Format::db_datetime($user->getCreateDate()); ?></td>
                </tr>
                <tr>
                    <th><?php echo __('Updated'); ?>:</th>
                    <td><?php echo Format::db_datetime($user->getUpdateDate()); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<div class="clear"></div>
<ul class="tabs">
    <li><a class="active" id="tickets_tab" href="#tickets"><i
    class="icon-list-alt"></i>&nbsp;<?php echo __('User Tickets'); ?></a></li>
    <li><a id="notes_tab" href="#notes"><i
    class="icon-pushpin"></i>&nbsp;<?php echo __('Notes'); ?></a></li>
</ul>
<div id="tickets" class="tab_content">
<?php
include STAFFINC_DIR . 'templates/tickets.tmpl.php';
?>
</div>

<div class="tab_content" id="notes" style="display:none">
<?php
$notes = QuickNote::forUser($user);
$create_note_url = 'users/'.$user->getId().'/note';
include STAFFINC_DIR . 'templates/notes.tmpl.php';
?>
</div>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="banemail-confirm">
        <?php echo sprintf(__('Are you sure want to <b>ban</b> %s?'), $user->getEmail()); ?>
        <br><br>
        <?php echo __('New tickets from the email address will be auto-rejected.'); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="confirmlink-confirm">
        <?php echo sprintf(__(
        'Are you sure want to send an <b>Account Activation Link</b> to <em> %s </em>?'),
        $user->getEmail()); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="pwreset-confirm">
        <?php echo sprintf(__(
        'Are you sure want to send a <b>Password Reset Link</b> to <em> %s </em>?'),
        $user->getEmail()); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <form action="users.php?id=<?php echo $user->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
        <hr style="margin-top:1em"/>
        <p class="full-width">
            <span class="buttons pull-left">
                <input type="button" value="<?php echo __('Cancel'); ?>" class="close">
            </span>
            <span class="buttons pull-right">
                <input type="submit" value="<?php echo __('OK'); ?>">
            </span>
         </p>
    </form>
    <div class="clear"></div>
</div>

<script type="text/javascript">
$(function() {
    $(document).on('click', 'a.user-action', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.dialog(url, [201, 204], function (xhr) {
            if (xhr.status == 204)
                window.location.href = 'users.php';
            else
                window.location.href = window.location.href;
         }, {
            onshow: function() { $('#user-search').focus(); }
         });
        return false;
    });
});
</script>
