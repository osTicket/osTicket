<div style="width:700;padding-top:5px; float:left;">
 <h2>Custom Lists</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="lists.php?a=add" class="Icon list-add">Add New Custom List</a></b></div>
<div class="clear"></div>

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = DynamicList::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('lists.php');
$showing=$pageNav->showing().' custom lists';
?>

<form action="lists.php" method="POST" name="lists">
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th>List Name</th>
            <th>Created</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (DynamicList::objects()->order_by('name')
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart()) as $list) {
            $sel = false;
            if ($ids && in_array($form->get('id'),$ids))
                $sel = true; ?>
        <tr>
            <td>
                <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $list->get('id'); ?>"
                    <?php echo $sel?'checked="checked"':''; ?>></td>
            <td><a href="?id=<?php echo $list->get('id'); ?>"><?php echo $list->get('name'); ?></a></td>
            <td><?php echo $list->get('created'); ?></td>
            <td><?php echo $list->get('updated'); ?></td>
        </tr>
    <?php }
    ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="4">
            <?php if($count){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php } else {
                echo 'No custom lists defined yet &mdash; add one!';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count) //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

<p class="centered" id="actions">
    <input class="button" type="submit" name="delete" value="Delete">
</p>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Are you sure you want to DELETE selected lists?</strong></font>
        <br><br>Deleted forms CANNOT be recovered.
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
