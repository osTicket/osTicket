<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qstr='';

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

    $qstr.='&query='.urlencode($_REQUEST['query']);
}

$sortOptions = array('name' => 'user.name',
                     'email' => 'email.address',
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
$pageNav->setURL('users.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');

$select .= ', count(DISTINCT ticket.ticket_id) as tickets ';

$from .= ' LEFT JOIN '.TICKET_TABLE.' ticket ON (ticket.user_id = user.id) ';


$query="$select $from $where GROUP BY user.id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$qhash = md5($query);
$_SESSION['users_qs_'.$qhash] = $query;

?>
<h2>User Directory</h2>
<div style="width:700px; float:left;">
    <form action="users.php" method="get">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="search">
        <table>
            <tr>
                <td><input type="text" id="basic-user-search" name="query" size=30 value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
                <td><input type="submit" name="basic_search" class="button" value="Search"></td>
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
            </tr>
        </table>
    </form>
 </div>
 <div style="float:right;text-align:right;padding-right:5px;">
    <b><a href="#users/add" class="Icon newstaff popup-dialog">Add User</a></b>
    |
    <b><a href="#users/import" class="popup-dialog"><i class="icon-cloud-upload icon-large"></i> Import</a></b>
</div>
<div class="clear"></div>
<?php
$showing = $search ? 'Search Results: ' : '';
$res = db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing .= $pageNav->showing();
else
    $showing .= 'No users found!';
?>
<form action="users.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="350"><a <?php echo $name_sort; ?> href="users.php?<?php echo $qstr; ?>&sort=name">Name</a></th>
            <th width="250"><a  <?php echo $status_sort; ?> href="users.php?<?php echo $qstr; ?>&sort=status">Status</a></th>
            <th width="100"><a <?php echo $create_sort; ?> href="users.php?<?php echo $qstr; ?>&sort=create">Created</a></th>
            <th width="145"><a <?php echo $update_sort; ?> href="users.php?<?php echo $qstr; ?>&sort=update">Updated</a></th>
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
                    $status = 'Guest';

                $sel=false;
                if($ids && in_array($row['id'], $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $row['id']; ?>">
                <td>&nbsp;
                    <a class="userPreview" href="users.php?id=<?php echo $row['id']; ?>"><?php echo $name; ?></a>
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
</table>
<?php
if($res && $num): //Show options..
    echo sprintf('<div>&nbsp;Page: %s &nbsp; <a class="no-pjax"
            href="users.php?a=export&qh=%s">Export</a></div>',
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
            if (user && user.id)
                window.location.href = 'users.php?id='+user.id;
            else
              $.pjax({url: window.location.href, container: '#pjax-container'})
         });

        return false;
     });
});
</script>

