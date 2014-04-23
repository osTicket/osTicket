<div class="quicknote" data-id="<?php echo $note->id; ?>">
    <div class="header">
        <div class="header-left">
            <?php echo $note->getFormattedTime(); ?>
        </div>
        <div class="header-right">
<?php
            echo $note->getStaff()->getName();
if ($ext_id && $note->ext_id != $ext_id) { ?>
            <span class="label label-info"><?php echo $note->getExtType(); ?></span>
<?php }
if (isset($show_options) && $show_options) { ?>
            <div class="options">
                <a href="#" class="action edit-note no-pjax" title="edit"><i class="icon-edit"></i></a>
                <a href="#" class="action save-note no-pjax" style="display:none" title="save"><i class="icon-save"></i></a>
                <a href="#" class="action cancel-edit no-pjax" style="display:none" title="undo"><i class="icon-undo"></i></a>
                <a href="#" class="action delete no-pjax" title="delete"><i class="icon-trash"></i></a>
            </div>
<?php } ?>
        </div>
    </div>
    <div class="body editable">
        <?php echo $note->display(); ?>
    </div>
</div>
