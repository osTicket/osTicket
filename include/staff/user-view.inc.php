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
            <span class="action-button" data-dropdown="#action-dropdown-more">
                <span ><i class="icon-cog"></i> More</span>
                <i class="icon-caret-down"></i>
            </span>
            <a id="user-delete" class="action-button user-action"
            href="#users/<?php echo $user->getId(); ?>/delete"><i class="icon-trash"></i> Delete User</a>
            <?php
            if ($account) { ?>
            <a id="user-manage" class="action-button user-action"
            href="#users/<?php echo $user->getId(); ?>/manage"><i class="icon-edit"></i> Manage Account</a>
            <?php
            } else { ?>
            <a id="user-register" class="action-button user-action"
            href="#users/<?php echo $user->getId(); ?>/register"><i class="icon-edit"></i> Register</a>
            <?php
            } ?>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
              <ul>
                <?php
                if ($account) {
                    if (!$account->isConfirmed()) {
                        ?>
                    <li><a class="confirm-action" href="#confirmlink"><i
                        class="icon-envelope"></i> Send Activation Email</a></li>
                    <?php
                    } else { ?>
                    <li><a class="confirm-action" href="#pwreset"><i
                        class="icon-envelope"></i> Send Password Reset Email</a></li>
                    <?php
                    } ?>
                    <li><a class="user-action"
                        href="#users/<?php echo $user->getId(); ?>/manage/access"><i
                        class="icon-lock"></i> Manage Account Access</a></li>
                <?php

                } ?>
                <li><a href="#ajax.php/users/<?php echo $user->getId();
                    ?>/forms/manage" onclick="javascript:
                    $.dialog($(this).attr('href').substr(1), 201);
                    return false"
                    ><i class="icon-paste"></i> Manage Forms</a></li>

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
                    <th width="150">Name:</th>
                    <td><b><a href="#users/<?php echo $user->getId();
                    ?>/edit" class="user-action"><i
                    class="icon-edit"></i>&nbsp;<?php echo
                    Format::htmlchars($user->getName()->getOriginal());
                    ?></a></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>
                        <span id="user-<?php echo $user->getId(); ?>-email"><?php echo $user->getEmail(); ?></span>
                    </td>
                </tr>
                <tr>
                    <th>Organization:</th>
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
                    <th width="150">Status:</th>
                    <td> <span id="user-<?php echo $user->getId();
                    ?>-status"><?php echo $user->getAccountStatus(); ?></span></td>
                </tr>
                <tr>
                    <th>Created:</th>
                    <td><?php echo Format::db_datetime($user->getCreateDate()); ?></td>
                </tr>
                <tr>
                    <th>Updated:</th>
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
    class="icon-list-alt"></i>&nbsp;User Tickets</a></li>
    <li><a id="notes_tab" href="#notes"><i
    class="icon-pushpin"></i>&nbsp;Notes</a></li>
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
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="banemail-confirm">
        Are you sure want to <b>ban</b> <?php echo $user->getEmail(); ?>? <br><br>
        New tickets from the email address will be auto-rejected.
    </p>
    <p class="confirm-action" style="display:none;" id="confirmlink-confirm">
        Are you sure want to send <b>Account Activation Link</b> to <em><?php echo $user->getEmail()?></em>?
    </p>
    <p class="confirm-action" style="display:none;" id="pwreset-confirm">
        Are you sure want to send <b>Password Reset Link</b> to <em><?php echo $user->getEmail()?></em>?
    </p>
    <div>Please confirm to continue.</div>
    <form action="users.php?id=<?php echo $user->getId(); ?>" method="post" id="confirm-form" name="confirm-form">
        <?php csrf_token(); ?>
        <input type="hidden" name="id" value="<?php echo $user->getId(); ?>">
        <input type="hidden" name="a" value="process">
        <input type="hidden" name="do" id="action" value="">
        <hr style="margin-top:1em"/>
        <p class="full-width">
            <span class="buttons" style="float:left">
                <input type="button" value="Cancel" class="close">
            </span>
            <span class="buttons" style="float:right">
                <input type="submit" value="OK">
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
