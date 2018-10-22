<?php

class EventEnumRemoval extends MigrationTask {
    var $description = "Remove the Enum 'state' field from ThreadEvents";
    var $queue;
    var $skipList;
    var $errorList = array();

    function sleep() {
        return array('queue'=>$this->queue, 'skipList'=>$this->skipList);
    }
    function wakeup($stuff) {
        $this->queue = $stuff['queue'];
        $this->skipList = $stuff['skipList'];
        while (!$this->isFinished())
            $this->do_batch(30, 5000);
    }

    function run($max_time) {
        $this->do_batch($max_time * 0.9, 5000);
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

        $sql = "SELECT this.id, this.state, that.id, that.name FROM ".THREAD_EVENT_TABLE. " this
            INNER JOIN (
            SELECT id, name
            FROM ". EVENT_TABLE. ") that
            WHERE this.state = that.name
            AND event_id IS NULL";

        if(($skipList=$this->getSkipList()))
            $sql.= ' AND this.id NOT IN('.implode(',', db_input($skipList)).')';

        if($limit && is_numeric($limit))
            $sql.=' LIMIT '.$limit;

        //XXX: Do a hard fail or error querying the database?
        if(!($res=db_query($sql)))
            return $this->error('Unable to query DB for Thread Event migration!');

        // Force the log message to the database
        $ost->logDebug("Thread Event Migration", 'Found '.db_num_rows($res)
            .' events to migrate', true);

        if(!db_num_rows($res))
            return 0;  //Nothing else to do!!

        $this->queue = array();
        while (list($id, $state, $eventId, $eventName)=db_fetch_row($res)) {
            $info=array(
                'id'        => $id,
                'state'     => $state,
                'eventId'   => $eventId,
                'eventName' => $eventName,
            );
            $this->enqueue($info);
        }

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

        if (!$info['state']) {
            # Continue with next thread event
            return $this->skip($info['eventId'],
            sprintf('Thread Event ID %s: State is blank', $info['eventId']));
        }

        db_query('update '.THREAD_EVENT_TABLE
            .' set event_id='.db_input($info['eventId'])
            .' where state='.db_input($info['eventName'])
            .' and id='.db_input($info['id']));

        return true;
    }
}
return 'EventEnumRemoval';
?>
