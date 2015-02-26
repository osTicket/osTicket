    <?php if ($list) {
        $page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
        $count = $list->getNumItems();
        $pageNav = new Pagenate($count, $page, PAGE_LIMIT);
        if ($list->getSortMode() == 'SortCol')
            $pageNav->setSlack(1);
        $pageNav->setURL('lists.php?id='.$list->getId().'&a=items');
        $showing=$pageNav->showing().' '.__('list items');
        ?>
    <?php }
        else $showing = __('Add a few initial items to the list');
    ?>
    <div style="margin: 5px 0">
    <div class="pull-left">
        <input type="search" size="25" id="search" value="<?php
            echo Format::htmlchars($_POST['search']); ?>"/>
        <button type="submit" onclick="javascript:
            event.preventDefault();
            $.pjax({type: 'POST', data: { search: $('#search').val() }, container: '#pjax-container'});
            return false;
"><?php echo __('Search'); ?></button>
        <?php if ($_POST['search']) { ?>
        <a href="#" onclick="javascript:
            $.pjax.reload('#pjax-container'); return false; "
            ><i class="icon-remove-sign"></i> <?php
                echo __('clear'); ?></a>
        <?php } ?>
    </div>
    <?php if ($list) { ?>
    <div class="pull-right">
        <em style="display:inline-block; padding-bottom: 3px;"><?php echo $showing; ?></em>
        <?php if ($list->allowAdd()) { ?>
        <a class="action-button field-config"
            href="#list/<?php
            echo $list->getId(); ?>/item/add">
            <i class="icon-plus-sign"></i>
            <?php echo __('Add New Item'); ?>
        </a>
        <a class="action-button field-config"
            href="#list/<?php
            echo $list->getId(); ?>/import">
            <i class="icon-upload"></i>
            <?php echo __('Import Items'); ?>
        </a>
        <?php } ?>
        <span class="action-button pull-right" data-dropdown="#action-dropdown-more">
            <i class="icon-caret-down pull-right"></i>
            <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
        </span>
        <div id="action-dropdown-more" class="action-dropdown anchor-right">
            <ul>
                <li><a class="items-action" href="#list/<?php echo $list->getId(); ?>/delete">
                    <i class="icon-trash icon-fixed-width"></i>
                    <?php echo __('Delete'); ?></a></li>
                <li><a class="items-action" href="#list/<?php echo $list->getId(); ?>/disable">
                    <i class="icon-ban-circle icon-fixed-width"></i>
                    <?php echo __('Disable'); ?></a></li>
                <li><a class="items-action" href="#list/<?php echo $list->getId(); ?>/enable">
                    <i class="icon-ok-sign icon-fixed-width"></i>
                    <?php echo __('Enable'); ?></a></li>
            </ul>
        </div>
    </div>
    <?php } ?>

    <div class="clear"></div>
    </div>


<?php
$prop_fields = array();
if ($list) {
    foreach ($list->getConfigurationForm()->getFields() as $f) {
        if (in_array($f->get('type'), array('text', 'datetime', 'phone')))
            $prop_fields[] = $f;
        if (strpos($f->get('type'), 'list-') === 0)
            $prop_fields[] = $f;

        // 4 property columns max
        if (count($prop_fields) == 4)
            break;
    }
}
?>

    <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th width="24" nowrap></th>
            <th><?php echo __('Value'); ?></th>
<?php foreach ($prop_fields as $F) { ?>
            <th><?php echo $F->getLocal('label'); ?></th>
<?php } ?>
        </tr>
    </thead>

    <tbody <?php if (!isset($_POST['search']) && $list && $list->get('sort_mode') == 'SortCol') { ?>
            class="sortable-rows" data-sort="sort-"<?php } ?>>
        <?php
        if ($list) {
            $icon = ($list->get('sort_mode') == 'SortCol')
                ? '<i class="icon-sort"></i>&nbsp;' : '';
            $items = $list->getAllItems();
            if ($_POST['search']) {
                $items->filter(Q::any(array(
                    'value__contains'=>$_POST['search'],
                    'extra__contains'=>$_POST['search'],
                    'properties__contains'=>$_POST['search'],
                )));
                $search = true;
            }
            $items = $pageNav->paginate($items);
            // Emit a marker for the first sort offset ?>
            <input type="hidden" id="sort-offset" value="<?php echo
                max($items[0]->sort, $pageNav->getStart()); ?>"/>
<?php
            foreach ($items as $item) {
                include STAFFINC_DIR . 'templates/list-item-row.tmpl.php';
            }
        } ?>
    </tbody>
    </table>
<?php if ($pageNav && $pageNav->getNumPages()) { ?>
    <div><?php echo __('Page').':'.$pageNav->getPageLinks('items', $pjax_container); ?></div>
<?php } ?>
</div>

