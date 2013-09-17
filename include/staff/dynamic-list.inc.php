<?php

$info=array();
if($list && $_REQUEST['a']!='add') {
    $title = 'Update dynamic list';
    $action = 'update';
    $submit_text='Save Changes';
    $info = $list->ht;
    $newcount=2;
} else {
    $title = 'Add new dynamic list';
    $action = 'add';
    $submit_text='Add List';
    $newcount=4;
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<form action="?" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    <h2>Dynamic List</h2>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Dynamic lists are used to provide selection boxes for dynamic forms</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">Name:</td>
            <td><input size="50" type="text" name="name" value="<?php echo $info['name']; ?>"/></td>
        </tr>
        <tr>
            <td width="180">Plural Name:</td>
            <td><input size="50" type="text" name="name_plural" value="<?php echo $info['name_plural']; ?>"/></td>
        </tr>
        <tr>
            <td width="180">Sort Order:</td>
            <td><select name="sort_mode">
                <?php foreach (DynamicList::getSortModes() as $key=>$desc) { ?>
                <option value="<?php echo $key; ?>" <?php
                    if ($key == $info['sort_mode']) echo 'selected="selected"';
                    ?>><?php echo $desc; ?></option>
                <?php } ?>
                </select></td>
        </tr>
        <tr>
            <td width="180">Description:</td>
            <td><textarea name="notes" rows="3" cols="40"><?php
                echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
    </table>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
    <?php if ($list) {
        $page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
        $count = $list->getItemCount();
        $pageNav = new Pagenate($count, $page, PAGE_LIMIT);
        $pageNav->setURL('dynamic-list.php', 'id='.urlencode($_REQUEST['id']));
        $showing=$pageNav->showing().' list items';
        ?>
    <?php }
    ?>
        <tr>
            <th colspan="4">
                <em><?php echo $showing; ?></em>
            </th>
        </tr>
        <tr>
            <th>Delete</th>
            <th>Value</th>
            <th>Extra</th>
        </tr>
    </thead>
    <tbody <?php if ($info['sort_mode'] == 'SortCol') { ?>
            class="sortable-rows" data-sort="sort-"<?php } ?>>
        <?php if ($list)
        $icon = ($info['sort_mode'] == 'SortCol')
            ? '<i class="icon-sort"></i>&nbsp;' : '';
        if ($list) {
        foreach ($list->getItems($pageNav->getLimit(), $pageNav->getStart()) as $i) {
            $id = $i->get('id'); ?>
        <tr>
            <td><?php echo $icon; ?>
                <input type="checkbox" name="delete-<?php echo $id; ?>"/>
                <input type="hidden" name="sort-<?php echo $id; ?>"
                value="<?php echo $i->get('sort'); ?>"/></td>
            <td><input type="text" size="40" name="value-<?php echo $id; ?>"
                value="<?php echo $i->get('value'); ?>"/></td>
            <td><input type="text" size="20" name="extra-<?php echo $id; ?>"
                value="<?php echo $i->get('extra'); ?>"/></td>
        </tr>
    <?php }
    }
    for ($i=0; $i<$newcount; $i++) { ?>
        <tr>
            <td><?php echo $icon; ?> <em>add</em>
                <input type="hidden" name="sort-new-<?php echo $i; ?>"/></td>
            <td><input type="text" size="40" name="value-new-<?php echo $i; ?>"/></td>
            <td><input type="text" size="20" name="extra-new-<?php echo $i; ?>"/></td>
        </tr>
    <?php } ?>
    </tbody>
    </table>
<?php
if ($count) //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p style="padding-left:225px;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="?"'>
</p>
</form>
