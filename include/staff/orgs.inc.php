<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

OrganizationForm::ensureDynamicDataView();

$qs = array();
$orgs = Organization::objects()
    ->annotate(array('user_count'=>SqlAggregate::COUNT('users')));

if ($_REQUEST['query']) {
    $search = $_REQUEST['query'];
    $orgs->filter(Q::any(array(
        'name__contains' => $search,
        // TODO: Add search for cdata
    )));
    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array(
        'name' => 'name',
        'users' => 'users',
        'create' => 'created',
        'update' => 'updated'
        );

$orderWays = array('DESC' => '-', 'ASC' => '');
$sort= ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ? strtolower($_REQUEST['sort']) : 'name';
//Sorting options...
if ($sort && $sortOptions[$sort])
    $order_column = $sortOptions[$sort];

$order_column = $order_column ?: 'name';

if ($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order = $orderWays[strtoupper($_REQUEST['order'])];

if ($order_column && strpos($order_column,','))
    $order_column = str_replace(','," $order,",$order_column);

$x=$sort.'_sort';
$$x=' class="'.($order == '' ? 'asc' : 'desc').'" ';
$order_by="$order_column $order ";

$total = $orgs->count();
$page=($_GET['p'] && is_numeric($_GET['p']))? $_GET['p'] : 1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->paginate($orgs);

$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('orgs.php', $qs);
$qstr.='&amp;order='.($order=='-' ? 'ASC' : 'DESC');

//echo $query;
$_SESSION[':Q:orgs'] = $orgs;

$orgs->values('id', 'name', 'created', 'updated');
$orgs->order_by($order . $order_column);
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
if ($orgs->exists(true))
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
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($orgs as $org) {

            $sel=false;
            if($ids && in_array($org['id'], $ids))
                $sel=true;
            ?>
           <tr id="<?php echo $org['id']; ?>">
            <td nowrap align="center">
                <input type="checkbox" value="<?php echo $org['id']; ?>" class="ckb mass nowarn"/>
            </td>
            <td>&nbsp; <a href="orgs.php?id=<?php echo $org['id']; ?>"><?php
            echo $org['name']; ?></a> </td>
            <td>&nbsp;<?php echo $org['user_count']; ?></td>
            <td><?php echo Format::date($org['created']); ?></td>
            <td><?php echo Format::datetime($org['updated']); ?>&nbsp;</td>
           </tr>
        <?php
        }
        ?>
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
if ($total): //Show options..
    echo sprintf('<div>&nbsp;%s: %s &nbsp; <a class="no-pjax"
            href="orgs.php?a=export">%s</a></div>',
            __('Page'),
            $pageNav->getPageLinks(),
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
