<?php
$events = $events->order_by('id');
$events = $events->getIterator();
$events->rewind();
$event = $events->current();

if (count($entries)) {
    foreach ($entries as $entry) {
        // Emit all events prior to this entry
        while ($event && $event->timestamp <= $entry->created) {
            $event->render(ThreadEvent::MODE_STAFF);
            $events->next();
            $event = $events->current();
        }
        include STAFFINC_DIR . 'templates/thread-entry.tmpl.php';
    }
    // Emit all other events
    while ($event) {
        $event->render(ThreadEvent::MODE_STAFF);
        $events->next();
        $event = $events->current();
    }
}
else {
    echo '<p><em>'.__('No entries have been posted to this thread.').'</em></p>';
}
