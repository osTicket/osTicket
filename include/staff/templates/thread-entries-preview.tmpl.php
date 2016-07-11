<?php
$entryTypes = ThreadEntry::getTypes();
$entries = $ticket->getThread()->getEntries();
$entries
    ->filter(array('type__in' => array_keys($entryTypes)))
    ->order_by('-created')
    ->limit(6);
$i = 0;
foreach ($entries as $entry) {
    $user = $entry->getUser() ?: $entry->getStaff();
    $name = $user ? $user->getName() : $entry->poster;
    $i++;
?>
    <div id="thread-entry-<?php echo $enry->id; ?>">
        <div class="thread-preview-entry <?php
        echo $i>0 ? 'collapsed ' : ' ';
        echo $entryTypes[$entry->type]; ?>">
            <div class="header">
               <div class="thread-info">
                    <?php
                    echo sprintf('<div class="thread-name">
                            <span>%s</span>&nbsp;<span>%s</span></div>',
                            $name,
                            Format::datetime($entry->created));
                    ?>
                </div>
            </div>
            <div class="thread-body no-pjax">
                <div class="thread-teaser">
                <?php echo $entry->getBody()->toHtml(); ?>
                </div>
                <div class="clear"></div>
                <?php
                    $atts = isset($thread_attachments) ?
                    $thread_attachments[$entry->id] : $entry->attachments;
                    if (isset($atts) && $atts) {
                ?>
                    <div class="attachments"><?php
                        foreach ($atts as $A) {
                            if ($A->inline)
                                continue;
                            $size = '';
                            if ($A->file->size)
                                $size = sprintf('<small class="filesize faded">%s</small>', Format::file_size($A->file->size));
                ?>
                        <span class="attachment-info">
                        <i class="icon-paperclip icon-flip-horizontal"></i>
                        <a class="no-pjax truncate filename" href="<?php echo $A->file->getDownloadUrl();
                            ?>" download="<?php echo Format::htmlchars($A->getFilename()); ?>"
                            target="_blank"><?php echo Format::htmlchars($A->getFilename());
                        ?></a><?php echo $size;?>
                        </span>
                <?php   }
                    echo '</div><div class="clear"></div>';
                    }
                ?>

            </div>
        </div>
    </div>
<?php
} ?>
