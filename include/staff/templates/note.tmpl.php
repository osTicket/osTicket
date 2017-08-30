<div class="quicknote" data-id="<?php echo $note->id; ?>">
    <div class="header">
        <div class="header-left">
            <i class="note-type icon-<?php echo $note->getExtIconClass(); ?>"i
                title="<?php echo $note->getIconTitle(); ?>"></i>&nbsp;
            <?php echo $note->getFormattedTime(); ?>
        </div>
        <div class="header-right">
<?php
            echo $note->getStaff()->getName();
if (isset($show_options) && $show_options) { ?>
&nbsp;
            <div class="btn-group btn-group-sm pull-right">
                <a href="#" class="btn btn-light btn-small action edit-note" title="edit"><i class="icon-pencil"></i></a>
                <a href="#" class="btn btn-light btn-small action save-note" style="display:none" title="save"><i class="icon-save"></i></a>
                <a href="#" class="btn btn-light btn-small action cancel-edit" style="display:none" title="undo"><i class="icon-undo"></i></a>
                <a href="#" class="btn btn-light btn-small action delete" title="delete"><i class="icon-trash"></i></a>
            </div>
<?php } ?>
        </div>
    </div>
    <div class="body editable">
        <?php echo $note->display(); ?>
    </div>
</div>
