<?php

class EventEnumRemoval extends MigrationTask {
    var $description = "Remove the Enum 'state' field from ThreadEvents";
    var $queue;
    var $skipList;
    var $errorList = array();
    var $limit = 20000;

    function sleep() {
        return array('queue'=>$this->queue, 'skipList'=>$this->skipList);
    }
    function wakeup($stuff) {
        $this->queue = $stuff['queue'];
        $this->skipList = $stuff['skipList'];
        while (!$this->isFinished())
            $this->do_batch(30, $this->limit);
    }

    function run($max_time) {
        $this->do_batch($max_time * 0.9, $this->limit);
    }

    function isFinished() {
        return $this->getQueueLength() == 0;
    }

    function do_batch($time=30, $max=0) {
        if(!$this->queueEvents($max) || !$this->getQueueLength())
            return 0;

        $this->setStatus("{$this->getQueueLength()} events remaining");

        $count = 0;
        $start = Misc::micro_time();
        while ($this->getQueueLength() && (Misc::micro_time()-$start) < $time) {
            if($this->next() && $max && ++$count>=$max) {
                break;
            }
        }

        return $this->queueEvents($max);
    }

    function queueEvents($limit=0){
        global $cfg, $ost;

        # Since the queue is persistent - we want to make sure we get to empty
        # before we find more events.
        if(($qc=$this->getQueueLength()))
            return $qc;

        $sql = "SELECT COUNT(t.id) FROM ".THREAD_EVENT_TABLE. " t
            INNER JOIN ".EVENT_TABLE. " e ON (e.name=t.state)
            WHERE t.event_id IS NULL";

        //XXX: Do a hard fail or error querying the database?
        if(!($res=db_query($sql)))
            return $this->error('Unable to query DB for Thread Event migration!');

        $count = db_result($res);

        // Force the log message to the database
        $ost->logDebug("Thread Event Migration", 'Found '.$count
            .' events to migrate', true);

        if($count == 0)
            return 0;  //Nothing else to do!!

        $start = db_result(db_query("SELECT id FROM ".THREAD_EVENT_TABLE. "
            WHERE event_id IS NULL
            ORDER BY id ASC LIMIT 1"));

        $this->queue = array();
        $info=array(
            'count'        => $count,
            'start'        => $start,
            'end'          => $start + $limit
        );
        $this->enqueue($info);

        return $this->getQueueLength();
    }

    function skip($eventId, $error) {
        $this->skipList[] = $eventId;

        return $this->error($error." (ID #$eventId)");
    }

    function error($what) {
        global $ost;

        $this->errors++;
        $this->errorList[] = $what;
        // Log the error but don't send the alert email
        $ost->logError('Upgrader: Thread Event Migrater', $what, false);
        # Assist in returning FALSE for inline returns with this method
        return false;
    }

    function getErrors() {
        return $this->errorList;
    }

    function getSkipList() {
        return $this->skipList;
    }

    function enqueue($info) {
        $this->queue[] = $info;
    }

    function getQueueLength() {
        return count($this->queue);
    }

    function next() {
        # Fetch next item -- use the last item so the array indices don't
        # need to be recalculated for every shift() operation.
        $info = array_pop($this->queue);

        $sql = "UPDATE ".THREAD_EVENT_TABLE. " t
            INNER JOIN ".EVENT_TABLE. " e ON (e.name=t.state)
            SET t.event_id = e.id
            WHERE t.event_id IS NULL AND t.id <= ". $info['end'];

        db_query($sql);

        return true;
    }
}
return 'EventEnumRemoval';
?>
