<?php
$count = $schedule->getNumEntries();
$entries = $schedule->getEntries()
    ->order_by('sort');
?>
<div style="margin: 5px 0">
<div class="pull-left" valign="bottom"><?php
    echo sprintf('All times are in %s timezone',
        $schedule->getTimezone()); ?></div>
<div class="pull-right">
    <a class="green button action-button entry-action"
        href="#schedule/<?php
        echo $schedule->getId(); ?>/entry/add">
        <i class="icon-plus-sign"></i>
        <?php echo __('Add New Entry'); ?>
    </a>
    <?php
    if ($count) { ?>
    <span class="action-button pull-right" data-dropdown="#action-dropdown-more">
        <i class="icon-caret-down pull-right"></i>
        <span ><i class="icon-cog"></i> <?php echo __('Actions');?></span>
    </span>
    <div id="action-dropdown-more" class="action-dropdown anchor-right">
        <ul>
            <li class="danger"><a class="entries-action"
            href="#schedule/<?php echo $schedule->getId(); ?>/delete-entries">
                <i class="icon-trash icon-fixed-width"></i>
                <?php echo __('Delete'); ?></a></li>
        </ul>
    </div>
    <?php
    } ?>
</div>
<div class="clear"></div>
</div>
<?php
if ($count) { ?>
<div>
<table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th width="28" nowrap></th>
            <th><?php echo __('Name'); ?></th>
            <th><?php echo __('Repeats'); ?></th>
            <th width="180"><?php echo __('Updated'); ?></th>
        </tr>
    </thead>
    <tbody id="schedule-entries" class="sortable-rows" data-sort="sort-">
        <?php
        $icon = '<i class="icon-sort"></i>&nbsp;';
        ?>
        <input type="hidden" id="sort-offset" value="<?php echo
            max($entries[0]->sort, 1); ?>"/>
        <?php
        $sort = 0;
        foreach ($entries as $entry) {
            $id = $entry->getId(); ?>
            <tr id="schedule-entry-<?php echo $id; ?>">
                <td nowrap><?php echo $icon; ?>
                    <input type="hidden" name="sort-<?php echo $id; ?>"
                    value="<?php echo $entry->sort ?: ++$sort; ?>"/>
                    <input type="checkbox" value="<?php echo $id; ?>"
                    class="schedule-entry nowarn"/>
                </td>
                <td>
                    <a class="entry-action"
                       style="overflow:inherit"
                       href="#schedule/<?php
                        echo $schedule->getId(); ?>/entry/<?php
                        echo $id ?>/update"
                       id="entry-<?php echo $id; ?>"
                    ><?php
                    echo '<i class="icon-edit"></i>&nbsp;';
                    echo Format::htmlchars($entry->getName()); ?>
                    </a>
                </td>
                <td><span class="faded-more"><i
                class="icon-time"></i></span>&nbsp;<?php echo $entry->getDesc(); ?></td>
                <td nowrap><?php echo  Format::datetime($entry->getUpdated());?></td>
            </tr>
        <?php
        } ?>
    </tbody>
</table>
</div>
<?php } ?>
