 <form action="roles.php" method="POST" name="roles">
 <div class="subnav">


                        <div class="float-left subnavtitle">
                        
                            <span ><a href="<?php echo $refresh_url; ?>"
                                title="<?php echo __('Refresh'); ?>"><i class="icon-refresh"></i> 
                                </a> &nbsp;
            <?php echo __('Roles');?>
                                
                                </span>
                        
                       
                       
                        </div>
 
        <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
                    
                    <a class="btn btn-icon waves-effect waves-light btn-success"
                       href="roles.php?a=add" data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Add New Role'); ?>">
                        <i class="fa fa-plus-square"></i>
                    </a>
        <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
            data-toggle="dropdown"><i class="fa fa-cog" data-placement="bottom" data-toggle="tooltip" 
             title="More"></i>
            </button>
                    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="actions">
                    
                   <a class="confirm" data-name="enable" href="roles.php?a=enable">
                        <i class="icon-ok-sign icon-fixed-width"></i>
                        <?php echo __('Enable'); ?></a>
                       
                           <a class="confirm" data-name="disable" href="roles.php?a=disable">
                        <i class="icon-ban-circle icon-fixed-width"></i>
                        <?php echo __('Disable'); ?></a>
                    <li class="danger"><a class="confirm" data-name="delete" href="roles.php?a=delete">
                        <i class="icon-trash icon-fixed-width"></i>
                        <?php echo __('Delete'); ?></a>
                               
                    </div>
            </div>                    
        </div>   
        
        <div class="clearfix"></div>                      
 </div>
 
 <div class="card-box">

<div class="row">
    <div class="col">


<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Role::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('roles.php');
$showing=$pageNav->showing().' '._N('role', 'roles', $count);

csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="table table-striped table-hover table-condensed table-sm">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="53%"><?php echo __('Name'); ?></th>
            <th width="8%"><?php echo __('Status'); ?></th>
            <th width="15%"><?php echo __('Created On') ?></th>
            <th width="20%"><?php echo __('Last Updated'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (Role::objects()->order_by('name')
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart()) as $role) {
            $id = $role->getId();
            $sel = false;
            if ($ids && in_array($id, $ids))
                $sel = true; ?>
        <tr>
            <td align="center">
                <?php
                if ($role->isDeleteable()) { ?>
                <input width="7" type="checkbox" class="ckb" name="ids[]"
                value="<?php echo $id; ?>"
                    <?php echo $sel?'checked="checked"':''; ?>>
                <?php
                } else {
                    echo '&nbsp;';
                }
                ?>
            </td>
            <td><a href="?id=<?php echo $id; ?>"><?php echo
            $role->getLocal('name'); ?></a></td>
            <td>&nbsp;<?php echo $role->isEnabled() ? __('Active') :
            '<b>'.__('Disabled').'</b>'; ?></td>
            <td><?php echo Format::date($role->getCreateDate()); ?></td>
            <td><?php echo Format::datetime($role->getUpdateDate()); ?></td>
        </tr>
    <?php }
    ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="5">
            <?php if($count){ ?>
            <?php echo __('Select'); ?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All'); ?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None'); ?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle'); ?></a>&nbsp;&nbsp;
            <?php } else {
                echo sprintf(__('No roles defined yet &mdash; %s add one %s!'),
                    '<a href="roles.php?a=add">','</a>');
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

   
    <div class="float-right">
          <span class="faded"><?php echo $pageNav->showing(); ?></span>
    </div>  
</div></div>

</div></div></div></form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>enable</b> %s?'),
            _N('selected role', 'selected roles', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> %s?'),
            _N('selected role', 'selected roles', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected role', 'selected roles', 2)); ?></strong></font>
        <br><br><?php echo __('Deleted roles CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel'); ?>" class="btn btn-sm btn-warning close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!'); ?>" class="btn btn-sm btn-danger confirm">
        </span>
    </p>
    <div class="clear"></div>
</div>
