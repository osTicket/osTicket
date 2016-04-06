<h3 class="drag-handle"><?php echo __('Manage Content'); ?> &mdash; <?php echo Format::htmlchars($content->getName()); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>

<?php if ($errors['err']) { ?>
<div class="error-banner">
    <?php echo $errors['err']; ?>
</div>
<?php } ?>
<form method="post" action="#content/<?php echo $content->getId(); ?>"
        style="clear:none">

<?php
if (count($langs) > 1) { ?>
    <ul class="tabs alt clean" id="content-trans">
    <li class="empty"><i class="icon-globe" title="<?php echo __('This content is translatable'); ?>"></i></li>
<?php foreach ($langs as $tag=>$nfo) { ?>
    <li class="<?php if ($tag == $cfg->getPrimaryLanguage()) echo "active";
        ?>"><a href="#translation-<?php echo $tag; ?>" title="<?php
        echo Internationalization::getLanguageDescription($tag);
    ?>"><span class="flag flag-<?php echo strtolower($nfo['flag']); ?>"></span>
    </a></li>
<?php } ?>
    </ul>
<?php
} ?>

<div id="content-trans_container">
    <div id="translation-<?php echo $cfg->getPrimaryLanguage(); ?>"
        class="tab_content" lang="<?php echo $cfg->getPrimaryLanguage(); ?>">
    <div class="error"><?php echo $errors['name']; ?></div>
    <input type="text" style="width: 100%; font-size: 14pt" name="name" value="<?php
    echo Format::htmlchars($info['title']); ?>" spellcheck="true"
        lang="<?php echo $cfg->getPrimaryLanguage(); ?>" />
    <div style="margin-top: 5px">
    <div class="error"><?php echo $errors['body']; ?></div>
    <textarea class="richtext no-bar" name="body"
        data-root-context="<?php echo $content->getType();
        ?>"><?php echo Format::htmlchars(Format::viewableImages($info['body']));
        ?></textarea>
    </div>
    </div>

<?php foreach ($langs as $tag=>$nfo) {
        if ($tag == $cfg->getPrimaryLanguage())
            continue;
        $trans = $info['trans'][$tag]; ?>
    <div id="translation-<?php echo $tag; ?>" class="tab_content hidden"
        dir="<?php echo $nfo['direction']; ?>" lang="<?php echo $tag; ?>">
    <input type="text" style="width: 100%; font-size: 14pt"
        name="trans[<?php echo $tag; ?>][title]" value="<?php
        echo Format::htmlchars($trans['title']); ?>"
        placeholder="<?php echo __('Title'); ?>"  spellcheck="true"
        lang="<?php echo $tag; ?>" />
    <div style="margin-top: 5px">
    <textarea class="richtext no-bar" data-direction=<?php echo $nfo['direction']; ?>
        data-root-context="<?php echo $content->getType(); ?>"
        placeholder="<?php echo __('Message content'); ?>"
        name="trans[<?php echo $tag; ?>][body]"><?php
    echo Format::htmlchars(Format::viewableImages($trans['body']));
?></textarea>
    </div>
    </div>
<?php } ?>

    <div class="info-banner" style="margin-top:7px"><?php
echo $content->getNotes(); ?></div>

</div>

    <hr class="clear"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="<?php
                echo $user ? 'cancel' : 'close'; ?>" value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Save Changes'); ?>">
        </span>
     </p>
</div>
</form>
<div class="clear"></div>
