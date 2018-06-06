<div class="quicknote" data-id="<?php echo $note->id; ?>">
    <div class="header">
        <div class="header-left">
            <i class="note-type icon-<?php echo $note->getExtIconClass(); ?>"i
                title="<?php echo $note->getIconTitle(); ?>"></i>&nbsp;
            <?php echo $note->getFormattedTime(); ?>
        </div>
        <div class="header-right">
<?php
$staff = $note->getStaff();
echo $staff ? $staff->getName() : _('Staff');
if (isset($show_options) && $show_options) { ?>
            <div class="options no-pjax">
                <a href="#" class="action edit-note" title="edit"><i class="icon-pencil"></i></a>
                <a href="#" class="action save-note" style="display:none" title="save"><i class="icon-save"></i></a>
                <a href="#" class="action cancel-edit" style="display:none" title="undo"><i class="icon-undo"></i></a>
                <a href="#" class="action delete" title="delete"><i class="icon-trash"></i></a>
            </div>
<?php } ?>
        </div>
    </div>
    <div class="body editable">
        <?php echo $note->display(); ?>
    </div>
</div>
