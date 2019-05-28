<?php

$sort = 'id';
if ($options['sort'] && !strcasecmp($options['sort'], 'DESC'))
    $sort = '-id';

$cmp = function ($a, $b) use ($sort) {
    return ($sort == 'id')
        ? ($a < $b) : $a > $b;
};

$events = $events->order_by($sort);
$eventCount = count($events);
$events = new IteratorIterator($events->getIterator());
$events->rewind();
$event = $events->current();
$htmlId = $options['html-id'] ?: ('thread-'.$this->getId());

$thread_attachments = array();
foreach (Attachment::objects()->filter(array(
    'thread_entry__thread__id' => $this->getId(),
))->select_related('thread_entry', 'file') as $att) {
    $thread_attachments[$att->object_id][] = $att;
}

$tid = $this->getObJectId();
if ($this->getObjectType() == 'T')
    $ticket = Ticket::lookup($tid);

?>
<div id="<?php echo $htmlId; ?>">
    <div id="thread-items" data-thread-id="<?php echo $this->getId(); ?>">
    <?php
    if ($entries->exists(true)) {
        // Go through all the entries and bucket them by time frame
        $buckets = array();
        $childEntries = array();
        foreach ($entries as $i=>$E) {
            if ($ticket) {
                $extra = json_decode($E->extra, true);
                //separated entries
                if ($ticket->getMergeType() == 'separate') {
                    if ($extra['thread'])
                        $childEntries[$E->getId()] = $E;
                    else
                        $buckets[$E->getId()] = $E;
                } else
                    $buckets[$E->getId()] = $E;
            } else
                $buckets[$E->getId()] = $E;
        }

        if ($ticket && $ticket->getMergeType() == 'separate')
            $buckets = $buckets + $childEntries;

        // TODO: Consider adding a date boundary to indicate significant
        //       changes in dates between thread items.

        foreach ($buckets as $entry) {
            if ($entry->hasFlag(ThreadEntry::FLAG_CHILD) && $entry->extra) {
                $extra = json_decode($entry->extra, true);
                $indent = true;
                if ($extra['number'])
                    $number = $extra['number'];
                else {
                    if (!$thread = Thread::lookup($extra['thread']))
                        continue;
                    $threadExtra = json_decode($thread->extra, true);
                    $number = $threadExtra['number'];
                }

            }
            else
                $number = null;

            // Emit all events prior to this entry
            while ($event && $cmp($event->timestamp, $entry->created)) {
                $event->render(ThreadEvent::MODE_STAFF);
                $events->next();
                $event = $events->current();
            }
            ?><div id="thread-entry-<?php echo $entry->getId(); ?>"><?php
            include STAFFINC_DIR . 'templates/thread-entry.tmpl.php';
            ?></div><?php
        }
    }

    // Emit all other events
    while ($event) {
        $event->render(ThreadEvent::MODE_STAFF);
        $events->next();
        $event = $events->current();
    }
    // This should never happen
    if (count($entries) + $eventCount == 0) {
        echo '<p><em>'.__('No entries have been posted to this thread.').'</em></p>';
    }
    ?>
    </div>
</div>
<script type="text/javascript">
    $(function() {
        var container = '<?php echo $htmlId; ?>';

        // Set inline image urls.
        <?php
        $urls = array();
        foreach ($thread_attachments as $eid=>$atts) {
            foreach ($atts as $A) {
                if (!$A->inline)
                    continue;
                $urls[strtolower($A->file->getKey())] = array(
                    'download_url' => $A->file->getDownloadUrl(['id' =>
                        $A->getId()]),
                    'filename' => $A->getFilename(),
                );
            }
        }
        ?>
        $('#'+container).data('imageUrls', <?php echo JsonDataEncoder::encode($urls); ?>);
        // Trigger thread processing.
        if ($.thread)
            $.thread.onLoad(container,
                    {autoScroll: <?php echo $sort == 'id' ? 'true' : 'false'; ?>});
    });
</script>
