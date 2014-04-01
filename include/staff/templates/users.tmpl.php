<?php

$qstr='';
$select = 'SELECT user.*, email.address as email ';

$from = 'FROM '.USER_TABLE.' user '
      . 'LEFT JOIN '.USER_ACCOUNT_TABLE.' account ON (user.id = account.user_id) '
      . 'LEFT JOIN '.USER_EMAIL_TABLE.' email ON (user.id = email.user_id) ';

$where='WHERE account.org_id='.db_input($org->getId());


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

$showing = $search ? 'Search Results: ' : '';
$res = db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing .= $pageNav->showing();
else
    $showing .= 'No users found!';

?>
<div style="width:700px; float:left;"><b><?php echo $showing; ?></b></div>
<div style="float:right;text-align:right;padding-right:5px;">
    <b><a href="#orgs/<?php echo $org->getId(); ?>/add-user" class="Icon newstaff add-user">Add New User</a></b></div>
<div class="clear"></div>
<br/>
<form action="users.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="350"> Name</th>
            <th width="300"> Email</th>
            <th width="100"> Status</th>
            <th width="100"> Created</th>
        </tr>
    </thead>
    <tbody>
    <?php
        if($res && db_num_rows($res)):
            $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
            while ($row = db_fetch_array($res)) {

                $name = new PersonsName($row['name']);
                $status = 'Active';
                $sel=false;
                if($ids && in_array($row['id'], $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $row['id']; ?>">
                <td>&nbsp;
                    <a href="users.php?id=<?php echo $row['id']; ?>"><?php echo $name; ?></a>
                    &nbsp;
                    <?php
                    if ($row['tickets'])
                         echo sprintf('<i class="icon-fixed-width icon-file-text-alt"></i>
                             <small>(%d)</small>', $row['tickets']);
                    ?>
                </td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $status; ?></td>
                <td><?php echo Format::db_date($row['created']); ?></td>
               </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="4"> &nbsp; </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
endif;
?>
</form>

<script type="text/javascript">
$(function() {
    $(document).on('click', 'a.add-user', function(e) {
        e.preventDefault();
        $.userLookup('ajax.php/users/add', function (user) {
            window.location.href = 'users.php?id='+user.id;
         });

        return false;
     });
});
</script>

