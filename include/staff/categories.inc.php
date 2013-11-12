<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');

$qstr='';
$sql='SELECT cat.category_id, cat.name, cat.ispublic, cat.updated, count(faq.faq_id) as faqs '.
     ' FROM '.FAQ_CATEGORY_TABLE.' cat '.
     ' LEFT JOIN '.FAQ_TABLE.' faq ON (faq.category_id=cat.category_id) ';
$sql.=' WHERE 1';
$sortOptions=array('name'=>'cat.name','type'=>'cat.ispublic','faqs'=>'faqs','updated'=>'cat.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'cat.name';

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

$total=db_count('SELECT count(*) FROM '.FAQ_CATEGORY_TABLE.' cat ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('categories.php',$qstr.'&sort='.urlencode($_REQUEST['sort']).'&order='.urlencode($_REQUEST['order']));
$qstr.='&order='.($order=='DESC'?'ASC':'DESC');
$query="$sql GROUP BY cat.category_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' categories';
else
    $showing='No FAQ categories found!';

?>
<div style="width:700px;padding-top:5px; float:left;">
 <h2>FAQ Categories</h2>
 </div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
    <b><a href="categories.php?a=add" class="Icon newCategory">Add New Category</a></b></div>
<div class="clear"></div>
<form action="categories.php" method="POST" name="cat">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th width="500"><a <?php echo $name_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=name">Name</a></th>
            <th width="150"><a  <?php echo $type_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=type">Type</a></th>
            <th width="80"><a  <?php echo $faqs_sort; ?> href="categories.php?<?php echo $qstr; ?>&sort=faqs">FAQs</a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="categories.php?<?php echo $qstr; ?>&sort=updated">Last Updated</a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['category_id'],$ids))
                    $sel=true;
                
                $faqs=0;
                if($row['faqs'])
                    $faqs=sprintf('<a href="faq.php?cid=%d">%d</a>',$row['category_id'],$row['faqs']);
                ?>
            <tr id="<?php echo $row['category_id']; ?>">
                <td width=7px>
                  <input type="checkbox" name="ids[]" value="<?php echo $row['category_id']; ?>" class="ckb"
                            <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <td><a href="categories.php?id=<?php echo $row['category_id']; ?>"><?php echo Format::truncate($row['name'],200); ?></a>&nbsp;</td>
                <td><?php echo $row['ispublic']?'<b>Public</b>':'Internal'; ?></td>
                <td style="text-align:right;padding-right:25px;"><?php echo $faqs; ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="5">
            <?php if($res && $num){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No FAQ categories found.';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="make_public" value="Make Public">
    <input class="button" type="submit" name="make_private" value="Make Internal">
    <input class="button" type="submit" name="delete" value="Delete" >
</p>
<?php
endif;
?>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="make_public-confirm">
        Are you sure want to make selected categories <b>public</b>?
    </p>
    <p class="confirm-action" style="display:none;" id="make_private-confirm">
        Are you sure want to make selected categories <b>private</b> (internal)?
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Are you sure you want to DELETE selected categories?</strong></font>
        <br><br>Deleted entries CANNOT be recovered, including any associated FAQs.
    </p>
    <div>Please confirm to continue.</div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="No, Cancel" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="button" value="Yes, Do it!" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
