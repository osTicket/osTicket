<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qstr='';

$select = 'SELECT org.*
            ,COALESCE(team.name,
                    IF(staff.staff_id, CONCAT_WS(" ", staff.firstname, staff.lastname), NULL)
                    ) as account_manager ';
$from = 'FROM '.ORGANIZATION_TABLE.' org '
       .'LEFT JOIN '.STAFF_TABLE.' staff ON (
           LEFT(org.manager, 1) = "s" AND staff.staff_id = SUBSTR(org.manager, 2)) '
       .'LEFT JOIN '.TEAM_TABLE.' team ON (
           LEFT(org.manager, 1) = "t" AND team.team_id = SUBSTR(org.manager, 2)) ';

$where = ' WHERE 1 ';

if ($_REQUEST['query']) {

    $from .=' LEFT JOIN '.FORM_ENTRY_TABLE.' entry
                ON (entry.object_type=\'O\' AND entry.object_id = org.id)
              LEFT JOIN '.FORM_ANSWER_TABLE.' value
                ON (value.entry_id=entry.id) ';

    $search = db_input(strtolower($_REQUEST['query']), false);
    $where .= ' AND (
                    org.name LIKE \'%'.$search.'%\' OR value.value LIKE \'%'.$search.'%\'
                )';

    $qstr.='&query='.urlencode($_REQUEST['query']);
}

$sortOptions = array('name' => 'org.name',
                     'users' => 'users',
                     'create' => 'org.created',
                     'update' => 'org.updated');
$orderWays = array('DESC'=>'DESC','ASC'=>'ASC');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column =$sortOptions[$sort];

$order_column = $order_column ?: 'org.name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

$order=$order ?: 'ASC';
if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(DISTINCT org.id) '.$from.' '.$where);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total,$page,PAGE_LIMIT);
$pageNav->setURL('orgs.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');

$select .= ', count(DISTINCT user.id) as users ';

$from .= ' LEFT JOIN '.USER_TABLE.' user ON (user.org_id = org.id) ';


$query="$select $from $where GROUP BY org.id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$qhash = md5($query);
$_SESSION['orgs_qs_'.$qhash] = $query;
?>
<h2><?php echo __('Organizations'); ?></h2>
<div class="pull-left" style="width:700px;">
    <form action="orgs.php" method="get">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="search">
        <table>
            <tr>
                <td><input type="text" id="basic-org-search" name="query" size=30 value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
                <td><input type="submit" name="basic_search" class="button" value="<?php echo __('Search'); ?>"></td>
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
            </tr>
        </table>
    </form>
 </div>
 <div class="pull-right flush-right">
    <b><a href="#orgs/add" class="Icon newDepartment add-org"><?php
    echo __('Add New Organization'); ?></a></b></div>
<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
$res = db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing .= $pageNav->showing();
else
    $showing .= __('No organizations found!');
?>
<form action="orgs.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="400"><a <?php echo $name_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="100"><a <?php echo $users_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=users"><?php echo __('Users'); ?></a></th>
            <th width="150"><a <?php echo $create_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th width="145"><a <?php echo $update_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=update"><?php echo __('Last Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        if($res && db_num_rows($res)):
            $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
            while ($row = db_fetch_array($res)) {

                $sel=false;
                if($ids && in_array($row['id'], $ids))
                    $sel=true;
                ?>
               <tr id="<?php echo $row['id']; ?>">
                <td>&nbsp; <a href="orgs.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a> </td>
                <td>&nbsp;<?php echo $row['users']; ?></td>
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
    echo sprintf('<div>&nbsp;%s: %s &nbsp; <a class="no-pjax"
            href="orgs.php?a=export&qh=%s">%s</a></div>',
            __('Page'),
            $pageNav->getPageLinks(),
            $qhash,
            __('Export'));
endif;
?>
</form>

<script type="text/javascript">
$(function() {
    $('input#basic-org-search').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/orgs/search?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            window.location.href = 'orgs.php?id='+obj.id;
        },
        property: "/bin/true"
    });

    $(document).on('click', 'a.add-org', function(e) {
        e.preventDefault();
        $.orgLookup('ajax.php/orgs/add', function (org) {
            window.location.href = 'orgs.php?id='+org.id;
         });

        return false;
     });
});
</script>
