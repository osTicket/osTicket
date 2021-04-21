<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

//get canned responses for depts with a role that can manage canned responses
$departments = array();
if ($thisstaff->getRole()->hasPerm(Canned::PERM_MANAGE, false))
    $departments[] = $thisstaff->getDeptId();

foreach($thisstaff->dept_access as $da) {
    if ($da->role->hasPerm(Canned::PERM_MANAGE, false))
        $departments[] = $da->dept->getId();
}
$departments = implode(',', $departments);

$qs = array();
$sql='SELECT canned.*, count(attach.file_id) as files, dept.name as department '.
     ' FROM '.CANNED_TABLE.' canned '.
     ' LEFT JOIN '.DEPT_TABLE.' dept ON (dept.id=canned.dept_id) '.
     ' LEFT JOIN '.ATTACHMENT_TABLE.' attach
            ON (attach.object_id=canned.canned_id AND attach.`type`=\'C\' AND NOT attach.inline)';
$sql.= sprintf(' WHERE canned.dept_id = 0 OR dept.id IN (%s)', $departments);

$sortOptions=array('title'=>'canned.title','status'=>'canned.isenabled','dept'=>'department','updated'=>'canned.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'title';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}

$order_column=$order_column?$order_column:'canned.title';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}

$order=$order?$order:'ASC';

if($order_column && strpos($order_column,',')){
    $order_column=str_replace(','," $order,",$order_column);
}

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(*) FROM '.CANNED_TABLE.' canned ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('canned.php', $qs);
//Ok..lets roll...create the actual query
$qstr .= '&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY canned.canned_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' '._N('premade response', 'premade responses',
        $total);
else
    $showing=__('No premade responses found!');

?>
<form action="canned.php" method="POST" name="canned">

<div class="sticky bar opaque">
    <div class="content">
        <div class="pull-left flush-left">
            <h2><?php echo __('Canned Responses');?></h2>
        </div>
        <div class="pull-right flush-right">
            <a href="canned.php?a=add" class="green button"><i class="icon-plus-sign"></i> <?php echo __('Add New Response');?></a>

            <span class="action-button" data-dropdown="#action-dropdown-more" style="/*DELME*/ vertical-align:top; margin-bottom:0">
                    <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
            </span>
            <div id="action-dropdown-more" class="action-dropdown anchor-right">
                <ul id="actions">
                    <li>
                        <a class="confirm" data-name="enable" href="canned.php?a=enable">
                            <i class="icon-ok-sign icon-fixed-width"></i>
                            <?php echo __( 'Enable'); ?>
                        </a>
                    </li>
                    <li>
                        <a class="confirm" data-name="disable" href="canned.php?a=disable">
                            <i class="icon-ban-circle icon-fixed-width"></i>
                            <?php echo __( 'Disable'); ?>
                        </a>
                    </li>
                    <li class="danger">
                        <a class="confirm" data-name="delete" href="canned.php?a=delete">
                            <i class="icon-trash icon-fixed-width"></i>
                            <?php echo __( 'Delete'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="46%"><a <?php echo $title_sort; ?> href="canned.php?<?php echo $qstr; ?>&sort=title"><?php echo __('Title');?></a></th>
            <th width="10%"><a  <?php echo $status_sort; ?> href="canned.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status');?></a></th>
            <th width="20%"><a  <?php echo $dept_sort; ?> href="canned.php?<?php echo $qstr; ?>&sort=dept"><?php echo __('Department');?></a></th>
            <th width="20%" nowrap><a  <?php echo $updated_sort; ?>href="canned.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['canned_id'],$ids))
                    $sel=true;
                $files=$row['files']?'<span class="Icon file">&nbsp;</span>':'';
                ?>
            <tr id="<?php echo $row['canned_id']; ?>">
                <td align="center">
                  <input type="checkbox" name="ids[]" value="<?php echo $row['canned_id']; ?>" class="ckb"
                            <?php echo $sel?'checked="checked"':''; ?> />
                </td>
                <td>
                    <a href="canned.php?id=<?php echo $row['canned_id']; ?>"><?php echo Format::truncate($row['title'],200); echo "&nbsp;$files"; ?></a>&nbsp;
                </td>
                <td><?php echo $row['isenabled']?__('Active'):'<b>'.__('Disabled').'</b>'; ?></td>
                <td><?php echo $row['department']?$row['department']:'&mdash; '.__('All Departments').' &mdash;'; ?></td>
                <td>&nbsp;<?php echo Format::datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="5">
            <?php if($res && $num){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No canned responses');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

<?php
endif;
?>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected canned response', 'selected canned responses', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected canned response', 'selected canned responses', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected canned response', 'selected canned responses', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered, including any associated attachments.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
