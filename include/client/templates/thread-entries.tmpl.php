<?php
$states = array('created', 'closed', 'reopened', 'edited', 'collab', 'merged');
$event_ids = array();
foreach ($states as $state) {
    $eid = Event::getIdByName($state);
    $event_ids[] = $eid;
}
$events = $events
    ->filter(array('event_id__in' => $event_ids))
    ->order_by('id');
$eventCount = count($events);
$events = new IteratorIterator($events->getIterator());
$events->rewind();
$event = $events->current();

$htmlId = $options['html-id'] ?: ('thread-'.$this->getId());

$tid = $this->getObJectId();
if ($this->getObjectType() == 'T')
    $ticket = Ticket::lookup($tid);
?>
<div id="<?php echo $htmlId; ?>" data-thread-id="<?php echo $this->getId(); ?>">
<?php
if (count($entries)) {
    $buckets = ThreadEntry::sortEntries($entries, $ticket);
    foreach ($buckets as $entry) {
        $extra = $entry->getMergeData();
        if ($entry->hasFlag(ThreadEntry::FLAG_CHILD) && $extra) {
            if (!is_array($extra))
                $extra = json_decode($extra, true);
            if (!$thread = Thread::objects()->filter(array('id'=>$extra['thread']))->values_flat('extra'))
                continue;
            foreach ($thread as $t)
                $threadExtra = $t[0];
            $threadExtra = json_decode($threadExtra, true);
            $number = $threadExtra['number'];
        } else
            $number = null;

        // Emit all events prior to this entry
        while ($event && $event->timestamp < $entry->created) {
            $event->render(ThreadEvent::MODE_CLIENT);
            $events->next();
            $event = $events->current();
        }
        ?><div id="thread-entry-<?php echo $entry->getId(); ?>"><?php
        include 'thread-entry.tmpl.php';
        ?></div><?php
    }
}

// Emit all other events
while ($event) {
    $event->render(ThreadEvent::MODE_CLIENT);
    $events->next();
    $event = $events->current();
}

// This should never happen
if (count($entries) + $eventCount == 0)
    echo '<p><em>'.__('No entries have been posted to this thread.').'</em></p>';
?>
</div>
