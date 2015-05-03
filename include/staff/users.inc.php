<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qs = array();

$select = 'SELECT user.*, email.address as email, org.name as organization
          , account.id as account_id, account.status as account_status ';

$from = 'FROM '.USER_TABLE.' user '
      . 'LEFT JOIN '.USER_EMAIL_TABLE.' email ON (user.id = email.user_id) '
      . 'LEFT JOIN '.ORGANIZATION_TABLE.' org ON (user.org_id = org.id) '
      . 'LEFT JOIN '.USER_ACCOUNT_TABLE.' account ON (account.user_id = user.id) ';

$where='WHERE 1 ';


if ($_REQUEST['query']) {

    $from .=' LEFT JOIN '.FORM_ENTRY_TABLE.' entry
                ON (entry.object_type=\'U\' AND entry.object_id = user.id)
              LEFT JOIN '.FORM_ANSWER_TABLE.' value
                ON (value.entry_id=entry.id) ';

    $search = db_input(strtolower($_REQUEST['query']), false);
    $where .= ' AND (
                    email.address LIKE \'%'.$search.'%\'
                    OR user.name LIKE \'%'.$search.'%\'
                    OR org.name LIKE \'%'.$search.'%\'
                    OR value.value LIKE \'%'.$search.'%\'
                )';

    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array('name' => 'user.name',
                     'email' => 'email.address',
                     'status' => 'account_status',
                     'create' => 'user.created',
                     'update' => 'user.updated');
$orderWays = array('DESC'=>'DESC','ASC'=>'ASC');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column =$sortOptions[$sort];

$order_column = $order_column ?: 'user.name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

$order=$order ?: 'ASC';
if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(DISTINCT user.id) '.$from.' '.$where);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('users.php', $qs);
$qstr.='&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');

$select .= ', count(DISTINCT ticket.ticket_id) as tickets ';

$from .= ' LEFT JOIN '.TICKET_TABLE.' ticket ON (ticket.user_id = user.id) ';


$query="$select $from $where GROUP BY user.id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$qhash = md5($query);
$_SESSION['users_qs_'.$qhash] = $query;

?>
<h2><?php echo __('User Directory'); ?></h2>
<div class="pull-left">
    <form action="users.php" method="get">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="search">
        <table>
            <tr>
                <td><input type="text" id="basic-user-search" name="query" size=30 value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
                <td><input type="submit" name="basic_search" class="button" value="<?php echo __('Search'); ?>"></td>
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
            </tr>
        </table>
    </form>
 </div>

<div class="pull-right">
    <a class="action-button popup-dialog"
        href="#users/add">
        <i class="icon-plus-sign"></i>
        <?php echo __('Add User'); ?>
    </a>
    <a class="action-button popup-dialog"
        href="#users/import">
        <i class="icon-upload"></i>
        <?php echo __('Import'); ?>
    </a>
    <span class="action-button" data-dropdown="#action-dropdown-more"
        style="/*DELME*/ vertical-align:top; margin-bottom:0">
        <i class="icon-caret-down pull-right"></i>
        <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
    </span>
    <div id="action-dropdown-more" class="action-dropdown anchor-right">
        <ul>
            <li><a class="users-action" href="#delete">
                <i class="icon-trash icon-fixed-width"></i>
                <?php echo __('Delete'); ?></a></li>
            <li><a href="#orgs/lookup/form" onclick="javascript:
$.dialog('ajax.php/orgs/lookup/form', 201);
return false;">
                <i class="icon-group icon-fixed-width"></i>
                <?php echo __('Add to Organization'); ?></a></li>
<?php
if ('disabled' != $cfg->getClientRegistrationMode()) { ?>
            <li><a class="users-action" href="#reset">
                <i class="icon-envelope icon-fixed-width"></i>
                <?php echo __('Send Password Reset Email'); ?></a></li>
            <li><a class="users-action" href="#register">
                <i class="icon-smile icon-fixed-width"></i>
                <?php echo __('Register'); ?></a></li>
            <li><a class="users-action" href="#lock">
                <i class="icon-lock icon-fixed-width"></i>
                <?php echo __('Lock'); ?></a></li>
            <li><a class="users-action" href="#unlock">
                <i class="icon-unlock icon-fixed-width"></i>
                <?php echo __('Unlock'); ?></a></li>
<?php } # end of registration-enabled? ?>
        </ul>
    </div>
</div>

<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
$res = db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing .= $pageNav->showing();
else
    $showing .= __('No users found!');
?>
<form id="users-list" action="users.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <input type="hidden" id="selected-count" name="count" value="" >
 <input type="hidden" id="org_id" name="org_id" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th nowrap width="12"> </th>
            <th width="350"><a <?php echo $name_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="250"><a  <?php echo $status_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=status"><?php echo __('Status'); ?></a></th>
            <th width="100"><a <?php echo $create_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th width="145"><a <?php echo $update_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=update"><?php echo __('Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        if($res && db_num_rows($res)):
            $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
            while ($row = db_fetch_array($res)) {
                // Default to email address mailbox if no name specified
                if (!$row['name'])
                    list($name) = explode('@', $row['email']);
                else
                    $name = new PersonsName($row['name']);

                // Account status
                if ($row['account_id'])
                    $status = new UserAccountStatus($row['account_status']);
                else
                    $status = __('Guest');

                $sel=false;
                if($ids && in_array($row['id'], $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $row['id']; ?>">
                <td nowrap>
                    <input type="checkbox" value="<?php echo $row['id']; ?>" class="ckb mass nowarn"/>
                </td>
                <td>&nbsp;
                    <a class="userPreview" href="users.php?id=<?php echo $row['id']; ?>"><?php
                        echo Format::htmlchars($name); ?></a>
                    &nbsp;
                    <?php
                    if ($row['tickets'])
                         echo sprintf('<i class="icon-fixed-width icon-file-text-alt"></i>
                             <small>(%d)</small>', $row['tickets']);
                    ?>
                </td>
                <td><?php echo $status; ?></td>
                <td><?php echo Format::db_date($row['created']); ?></td>
                <td><?php echo Format::db_datetime($row['updated']); ?>&nbsp;</td>
               </tr>
            <?php
            } //end of while.
        endif; ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($res && $num) { ?>
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
if($res && $num): //Show options..
    echo sprintf('<div>&nbsp;'.__('Page').': %s &nbsp; <a class="no-pjax"
            href="users.php?a=export&qh=%s">'.__('Export').'</a></div>',
            $pageNav->getPageLinks(),
            $qhash);
endif;
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
          var submit = function() {
            $form.find('#action').val(action);
            $.each(ids, function() { $form.append($('<input type="hidden" name="ids[]">').val(this)); });
            $form.find('#selected-count').val(ids.length);
            $form.submit();
          };
          if (!confirmed)
              $.confirm(__('You sure?')).then(submit);
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
    $(document).on('dialog:close', function(e, json) {
        $form = $('form#users-list');
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
});
</script>

