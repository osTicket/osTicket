<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = $plugin->getNumInstances();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('plugins.php?id='.$plugin->getId().'&a=instances');

// use dialog modal or page to add or configure an instance
$modal = $plugin->useModalConfig();
?>
<div style="margin: 5px 0">
    <div class="pull-right">
        <a class="green button action-button <?php echo $modal ?
        'instance-config' : ''; ?>"
        href="<?php echo sprintf(
                ($modal ?  '#plugins/%d/instances/add' : 'plugins.php?id=%d&a=add-instance#instances'),
                $plugin->getId()); ?>"
        >
            <i class="icon-plus-sign"></i>
            <?php echo __('Add New Instance'); ?>
        </a>
        <?php
        if ($count) { ?>
        <span class="action-button pull-right" data-dropdown="#action-dropdown-more">
            <i class="icon-caret-down pull-right"></i>
            <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
        </span>
        <div id="action-dropdown-more" class="action-dropdown anchor-right">
            <ul>
                <li><a class="instances-action" href="#disable">
                    <i class="icon-ban-circle icon-fixed-width"></i>
                    <?php echo __('Disable'); ?></a></li>
                <li><a class="instances-action" href="#enable">
                    <i class="icon-ok-sign icon-fixed-width"></i>
                    <?php echo __('Enable'); ?></a></li>
                <li class="danger"><a class="instances-action" href="#delete">
                    <i class="icon-trash icon-fixed-width"></i>
                    <?php echo __('Delete'); ?></a></li>
            </ul>
        </div>
        <?php
        } ?>
    </div>
    <div class="clear"></div>
 </div>
<div>
<?php
if ($count) { ?>
<form id="plugin-instances-form" action="" method="POST">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="instances-actions" >
 <input type="hidden" id="action" name="a" value="" >
<table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
<thead>
    <tr>
        <th width="28" nowrap></th>
        <th><?php echo __('Name'); ?></th>
        <th width="120"><?php echo __('Status'); ?></th>
        <th width="200"><?php echo __('Added'); ?></th>
        <th width="200"><?php echo __('Updated'); ?></th>
    </tr>
</thead>
<tbody id="plugin-instances">
<?php
    foreach ($plugin->getInstances()->order_by('name') as $i) {
        $id = $i->getId(); ?>
    <tr id="plugin-instance-<?php echo $id; ?>" class="<?php if (!$i->isEnabled()) echo 'disabled'; ?>">
        <td nowrap><?php echo $icon; ?>
            <input type="checkbox" value="<?php echo $id ?>" class="mass nowarn"/>
        </td>
        <td>
            <a <?php echo $modal ? 'class="instance-config"' : ''; ?>
               style="overflow:inherit"
               href="<?php echo sprintf(
               $modal ? '#plugins/%s/instances/%d/update' :
               'plugins.php?id=%s&xid=%d',
               $plugin->getId(), $id); ?>"
            ><?php
                echo sprintf('<i class="icon-edit" %s></i> ',
                        (0 && !$i->getConfig())
                        ? 'style="color:red; font-weight:bold;"' : '');
            ?>
            <?php echo Format::htmlchars($i->getName()); ?>
            </a>
        </td>
        <td><?php echo $i->isEnabled() ? __('Enabled') : __('Disabled'); ?></td>
        <td><?php echo  Format::datetime($i->getCreateDate()); ?></td>
        <td><?php echo  Format::datetime($i->getUpdateDate()); ?></td>
    </tr>
    <?php
    }
?>
</tbody>
</table>
<?php if ($pageNav && $pageNav->getNumPages()) { ?>
    <div><?php echo __('Page').':'.$pageNav->getPageLinks('instances', $pjax_container); ?></div>
</form>
<?php }
}?>
</div>
