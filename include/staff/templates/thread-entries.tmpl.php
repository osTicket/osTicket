<?php
$entryTypes = array('M'=>'message', 'R'=>'response', 'N'=>'note');
if ($entries) {
    foreach ($entries as $entry) { ?>
    <table class="thread-entry <?php echo $entryTypes[$entry->type]; ?>" cellspacing="0" cellpadding="1" width="940" border="0">
        <tr>
            <th colspan="4" width="100%">
            <div>
                <span class="pull-left">
                <span style="display:inline-block"><?php
                    echo Format::datetime($entry->created);?></span>
                <span style="display:inline-block;padding:0 1em;max-width: 500px" class="faded title truncate"><?php
                    echo $entry->title; ?></span>
                </span>
            <div class="pull-right">
<?php           if ($entry->hasActions()) {
                $actions = $entry->getActions(); ?>
                <span class="action-button pull-right" data-dropdown="#entry-action-more-<?php echo $entry->getId(); ?>">
                    <i class="icon-caret-down"></i>
                    <span ><i class="icon-cog"></i></span>
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
                <span style="vertical-align:middle">
                    <span style="vertical-align:middle;" class="textra"></span>
                    <span style="vertical-align:middle;"
                        class="tmeta faded title"><?php
                        echo Format::htmlchars($entry->getName()); ?></span>
                </span>
            </div>
            </th>
        </tr>
        <tr><td colspan="4" class="thread-body" id="thread-id-<?php
            echo $entry->getId(); ?>"><div><?php
            echo $entry->getBody()->toHtml(); ?></div></td></tr>
        <?php
        $urls = null;
        if ($entry->has_attachments
            && ($urls = $entry->getAttachmentUrls())) { ?>
        <tr>
            <td class="info" colspan="4"><?php
                foreach ($entry->attachments as $A) {
                    if ($A->inline) continue;
                    $size = '';
                    if ($A->file->size)
                        $size = sprintf('<em>(%s)</em>',
                            Format::file_size($A->file->size));
?>
            <a class="Icon file no-pjax" href="<?php echo $A->file->getDownloadUrl();
                ?>" target="_blank"><?php echo Format::htmlchars($A->file->name);
            ?></a><?php echo $size;?>&nbsp;
<?php               } ?>
            </td>
        </tr> <?php
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
    </table>
    <?php
    }
} else {
    echo '<p><em>'.__('No entries have been posted to this thread.').'</em></p>';
}?>
