<?php
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($user)) die('Invalid path');

$account = $user->getAccount();
$org = $user->getOrganization();


?>

<div class="subnav">
   
      
       <div class="float-left subnavtitle">
      
      <a href="users.php?id=<?php echo $user->getId(); ?>"
             title="Reload"><i class="icon-refresh"></i> <?php echo Format::htmlchars($user->getName()); ?></a>
      </a>
      </div>
      
     
                <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                   

                  <?php if ($thisstaff->hasPerm(User::PERM_MANAGE)) { 
            if ($account) { ?>
            <a id="user-manage" class="btn btn-light user-action"
            href="#users/<?php echo $user->getId(); ?>/manage"  data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Manage'); ?>"><i class="icon-edit"></i>
           </a>
            <?php
            } else { ?>
            <a id="user-register" class="btn btn-light user-action"
            href="#users/<?php echo $user->getId(); ?>/register"  data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Register'); ?>"><i class="icon-smile"></i>
           </a>
            <?php
            } ?>
<?php } ?>
                    
            <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
            data-toggle="dropdown"><i class="fa fa-cog" data-placement="bottom" data-toggle="tooltip" 
             title="<?php echo __('More'); ?>"></i>
            </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">
                    
                    <?php if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                <a href="#ajax.php/users/<?php echo $user->getId();
                    ?>/forms/manage" onclick="javascript:
                    $.dialog($(this).attr('href').substr(1), 201);
                    return false"
                    class="dropdown-item"><i class="icon-paste"></i>
                    <?php echo __('Manage Forms'); ?></a>
                    <?php } ?>
                    
                    
                    
                    
                                    <?php
                if ($account) {
                    if (!$account->isConfirmed()) {
                        ?>
                    <a class="dropdown-item confirm-action" href="#confirmlink"><i
                        class="icon-envelope"></i>
                        <?php echo __('Send Activation Email'); ?></a>
                    <?php
                    } else { ?>
                    <a class="dropdown-item confirm-action" href="#pwreset"><i
                        class="icon-envelope"></i>
                        <?php echo __('Send Password Reset Email'); ?></a>
                    <?php
                    } ?>
<?php if ($thisstaff->hasPerm(User::PERM_MANAGE)) { ?>
                    <a class="dropdown-item user-action"
                        href="#users/<?php echo $user->getId(); ?>/manage/access"><i
                        class="icon-lock"></i>
                        <?php echo __('Manage Account Access'); ?></a>
                <?php
}
                } ?>

                        </div>
                    </div>
                    
                    
                    
                                        <?php if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
            <a id="user-delete" class="btn btn-light user-action"
            href="#users/<?php echo $user->getId(); ?>/delete"  data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Delete User'); ?>"><i class="fa fa-trash-o"></i>
            </a>
<?php } ?>
            <a class="btn btn-light"
            href="users.php"  data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Users'); ?>"><i class="fa fa-list-alt"></i>
            </a>
                </div>

    <div class="clearfix"></div>

</div>      
        


<div class="card-box">

<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
<div class="row">   
<div class="col-sm-3">
<div>
    <label> <?php echo __('Name'); ?>: </label>

        <?php
        if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                            <b><a href="#users/<?php echo $user->getId();
                            ?>/edit" class="user-action"><i
                                class="icon-edit"></i>
        <?php }
                            echo Format::htmlchars($user->getName()->getOriginal());
        if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                                </a></b>
        <?php } ?>
</div>
<div>
    <label><?php echo __('Email'); ?>:</label>
    <span id="user-<?php echo $user->getId(); ?>-email"><?php echo $user->getEmail(); ?></span>
</div>

<div>
    <label><?php echo __('Organization'); ?>:</label>
    <span id="user-<?php echo $user->getId(); ?>-org">
    <?php
        if ($org)
            echo sprintf('<a href="#users/%d/org" class="user-action">%s</a>',
                    $user->getId(), $org->getName());
        elseif ($thisstaff->hasPerm(User::PERM_EDIT)) {
            echo sprintf(
                '<a href="#users/%d/org" class="user-action">%s</a>',
                $user->getId(),
                __('Add Organization'));
        }
    ?>
    </span>
</div>

</div>
<div class="col-sm-9">
<div>
    <label><?php echo __('Status'); ?>:</label>
<span id="user-<?php echo $user->getId();
                    ?>-status"><?php echo $user->getAccountStatus(); ?></span>
</div>
<div>
    <label><?php echo __('Created'); ?>:</label>
<?php echo Format::datetime($user->getCreateDate()); ?>
</div>
<div>
    <label><?php echo __('Updated'); ?>:</label>
<?php echo Format::datetime($user->getUpdateDate()); ?>
</div>


</div>
</div>


<!--<div class="avatar pull-left" style="margin: 10px; width: 80px;">
    <?php echo $user->getAvatar(); ?>
</div>-->



</div>
      


     
     
<ul class="nav nav-tabs" role="tablist" style="margin-top:10px;">
  <li class="nav-item">
    <a class="nav-link active" href="#ticket" role="tab" data-toggle="tab"><i class="icon-list-alt"></i>&nbsp;<?php echo __('Tickets'); ?></a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="#note" role="tab" data-toggle="tab"><i class="icon-pushpin"></i>&nbsp;<?php echo __('Notes'); ?></a>
  </li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="ticket">
   <?php
    include STAFFINC_DIR . 'templates/tickets.tmpl.php';
    ?>
  </div>
  <div role="tabpanel" class="tab-pane" id="note">
  <?php
    $notes = QuickNote::forUser($user);
    $create_note_url = 'users/'.$user->getId().'/note';
    include STAFFINC_DIR . 'templates/notes.tmpl.php';
    ?>
  </div>

</div>
     

</div>
<div class="hidden dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="banemail-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>ban</b> %s?'), $user->getEmail()); ?>
        <br><br>
        <?php echo __('New tickets from the email address will be auto-rejected.'); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="confirmlink-confirm">
        <?php echo sprintf(__(
        'Are you sure you want to send an <b>Account Activation Link</b> to <em> %s </em>?'),
        $user->getEmail()); ?>
    </p>
    <p class="confirm-action" style="display:none;" id="pwreset-confirm">
        <?php echo sprintf(__(
        'Are you sure you want to send a <b>Password Reset Link</b> to <em> %s </em>?'),
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
                <input type="button" value="<?php echo __('Cancel'); ?>" class="close btn-danger">
            </span>
            <span class="buttons pull-right">
                <input class="btn btn-primary btn-sm" type="submit" value="<?php echo __('OK'); ?>">
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
            return false;
         }, {
            onshow: function() { $('#user-search').focus(); }
         });
        return false;
    });
});
</script>
