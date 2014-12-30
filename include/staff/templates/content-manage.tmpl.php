<h3><?= __('Manage Content'); ?> &mdash; <?= Format::htmlchars($content->getName()); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form method="post" action="#content/<?= $content->getId(); ?>">
    <input type="text" style="width: 100%; font-size: 14pt" name="name" value="<?= Format::htmlchars($content->getName()); ?>" />
    <div style="margin-top: 5px">
    <textarea class="richtext no-bar" name="body"><?= Format::viewableImages($content->getBody());?></textarea>
    </div>
    <div id="msg_info" style="margin-top:7px"><?= $content->getNotes(); ?></div>
    <hr/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?= __('Reset'); ?>">
            <input type="button" name="cancel" class="<?= $user ? 'cancel' : 'close'; ?>" value="<?= __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">input type="submit" value="<?= __('Save Changes'); ?>"></span>
     </p>
</form>
</div>
<div class="clear"></div>
