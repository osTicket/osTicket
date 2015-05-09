<?php
$entryTypes = array('M'=>'message', 'R'=>'response', 'N'=>'note');
$user = $entry->getUser() ?: $entry->getStaff();
$name = $user ? $user->getName() : $entry->poster;
if ($user && ($url = $user->get_gravatar(48)))
    $avatar = "<img class=\"avatar\" src=\"{$url}\"> ";
?>

<div class="thread-entry <?php echo $entryTypes[$entry->type]; ?> <?php if ($avatar) echo 'avatar'; ?>">
<?php if ($avatar) { ?>
    <span class="<?php echo ($entry->type == 'M') ? 'pull-right' : 'pull-left'; ?> avatar">
<?php echo $avatar; ?>
    </span>
<?php } ?>
    <div class="header">
        <div class="pull-right">
<?php           if ($entry->hasActions()) {
            $actions = $entry->getActions(); ?>
            <span class="muted-button pull-right" data-dropdown="#entry-action-more-<?php echo $entry->getId(); ?>">
                <i class="icon-caret-down"></i>
            </span>
            <div id="entry-action-more-<?php echo $entry->getId(); ?>" class="action-dropdown anchor-right">
        <ul class="title">
<?php               foreach ($actions as $group => $list) {
                foreach ($list as $id => $action) { ?>
            <li>
            <a class="no-pjax" href="#" onclick="javascript:
                    <?php echo str_replace('"', '\\"', $action->getJsStub()); ?>; return false;">
                <i class="<?php echo $action->getIcon(); ?>"></i> <?php
                    echo $action->getName();
        ?></a></li>
<?php                   }
            } ?>
        </ul>
        </div>
<?php           } ?>
                <span style="vertical-align:middle;" class="textra">
        <?php if ($entry->flags & ThreadEntry::FLAG_EDITED) { ?>
                <span class="label label-bare" title="<?php
        echo sprintf(__('Edited on %s by %s'), Format::datetime($entry->updated), 'You');
                ?>"><?php echo __('Edited'); ?></span>
        <?php } ?>
                </span>
        </div>
<?php
            echo sprintf(__('<b>%s</b> posted %s'), $name,
                sprintf('<time class="relative" datetime="%s" title="%s">%s</time>',
                    date(DateTime::W3C, Misc::db2gmtime($entry->created)),
                    Format::daydatetime($entry->created),
                    Format::relativeTime(Misc::db2gmtime($entry->created))
                )
            ); ?>
            <span style="max-width:500px" class="faded title truncate"><?php
                echo $entry->title; ?></span>
            </span>
    </div>
    <div class="thread-body" id="thread-id-<?php
        echo $entry->getId(); ?>"><div><?php
        echo $entry->getBody()->toHtml(); ?></div>
    </div>
    <?php
    $urls = null;
    if ($entry->has_attachments
        && ($urls = $entry->getAttachmentUrls())) { ?>
    <div><?php
            foreach ($entry->attachments as $A) {
                if ($A->inline) continue;
                $size = '';
                if ($A->file->size)
                    $size = sprintf('<em>(%s)</em>',
                        Format::file_size($A->file->size));
?>
        <a class="Icon file no-pjax" href="<?php echo $A->file->getDownloadUrl();
            ?>" download="<?php echo Format::htmlchars($A->file->name); ?>"
            target="_blank"><?php echo Format::htmlchars($A->file->name);
        ?></a><?php echo $size;?>&nbsp;
<?php               } ?>
    </div> <?php
    }
    if ($urls) { ?>
        <script type="text/javascript">
            $('#thread-id-<?php echo $entry->getId(); ?>')
                .data('urls', <?php
                    echo JsonDataEncoder::encode($urls); ?>)
                .data('id', <?php echo $entry->getId(); ?>);
        </script>
<?php
    } ?>
</div>
