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

<div class="subnav">


                        <div class="float-left subnavtitle">
                        
                            <span ><a href="<?php echo $refresh_url; ?>"
                                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> 
                                </a> &nbsp;
            <?php echo __('Organazations');?>
                                
                                </span>
                        
                       
                       
                        </div>
                         <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                       
                    
                     <?php if ($thisstaff->hasPerm(Organization::PERM_CREATE)) { ?>
                <a class="btn btn-light add-org"
                   href="#" data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Add Organization'); ?>">
                    <i class="fa fa-plus-square"></i>
                    
                </a>
                <?php } ?>
                    
                    
                   
                    
                    <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-placement="bottom" data-toggle="tooltip" 
             title="<?php echo __('More'); ?>"><i class="fa fa-cog"></i>
            </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">
                    
                   <?php
                            if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                            <a class="dropdown-item orgs-action" href="#delete">
                                <i class="icon-trash icon-fixed-width"></i>
                                <?php echo __('Delete'); ?></a>
                            <?php }
                             # end of registration-enabled? ?>
               
                        </div>
                    </div>
                
                         </div>
                        
                        <div class="clearfix"></div>
                        
                  
 </div>

<div class="card-box">

<div class="row">
    <div class="col">
        <div class="float-right">
           <form  class="form-inline" action="orgs.php" method="get" style="padding-bottom: 10px; margin-top: -5px;">
            <?php csrf_token(); ?>
            
             <div class="input-group input-group-sm">
             <input type="hidden" name="a" value="search">
                <input type="text" class="form-control form-control-sm basic-search" id="basic-org-search" name="query"
                          value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                        autocomplete="off" autocorrect="off" autocapitalize="off"  placeholder="Search Organizations">
           
                <button type="submit"  class="input-group-addon" ><i class="fa fa-search"></i>
                </button>
            </div>
        </form>
        </div>
    </div>
</div>




<div class="row">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="clear"></div>
<div>
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
 <table  id="orgs" class="table table-striped table-hover table-condensed table-sm">
    <thead>
        <tr>
            <th>&nbsp;</th>
            <th><a <?php echo $name_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th><a <?php echo $users_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=users"><?php echo __('Users'); ?></a></th>
            <th data-breakpoints="xs sm"><a <?php echo $create_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th data-breakpoints="xs sm"><a <?php echo $update_sort; ?> href="orgs.php?<?php echo $qstr; ?>&sort=update"><?php echo __('Last Updated'); ?></a></th>
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


<div class="row">
    <div class="col">
        <div class="float-left">
        <nav>
        <ul class="pagination">   
            <?php
                echo $pageNav->getPageLinks();
            ?>
        </ul>
        </nav>
        </div>
        <div class="float-left">
        
        <div class="btn btn-icon waves-effect btn-default m-b-5"> 
               <?php
                echo sprintf('<a class="export-csv no-pjax" href="orgs.php?a=export">%s</a>',
                       
                        ('<i class="ti-cloud-down faded"></i>'));
                ?>
        </div>
                <i class=" hidden help-tip icon-question-sign" href="#export"></i>
        </div>
            
           
            <div class="float-right">
                  <span class="faded"><?php echo $pageNav->showing(); ?></span>
            </div>  
    </div>
</div>
</form>
</div>
</div>
</div>
</div>
<script type="text/javascript">


jQuery(function($){
	$('#orgs').footable();
});

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
