<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qs = array();

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

    $qs += array('query' => $_REQUEST['query']);
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
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('orgs.php', $qs);
$qstr.='&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');

$select .= ', count(DISTINCT user.id) as users ';

$from .= ' LEFT JOIN '.USER_TABLE.' user ON (user.org_id = org.id) ';


$query="$select $from $where GROUP BY org.id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
$qhash = md5($query);
$_SESSION['orgs_qs_'.$qhash] = $query;
?>
<div id="basic_search">
    <div style="min-height:25px;">
        <form action="orgs.php" method="get">
            <?php csrf_token(); ?>
            <div class="attached input">
            <input type="hidden" name="a" value="search">
            <input type="search" class="basic-search" id="basic-org-search" name="query" autofocus size="30" value="<?php echo Format::htmlchars($_REQUEST['query']); ?>" autocomplete="off" autocorrect="off" autocapitalize="off">
                <button type="submit" class="attached button"><i class="icon-search"></i>
                </button>
            <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
            </div>
        </form>
    </div>
</div>
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Organizations'); ?></h2>
            </div>
            <div class="pull-right">
                <?php if ($thisstaff->hasPerm(Organization::PERM_CREATE)) { ?>
                <a class="green button action-button add-org"
                   href="#">
                    <i class="icon-plus-sign"></i>
                    <?php echo __('Add Organization'); ?>
                </a>
                <?php }
            if ($thisstaff->hasPerm(Organization::PERM_DELETE)) { ?>
                <span class="action-button" data-dropdown="#action-dropdown-more"
                      style="/*DELME*/ vertical-align:top; margin-bottom:0">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul>
                        <li class="danger"><a class="orgs-action" href="#delete">
                            <i class="icon-trash icon-fixed-width"></i>
                            <?php echo __('Delete'); ?></a></li>
                    </ul>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
<?php
$showing = $search ? __('Search Results').': ' : '';
$res = db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing .= $pageNav->showing();
else
    $showing .= __('No organizations found!');
?>
<form id="orgs-list" action="orgs.php" method="POST" name="staff" >
 <?php csrf_token(); ?>
 <input type="hidden" name="a" value="mass_process" >
 <input type="hidden" id="action" name="do" value="" >
 <input type="hidden" id="selected-count" name="count" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th nowrap width="4%">&nbsp;</th>
            <th width="45%"><a <?php echo $name_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="11%"><a <?php echo $users_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=users"><?php echo __('Users'); ?></a></th>
            <th width="20%"><a <?php echo $create_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th width="20%"><a <?php echo $update_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=update"><?php echo __('Last Updated'); ?></a></th>
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
                <td nowrap align="center">
                    <input type="checkbox" value="<?php echo $row['id']; ?>" class="ckb mass nowarn"/>
                </td>
                <td>&nbsp; <a href="orgs.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a> </td>
                <td>&nbsp;<?php echo $row['users']; ?></td>
                <td><?php echo Format::date($row['created']); ?></td>
                <td><?php echo Format::datetime($row['updated']); ?>&nbsp;</td>
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
            var url = 'orgs.php?id=' + org.id;
            $.pjax({url: url, container: '#pjax-container'})
         });

        return false;
     });

    var goBaby = function(action) {
        var ids = [],
            $form = $('form#orgs-list');
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
          $.confirm(__('You sure?')).then(submit);
        }
        else if (!ids.length) {
            $.sysAlert(__('Oops'),
                __('You need to select at least one item'));
        }
    };
    $(document).on('click', 'a.orgs-action', function(e) {
        e.preventDefault();
        goBaby($(this).attr('href').substr(1));
        return false;
    });
});
</script>
