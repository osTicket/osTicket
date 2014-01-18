<div style="width:700;padding-top:5px; float:left;">
 <h2>Currently Installed Plugins</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="plugins.php?a=add" class="Icon form-add">Add New Plugin</a></b></div>
<div class="clear"></div>

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = count($ost->plugins->allInstalled());
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('forms.php');
$showing=$pageNav->showing().' forms';
?>

<form action="plugins.php" method="POST" name="forms">
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th>Plugin Name</th>
            <th>Status</td>
            <th>Date Installed</th>
        </tr>
    </thead>
    <tbody>
<?php
foreach ($ost->plugins->allInstalled() as $p) {
    if ($p instanceof Plugin) { ?>
    <tr>
        <td><input type="checkbox" class="ckb" name="ids[]" value="<?php echo $p->getId(); ?>"
                <?php echo $sel?'checked="checked"':''; ?>></td>
        <td><a href="plugins.php?id=<?php echo $p->getId(); ?>"
            ><?php echo $p->getName(); ?></a></td>
        <td><?php echo ($p->isActive())
            ? 'Enabled' : '<strong>Disabled</strong>'; ?></td>
        <td><?php echo Format::db_datetime($p->getInstallDate()); ?></td>
    </tr>
    <?php } else {} ?>
<?php } ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="4">
            <?php if($count){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">All</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">None</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Toggle</a>&nbsp;&nbsp;
            <?php }else{
                echo 'No plugins installed yet &mdash; <a href="?a=add">add one</a>!';
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
    <input class="button" type="submit" name="enable" value="Enable">
    <input class="button" type="submit" name="disable" value="Disable">
</p>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href="">&times;</a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Are you sure you want to DELETE selected plugins?</strong></font>
        <br><br>Deleted forms CANNOT be recovered.
    </p>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <font color="green"><strong>Are you ready to enable selected plugins?</strong></font>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <font color="red"><strong>Are you sure you want to disable selected plugins?</strong></font>
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
