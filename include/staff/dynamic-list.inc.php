<?php

$info=array();
if($list && !$errors) {
    $title = 'Update custom list';
    $action = 'update';
    $submit_text='Save Changes';
    $info = $list->ht;
    $newcount=2;
} else {
    $title = 'Add new custom list';
    $action = 'add';
    $submit_text='Add List';
    $newcount=4;
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<form action="?" method="post" id="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="<?php echo $action; ?>">
    <input type="hidden" name="a" value="<?php echo $_REQUEST['a']; ?>">
    <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
    <h2>Custom List</h2>
    <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Custom lists are used to provide drop-down lists for custom forms</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">Name:</td>
            <td><input size="50" type="text" name="name" value="<?php echo $info['name']; ?>"/>
            <span class="error">*<br/><?php echo $errors['name']; ?></td>
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
        else $showing = 'Add a few initial items to the list';
    ?>
        <tr>
            <th colspan="4">
                <em><?php echo $showing; ?></em>
            </th>
        </tr>
        <tr>
            <th></th>
            <th>Value</th>
            <th>Extra <em style="display:inline">&mdash; abbreviations and such</em></th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody <?php if ($info['sort_mode'] == 'SortCol') { ?>
            class="sortable-rows" data-sort="sort-"<?php } ?>>
        <?php if ($list)
        $icon = ($info['sort_mode'] == 'SortCol')
            ? '<i class="icon-sort"></i>&nbsp;' : '';
        if ($list) {
        foreach ($list->getItems() as $i) {
            $id = $i->get('id'); ?>
        <tr>
            <td><?php echo $icon; ?>
                <input type="hidden" name="sort-<?php echo $id; ?>"
                value="<?php echo $i->get('sort'); ?>"/></td>
            <td><input type="text" size="40" name="value-<?php echo $id; ?>"
                value="<?php echo $i->get('value'); ?>"/></td>
            <td><input type="text" size="30" name="extra-<?php echo $id; ?>"
                value="<?php echo $i->get('extra'); ?>"/></td>
            <td>
                <input type="checkbox" name="delete-<?php echo $id; ?>"/></td>
        </tr>
    <?php }
    }
    for ($i=0; $i<$newcount; $i++) { ?>
        <tr>
            <td><?php echo $icon; ?> <em>+</em>
                <input type="hidden" name="sort-new-<?php echo $i; ?>"/></td>
            <td><input type="text" size="40" name="value-new-<?php echo $i; ?>"/></td>
            <td><input type="text" size="30" name="extra-new-<?php echo $i; ?>"/></td>
            <td></td>
        </tr>
    <?php } ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="7">
                <em><strong>Internal Notes:</strong> be liberal, they're internal</em>
            </th>
        </tr>
        <tr>
            <td colspan="7"><textarea name="notes" class="richtext no-bar"
                rows="6" cols="80"><?php
                echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
    </table>
    </table>
<p class="centered">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="?"'>
</p>
</form>
