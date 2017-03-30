<?php
$events = $events
    ->filter(array('state__in' => array('created', 'closed', 'reopened', 'edited', 'collab')))
    ->order_by('id');
$events = new IteratorIterator($events->getIterator());
$events->rewind();
$event = $events->current();

$htmlId = $options['html-id'] ?: ('thread-'.$this->getId());
?>
<div id="<?php echo $htmlId; ?>" data-thread-id="<?php echo $this->getId(); ?>">
<?php
if (count($entries)) {
    // Go through all the entries and bucket them by time frame
    $buckets = array();
    $rel = 0;
    foreach ($entries as $i=>$E) {
        // First item _always_ shows up
        if ($i != 0)
            // Set relative time resolution to 12 hours
            $rel = Format::relativeTime(Misc::db2gmtime($E->created, false, 43200));
        $buckets[$rel][] = $E;
    }

    // Go back through the entries and render them on the page
    $i = 0;
    foreach ($buckets as $rel=>$entries) {
        // TODO: Consider adding a date boundary to indicate significant
        //       changes in dates between thread items.
        foreach ($entries as $entry) {
            // Emit all events prior to this entry
            while ($event && $event->timestamp < $entry->created) {
                $event->render(ThreadEvent::MODE_CLIENT);
                $events->next();
                $event = $events->current();
            }
            include 'thread-entry.tmpl.php';
        }
        $i++;
    }
}

// Emit all other events
while ($event) {
    $event->render(ThreadEvent::MODE_CLIENT);
    $events->next();
    $event = $events->current();
}

// This should never happen
if (count($entries) + count($events) == 0) {
    echo '<p><em>'.__('No entries have been posted to this thread.').'</em></p>';
}
?>
</div>
