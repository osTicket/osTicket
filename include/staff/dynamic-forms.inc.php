<div style="width:700;padding-top:5px; float:left;">
 <h2>Custom Forms</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="forms.php?a=add" class="Icon form-add">Add New Custom Form</a></b></div>
<div class="clear"></div>

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = DynamicForm::objects()->filter(array('type__in'=>array('G')))->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('forms.php');
$showing=$pageNav->showing().' forms';
?>

<form action="forms.php" method="POST" name="forms">
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th>Built-in Forms</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach (UserForm::objects()->order_by('title') as $form) { ?>
        <tr>
            <td><i class="icon-user"></i></td>
            <td><a href="?id=<?php echo $form->get('id'); ?>">
                <?php echo $form->get('title'); ?></a>
            <td><?php echo $form->get('updated'); ?></td>
        </tr>
    <?php }
    foreach (TicketForm::objects()->order_by('title') as $form) { ?>
        <tr>
            <td><i class="icon-ticket"></i></td>
            <td><a href="?id=<?php echo $form->get('id'); ?>">
                <?php echo $form->get('title'); ?></a></td>
            <td><?php echo $form->get('updated'); ?></td>
        </tr>
    <?php }
    foreach (DynamicForm::objects()->filter(array('type'=>'C')) as $form) { ?>
        <tr>
            <td><i class="icon-building"></i></td>
            <td><a href="?id=<?php echo $form->get('id'); ?>">
                <?php echo $form->get('title'); ?></a></td>
            <td><?php echo $form->get('updated'); ?></td>
        </tr>
    <?php } ?>
    </tbody>
    <tbody>
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th>Custom Forms</th>
            <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (DynamicForm::objects()->filter(array('type'=>'G'))
                ->order_by('title')
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart()) as $form) {
            $sel=false;
            if($ids && in_array($form->get('id'),$ids))
                $sel=true; ?>
        <tr>
            <td><?php if ($form->isDeletable()) { ?>
                <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $form->get('id'); ?>"
                    <?php echo $sel?'checked="checked"':''; ?>>
            <?php } ?></td>
            <td><a href="?id=<?php echo $form->get('id'); ?>"><?php echo $form->get('title'); ?></a></td>
            <td><?php echo $form->get('updated'); ?></td>
        </tr>
    <?php }
    ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="3">
            <?php if($count){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No extra forms defined yet &mdash; <a href="forms.php?a=add">add one!</a>';
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
        <font color="red"><strong>Are you sure you want to DELETE selected forms?</strong></font>
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
