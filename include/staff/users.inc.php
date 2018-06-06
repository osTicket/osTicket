<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

// Ensure cdata
UserForm::ensureDynamicDataView();

$qs = array();
$users = User::objects()
    ->annotate(array('ticket_count'=>SqlAggregate::COUNT('tickets')));

if ($_REQUEST['query']) {
    $search = $_REQUEST['query'];
    $users->filter(Q::any(array(
        'emails__address__contains' => $search,
        'name__contains' => $search,
        'org__name__contains' => $search,
        'cdata__phone__contains' => $search,
        // TODO: Add search for cdata
    )));
    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array('name' => 'name',
                     'email' => 'emails__address',
                     'status' => 'account__status',
                     'create' => 'created',
                     'update' => 'updated');
$orderWays = array('DESC'=>'-','ASC'=>'');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column =$sortOptions[$sort];

$order_column = $order_column ?: 'name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.($order == '' ? 'asc' : 'desc').'" ';

$total = $users->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,PAGE_LIMIT);
$pageNav->paginate($users);

$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('users.php', $qs);
$qstr.='&amp;order='.($order=='-' ? 'ASC' : 'DESC');

//echo $query;
$_SESSION[':Q:users'] = $users;

$users->values('id', 'name', 'default_email__address', 'account__id',
    'account__status', 'created', 'updated');
$users->order_by($order . $order_column);
?>
<div id="basic_search">
    <div style="min-height:25px;">
        <form action="users.php" method="get">
            <?php csrf_token(); ?>
            <input type="hidden" name="a" value="search">
            <div class="attached input">
                <input type="text" class="basic-search" id="basic-user-search" name="query"
                         size="30" value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                        autocomplete="off" autocorrect="off" autocapitalize="off">
            <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
                <button type="submit" class="attached button"><i class="icon-search"></i>
                </button>
            </div>
        </form>
    </div>
 </div>
<form id="users-list" action="users.php" method="POST" name="staff" >

<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('User Directory'); ?></h2>
            </div>
            <div class="pull-right">
                <?php if ($thisstaff->hasPerm(User::PERM_CREATE)) { ?>
                <a class="green button action-button popup-dialog"
                   href="#users/add">
                    <i class="icon-plus-sign"></i>
                    <?php echo __('Add User'); ?>
                </a>
                <a class="action-button popup-dialog"
                   href="#users/import">
                    <i class="icon-upload"></i>
                    <?php echo __('Import'); ?>
                </a>
                <?php } ?>
                <span class="action-button" data-dropdown="#action-dropdown-more"
                      style="/*DELME*/ vertical-align:top; margin-bottom:0">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul>
                        <?php if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                        <li><a href="#add-to-org" class="users-action">
                            <i class="icon-group icon-fixed-width"></i>
                            <?php echo __('Add to Organization'); ?></a></li>
                        <?php
                            }
                        if ('disabled' != $cfg->getClientRegistrationMode()) { ?>
                        <li><a class="users-action" href="#reset">
                            <i class="icon-envelope icon-fixed-width"></i>
                            <?php echo __('Send Password Reset Email'); ?></a></li>
                        <?php if ($thisstaff->hasPerm(User::PERM_MANAGE)) { ?>
                        <li><a class="users-action" href="#register">
                            <i class="icon-smile icon-fixed-width"></i>
                            <?php echo __('Register'); ?></a></li>
                        <li><a class="users-action" href="#lock">
                            <i class="icon-lock icon-fixed-width"></i>
                            <?php echo __('Lock'); ?></a></li>
                        <li><a class="users-action" href="#unlock">
                            <i class="icon-unlock icon-fixed-width"></i>
                            <?php echo __('Unlock'); ?></a></li>
                        <?php }
                        if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                        <li class="danger"><a class="users-action" href="#delete">
                            <i class="icon-trash icon-fixed-width"></i>
                            <?php echo __('Delete'); ?></a></li>
                        <?php }
                        } # end of registration-enabled? ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
if($users->exists(true))
    $showing .= $pageNav->showing();
else
    $showing .= __('No users found!');
?>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <input type="hidden" id="selected-count" name="count" value="" >
 <input type="hidden" id="org_id" name="org_id" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th nowrap width="4%">&nbsp;</th>
            <th><a <?php echo $name_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="22%"><a  <?php echo $status_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=status"><?php echo __('Status'); ?></a></th>
            <th width="20%"><a <?php echo $create_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th width="20%"><a <?php echo $update_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=update"><?php echo __('Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($users as $U) {
                // Default to email address mailbox if no name specified
                if (!$U['name'])
                    list($name) = explode('@', $U['default_email__address']);
                else
                    $name = new UsersName($U['name']);

                // Account status
                if ($U['account__id'])
                    $status = new UserAccountStatus($U['account__status']);
                else
                    $status = __('Guest');

                $sel=false;
                if($ids && in_array($U['id'], $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $U['id']; ?>">
                <td nowrap align="center">
                    <input type="checkbox" value="<?php echo $U['id']; ?>" class="ckb mass nowarn"/>
                </td>
                <td>&nbsp;
                    <a class="preview"
                        href="users.php?id=<?php echo $U['id']; ?>"
                        data-preview="#users/<?php echo $U['id']; ?>/preview"><?php
                        echo Format::htmlchars($name); ?></a>
                    &nbsp;
                    <?php
                    if ($U['ticket_count'])
                         echo sprintf('<i class="icon-fixed-width icon-file-text-alt"></i>
                             <small>(%d)</small>', $U['ticket_count']);
                    ?>
                </td>
                <td><?php echo $status; ?></td>
                <td><?php echo Format::date($U['created']); ?></td>
                <td><?php echo Format::datetime($U['updated']); ?>&nbsp;</td>
               </tr>
<?php   } //end of foreach. ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($total) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo '<i>';
                echo __('Query returned 0 results.');
                echo '</i>';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($total) {
    echo sprintf('<div>&nbsp;'.__('Page').': %s &nbsp; <a class="no-pjax"
            href="users.php?a=export&qh=%s">'.__('Export').'</a></div>',
            $pageNav->getPageLinks(),
            $qhash);
}
?>
</form>

<script type="text/javascript">
$(function() {
    $('input#basic-user-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/users/local?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            window.location.href = 'users.php?id='+obj.id;
        },
        property: "/bin/true"
    });

    $(document).on('click', 'a.popup-dialog', function(e) {
        e.preventDefault();
        $.userLookup('ajax.php/' + $(this).attr('href').substr(1), function (user) {
            var url = window.location.href;
            if (user && user.id)
                url = 'users.php?id='+user.id;
            $.pjax({url: url, container: '#pjax-container'})
            return false;
         });

        return false;
    });
    var goBaby = function(action, confirmed) {
        var ids = [],
            $form = $('form#users-list');
        $(':checkbox.mass:checked', $form).each(function() {
            ids.push($(this).val());
        });
        if (ids.length) {
          var submit = function(data) {
            $form.find('#action').val(action);
            $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
            if (data)
              $.each(data, function() { $form.append($('<input type="hidden">').attr('name', this.name).val(this.value)); });
            $form.find('#selected-count').val(ids.length);
            $form.submit();
          };
          var options = {};
          if (action === 'delete') {
              options['deletetickets']
                =  __('Also delete all associated tickets and attachments');
          }
          else if (action === 'add-to-org') {
            $.dialog('ajax.php/orgs/lookup/form', 201, function(xhr, json) {
              var $form = $('form#users-list');
              try {
                  var json = $.parseJSON(json),
                      org_id = $form.find('#org_id');
                  if (json.id) {
                      org_id.val(json.id);
                      goBaby('setorg', true);
                  }
              }
              catch (e) { }
            });
            return;
          }
          if (!confirmed)
              $.confirm(__('You sure?'), undefined, options).then(submit);
          else
              submit();
        }
        else {
            $.sysAlert(__('Oops'),
                __('You need to select at least one item'));
        }
    };
    $(document).on('click', 'a.users-action', function(e) {
        e.preventDefault();
        goBaby($(this).attr('href').substr(1));
        return false;
    });

    // Remove CSRF Token From GET Request
    document.querySelector("form[action='users.php']").onsubmit = function() {
        document.getElementsByName("__CSRFToken__")[0].remove();
    };
});
</script>

