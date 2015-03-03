<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qs = array();

$users = User::objects()
    ->annotate(array('ticket_count'=>SqlAggregate::COUNT('tickets')));

if ($_REQUEST['query']) {
    $search = $_REQUEST['query'];
    $users->filter(Q::any(array(
        'emails__address__contains' => $search,
        'name__contains' => $search,
        'org__name__contains' => $search,
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
$_SESSION[':Q:users'] = clone $users;

$users->values('id', 'name', 'default_email__address', 'account__id',
    'account__status', 'created', 'updated');
$users->order_by($order . $order_column);
?>
<h2><?php echo __('User Directory'); ?></h2>
<div class="pull-left" style="width:700px;">
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
 <div class="pull-right flush-right" style="padding-right:5px;">
    <b><a href="#users/add" class="Icon newstaff popup-dialog"><?php echo __('Add User'); ?></a></b>
    |
    <b><a href="#users/import" class="popup-dialog"><i class="icon-cloud-upload icon-large"></i>
    <?php echo __('Import'); ?></a></b>
</div>
<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
if($users->exists(true))
    $showing .= $pageNav->showing();
else
    $showing .= __('No users found!');
?>
<form action="users.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
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
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($users as $U) {
                // Default to email address mailbox if no name specified
                if (!$U['name'])
                    list($name) = explode('@', $U['default_email__address']);
                else
                    $name = new PersonsName($U['name']);

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
            if (user && user.id)
                window.location.href = 'users.php?id='+user.id;
            else
              $.pjax({url: window.location.href, container: '#pjax-container'})
         });

        return false;
     });
});
</script>

