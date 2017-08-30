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
        // TODO: Add search for cdata
    )));
    $qs += array('query' => $_REQUEST['query']);
}

$sortOptions = array('name' => 'name',
                     'email' => 'emails__address',
					 'org' => 'org__name',
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
    'account__status', 'created', 'updated', 'org__name');
$users->order_by($order . $order_column);
?>

<div class="subnav">


                        <div class="float-left subnavtitle">
                        
                            <span ><a href="<?php echo $refresh_url; ?>"
                                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> 
                                </a> &nbsp;
            <?php echo __('User Directory');?>
                                
                                </span>
                        
                       
                       
                        </div>
 
        <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                    <?php if ($thisstaff->hasPerm(User::PERM_CREATE)) { ?>
                    <a class="btn btn-light popup-dialog"
                       href="#users/add" data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Add User'); ?>">
                        <i class="fa fa-plus-square"></i>
                    </a>
                    
                    <a class="btn btn-light popup-dialog"
                       href="#users/import"  data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Import'); ?>">
                        <i class="fa fa-arrow-circle-up"></i>
                        
                    </a>
                    <?php } ?>
                    
                    <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-placement="bottom" data-toggle="tooltip" 
             title="<?php echo __('More'); ?>"><i class="fa fa-cog"></i>
            </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">
                    
                    <?php if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                            <a href="#add-to-org"  class="dropdown-item users-action">
                                <i class="icon-group icon-fixed-width"></i>
                                <?php echo __('Add to Organization'); ?></a>
                            <?php
                                }
                            if ('disabled' != $cfg->getClientRegistrationMode()) { ?>
                            <a class="dropdown-item users-action" href="#reset">
                                <i class="icon-envelope icon-fixed-width"></i>
                                <?php echo __('Send Password Reset Email'); ?></a>
                            <?php if ($thisstaff->hasPerm(User::PERM_MANAGE)) { ?>
                            <a class="dropdown-item users-action" href="#register">
                                <i class="icon-smile icon-fixed-width"></i>
                                <?php echo __('Register'); ?></a>
                            <a class="dropdown-item users-action" href="#lock">
                                <i class="icon-lock icon-fixed-width"></i>
                                <?php echo __('Lock'); ?></a>
                            <a class="dropdown-item users-action" href="#unlock">
                                <i class="icon-unlock icon-fixed-width"></i>
                                <?php echo __('Unlock'); ?></a>
                            <?php }
                            if ($thisstaff->hasPerm(User::PERM_DELETE)) { ?>
                            <a class="dropdown-item users-action" href="#delete">
                                <i class="icon-trash icon-fixed-width"></i>
                                <?php echo __('Delete'); ?></a>
                            <?php }
                            } # end of registration-enabled? ?>
               
                        </div>
                    </div>
                </div> 
                        
                         
                         
                         <div class="clearfix"></div>
                        
                  
 </div>

<div class="card-box">

<div class="row">
    <div class="col">
        <div class="float-right">
            <form  class="form-inline" action="users.php" method="get" style="padding-bottom: 10px; margin-top: -5px;">
                <?php csrf_token(); ?>
                
                 <div class="input-group input-group-sm">
                 <input type="hidden" name="a" value="search">
                    <input type="text" class="form-control form-control-sm basic-search" id="basic-user-search" name="query"
                            value="<?php echo Format::htmlchars($_REQUEST['query']); ?>"
                            autocomplete="off" autocorrect="off" autocapitalize="off"  placeholder="Search Users">
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
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


<form id="users-list" action="users.php" method="POST" name="staff" >

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
 <table  id="users" class="table table-striped table-hover table-condensed table-sm">
    <thead>
        <tr>
            <th nowrap width="4%">&nbsp;</th>
            <th><a <?php echo $name_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
			<th><a <?php echo $org_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=org"><?php echo __('Organization'); ?></a></th>
            <th data-breakpoints="xs sm" width="22%"><a  <?php echo $status_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=status"><?php echo __('Status'); ?></a></th>
            <th data-breakpoints="xs sm" width="20%"><a <?php echo $create_sort; ?> href="users.php?<?php
                echo $qstr; ?>&sort=create"><?php echo __('Created'); ?></a></th>
            <th data-breakpoints="xs sm"  width="20%"><a <?php echo $update_sort; ?> href="users.php?<?php
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

				$organization = new UsersName($U['org__name']);
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
				<td><?php echo ltrim($organization,","); ?></td>
                <td><?php echo $status; ?></td>
                <td><?php echo Format::date($U['created']); ?></td>
                <td><?php echo Format::datetime($U['updated']); ?>&nbsp;</td>
               </tr>
<?php   } //end of foreach. ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="6">
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
</form>

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
                echo sprintf('<a class="export-csv no-pjax" href="users.php?a=export&qh=%s">%s</a>',
                       $qhash,
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


</div>
</div>
</div>
<script type="text/javascript">

jQuery(function($){
	$('#users').footable();
});

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
});
</script>

