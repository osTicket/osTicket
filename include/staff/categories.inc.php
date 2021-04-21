<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qs = array();
$categories = Category::objects()
    ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')));
$sortOptions=array('name'=>'name','type'=>'ispublic','faqs'=>'faq_count','updated'=>'updated');
$orderWays=array('DESC'=>'-','ASC'=>'');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column ?: 'name';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}
$order=$order ?: '';

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=$categories->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('categories.php', $qs);
$qstr = '&amp;order='.($order=='DESC'?'ASC':'DESC');
$pageNav->paginate($categories);

?>

<form action="categories.php" method="POST" id="mass-actions">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('FAQ Categories');?></h2>
            </div>
            <div class="pull-right flush-right">
                <a href="categories.php?a=add" class="green button">
                    <i class="icon-plus-sign"></i>
                    <?php echo __( 'Add New Category');?>
                </a>
                <div class="pull-right flush-right">

                    <span class="action-button" data-dropdown="#action-dropdown-more">
                        <i class="icon-caret-down pull-right"></i>
                        <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                    </span>
                    <div id="action-dropdown-more" class="action-dropdown anchor-right">
                        <ul id="actions">
                            <li class="danger">
                                <a class="confirm" data-form-id="mass-actions" data-name="delete" href="categories.php?a=delete">
                                    <i class="icon-trash icon-fixed-width"></i>
                                    <?php echo __( 'Delete'); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
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
            <th width="56%"><a <?php echo $name_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name');?></a></th>
            <th width="10%"><a  <?php echo $type_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=type"><?php echo __('Type');?></a></th>
            <th width="10%"><a  <?php echo $faqs_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=faqs"><?php echo __('FAQs');?></a></th>
            <th width="20%" nowrap><a  <?php echo $updated_sort; ?>href="categories.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        foreach ($categories as $C) {
            $sel=false;
            if ($ids && in_array($C->getId(), $ids))
                $sel=true;

            $faqs=0;
            if ($C->faq_count)
                $faqs=sprintf('<a href="faq.php?cid=%d">%d</a>',$C->getId(),$C->faq_count);
            ?>
            <tr id="<?php echo $C->getId(); ?>">
                <td align="center">
                  <input type="checkbox" name="ids[]" value="<?php echo $C->getId(); ?>" class="ckb"
                            <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <td><a class="truncate" style="width:500px" href="categories.php?id=<?php echo $C->getId(); ?>"><?php
                    echo Category::getNamebyId($C->getId()); ?></a></td>
                <td><?php echo $C->getVisibilityDescription(); ?></td>
                <td style="text-align:right;padding-right:25px;"><?php echo $faqs; ?></td>
                <td>&nbsp;<?php echo Format::datetime($C->updated); ?></td>
            </tr><?php
        } // end of foreach ?>
    <tfoot>
     <tr>
        <td colspan="5">
            <?php if ($total) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php } else {
                echo __('No FAQ categories found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($total) {
    echo '<div>&nbsp;'.__('Page').': '.$pageNav->getPageLinks().'</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="make_public" value="<?php echo __('Make Public');?>">
    <input class="button" type="submit" name="make_private" value="<?php echo __('Make Internal');?>">
    <input class="button" type="submit" name="delete" value="<?php echo __('Delete');?>" >
</p>
<?php
}
?>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="make_public-confirm">
        <?php echo sprintf(__('Are you sure you want to make %s <b>public</b>?'),
            _N('selected category', 'selected categories', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="make_private-confirm">
        <?php echo sprintf(__('Are you sure you want to make %s <b>private</b> (internal)?'),
            _N('selected category', 'selected categories', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected category', 'selected categories', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered, including any associated FAQs.'); ?>
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
