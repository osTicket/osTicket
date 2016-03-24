<?php
    if ($list) {
        $page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
        $count = $list->getNumItems();
        $pageNav = new Pagenate($count, $page, PAGE_LIMIT);
        if ($list->getSortMode() == 'SortCol')
            $pageNav->setSlack(1);
        $pageNav->setURL('lists.php?id='.$list->getId().'&a=items');
    }
    ?>
    <div style="margin: 5px 0">
    <?php if ($list) { ?>
    <div class="pull-left">
        <input type="text" placeholder="<?php echo __('Search items'); ?>"
            data-url="ajax.php/list/<?php echo $list->getId(); ?>/items/search"
            size="25" id="items-search" value="<?php
            echo Format::htmlchars($_POST['search']); ?>"/>
    </div>
    <div class="pull-right">
<?php
if ($list->allowAdd()) { ?>
        <a class="green button action-button field-config"
            href="#list/<?php
            echo $list->getId(); ?>/item/add">
            <i class="icon-plus-sign"></i>
            <?php echo __('Add New Item'); ?>
        </a>
<?php
    if (method_exists($list, 'importCsv')) { ?>
        <a class="action-button field-config"
            href="#list/<?php
            echo $list->getId(); ?>/import">
            <i class="icon-upload"></i>
            <?php echo __('Import Items'); ?>
        </a>
<?php
    }
} ?>
        <span class="action-button pull-right" data-dropdown="#action-dropdown-more">
            <i class="icon-caret-down pull-right"></i>
            <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
        </span>
        <div id="action-dropdown-more" class="action-dropdown anchor-right">
            <ul>
                <li><a class="items-action" href="#list/<?php echo $list->getId(); ?>/disable">
                    <i class="icon-ban-circle icon-fixed-width"></i>
                    <?php echo __('Disable'); ?></a></li>
                <li><a class="items-action" href="#list/<?php echo $list->getId(); ?>/enable">
                    <i class="icon-ok-sign icon-fixed-width"></i>
                    <?php echo __('Enable'); ?></a></li>
                <li class="danger"><a class="items-action" href="#list/<?php echo $list->getId(); ?>/delete">
                    <i class="icon-trash icon-fixed-width"></i>
                    <?php echo __('Delete'); ?></a></li>
            </ul>
        </div>
    </div>
    <?php } ?>

    <div class="clear"></div>
    </div>


<?php
$prop_fields = ($list) ? $list->getSummaryFields() : array();
?>

    <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th width="28" nowrap></th>
            <th><?php echo __('Value'); ?></th>
<?php
if ($prop_fields) {
    foreach ($prop_fields as $F) { ?>
            <th><?php echo $F->getLocal('label'); ?></th>
<?php
    }
} ?>
        </tr>
    </thead>

    <tbody id="list-items" <?php if (!isset($_POST['search']) && $list && $list->get('sort_mode') == 'SortCol') { ?>
            class="sortable-rows" data-sort="sort-"<?php } ?>>
        <?php
        if ($list) {
            $icon = ($list->get('sort_mode') == 'SortCol')
                ? '<i class="icon-sort"></i>&nbsp;' : '';
            $items = $list->getAllItems();
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
<script type="text/javascript">
$(function() {
  var last_req;
  $('input#items-search').typeahead({
    source: function (typeahead, query) {
      if (last_req)
        last_req.abort();
      var $el = this.$element;
      var url = $el.data('url')+'?q='+query;
      last_req = $.ajax({
        url: url,
        dataType: 'json',
        success: function (data) {
          typeahead.process(data);
        }
      });
    },
    onselect: function (obj) {
      var $el = this.$element,
          url = 'ajax.php/list/{0}/item/{1}/update'
            .replace('{0}', obj.list_id)
            .replace('{1}', obj.id);
      $.dialog(url, [201], function (xhr, resp) {
        var json = $.parseJSON(resp);
        if (json && json.success) {
          if (json.id && json.row) {
            $('#list-item-' + json.id).replaceWith(json.row);
          }
        }
      });
      this.$element.val('');
    },
    property: "display"
  });
});
</script>
