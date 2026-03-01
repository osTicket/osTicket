<?php
    $id = $item->getId(); ?>
    <tr id="list-item-<?php echo $id; ?>" class="<?php if (!$item->isEnabled()) echo 'disabled'; ?>">
        <td nowrap><?php echo $icon; ?>
            <input type="hidden" name="sort-<?php echo $id; ?>"
            value="<?php echo $item->getSortOrder(); ?>"/>
            <input type="checkbox" value="<?php echo $id; ?>" class="mass nowarn"/>
        </td>
        <td>
            <a class="field-config preview"
               style="overflow:inherit"
               href="#list/<?php
                echo $list->getId(); ?>/item/<?php
                echo $id ?>/update"
               id="item-<?php echo $id; ?>"
               data-preview="<?php echo
                sprintf('#/list/%d/items/%d/preview',
                       $item->getListId(),
                       $item->getId());
                ?>"
            ><?php
                echo sprintf('<i class="icon-edit" %s></i> ',
                        ($prop_fields && !$item->getConfiguration())
                        ? 'style="color:red; font-weight:bold;"' : '');
            ?>
            <?php echo Format::htmlchars($item->getValue()); ?>
            <?php
            if ($list->hasAbbrev() && ($A = $item->getAbbrev())) { ?>
                ( <?php echo Format::htmlchars($A); ?> )
            <?php
            } ?>
<?php           if ($errors["value-$id"])
                echo sprintf('<div class="error">%s</div>',
                        $errors["value-$id"]);
            ?>
            </a>
        </td>
<?php $props = $item->getConfiguration();
if ($prop_fields) {
    foreach ($prop_fields as $F) { ?>
        <td style="max-width: 20%"><span class="truncate"><?php
        echo $F->display($props[$F->get('id')]);
        ?></span></td>
<?php }
} ?>
    </tr>
