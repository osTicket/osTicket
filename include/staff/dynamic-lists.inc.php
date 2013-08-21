<div style="width:700;padding-top:5px; float:left;">
 <h2>Dynamic Lists</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="dynamic-lists.php?a=add" class="Icon">Add New Dynamic List</a></b></div>
<div class="clear"></div>

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = DynamicList::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('dynamic-lists.php');
$showing=$pageNav->showing().' dynamic lists';
?>

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
                ->offset($pageNav->getStart()) as $list) { ?>
        <tr>
            <td/>
            <td><a href="?id=<?php echo $list->get('id'); ?>"><?php echo $list->get('name'); ?></a></td>
            <td><?php echo $list->get('created'); ?></td>
            <td><?php echo $list->get('updated'); ?></td>
        </tr>
    <?php }
    ?>
    </tbody>
</table>
<?php
if ($count) //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
