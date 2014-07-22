<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qstr='';
$sql='SELECT page.id, page.isactive, page.name, page.created, page.updated, '
     .'page.type, count(topic.topic_id) as topics '
     .' FROM '.PAGE_TABLE.' page '
     .' LEFT JOIN '.TOPIC_TABLE.' topic ON(topic.page_id=page.id) ';
$where = ' WHERE type in ("other","landing","thank-you","offline") ';
$sortOptions=array(
        'name'=>'page.name', 'status'=>'page.isactive',
        'created'=>'page.created', 'updated'=>'page.updated',
        'type'=>'page.type');

$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}

$order_column=$order_column?$order_column:'page.name';

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

$total=db_count('SELECT count(*) FROM '.PAGE_TABLE.' page '.$where);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('pages.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
//Ok..lets roll...create the actual query
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql $where GROUP BY page.id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing()._N('site page','site pages', $num);
else
    $showing=__('No pages found!');

?>

<div class="pull-left" style="width:700px;padding-top:5px;">
 <h2><?php echo __('Site Pages'); ?>
    <i class="help-tip icon-question-sign" href="#site_pages"></i>
    </h2>
</div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">
 <b><a href="pages.php?a=add" class="Icon newPage"><?php echo __('Add New Page'); ?></a></b></div>
<div class="clear"></div>
<form action="pages.php" method="POST" name="tpls">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th width="300"><a <?php echo $name_sort; ?> href="pages.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="90"><a  <?php echo $type_sort; ?> href="pages.php?<?php echo $qstr; ?>&sort=type"><?php echo __('Type'); ?></a></th>
            <th width="110"><a  <?php echo $status_sort; ?> href="pages.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status'); ?></a></th>
            <th width="150" nowrap><a  <?php echo $created_sort; ?>href="pages.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Date Added'); ?></a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="pages.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            $defaultPages=$cfg->getDefaultPages();
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['id'], $ids))
                    $sel=true;
                $inuse = ($row['topics'] || in_array($row['id'], $defaultPages));
                ?>
            <tr id="<?php echo $row['id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <td>&nbsp;<a href="pages.php?id=<?php echo $row['id']; ?>"><?php echo Format::htmlchars($row['name']); ?></a></td>
                <td class="faded"><?php echo $row['type']; ?></td>
                <td>
                    &nbsp;<?php echo $row['isactive']?__('Active'):'<b>'.__('Disabled').'</b>'; ?>
                    &nbsp;&nbsp;<?php echo $inuse?'<em>'.__('(in-use)').'</em>':''; ?>
                </td>
                <td>&nbsp;<?php echo Format::db_date($row['created']); ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if($res && $num){ ?>
            <?php echo __('Select'); ?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All'); ?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None'); ?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle'); ?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No pages found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="enable" value="<?php echo __('Enable'); ?>" >
    <input class="button" type="submit" name="disable" value="<?php echo __('Disable'); ?>" >
    <input class="button" type="submit" name="delete" value="<?php echo __('Delete'); ?>" >
</p>
<?php
endif;
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>enable</b> %s?'),
            _N('selected site page', 'selected site pages', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> %s?'),
            _N('selected site page', 'selected site pages', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected site page', 'selected site pages', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="Yes, Do it!" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
