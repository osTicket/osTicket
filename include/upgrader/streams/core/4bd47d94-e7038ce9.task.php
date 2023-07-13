<?php

/*
 * Populate the temporary thread_entry_email table in batches
 * to avoid an alter table statement to add the email_id column.
 */
 require_once INCLUDE_DIR.'class.migrater.php';
 define('THREAD_ENTRY_EMAIL_NEW_TABLE', TABLE_PREFIX.'thread_entry_email_new');

 class ThreadEntryEmailNew extends VerySimpleModel {
    static $meta = array(
        'table' => THREAD_ENTRY_EMAIL_NEW_TABLE,
        'pk' => array('id'),
    );
 }

 class ThreadEntryEmailMigration extends MigrationTask {
     var $description = "Add an email_id column to the thread_entry_email table";
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

         $this->setStatus("{$this->getQueueLength()} records remaining");

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

         // Count how many rows are left
         $sql = "SELECT COUNT(old.id)
            FROM ". THREAD_ENTRY_EMAIL_TABLE." old
            WHERE old.id NOT IN (SELECT new.id FROM ". THREAD_ENTRY_EMAIL_NEW_TABLE ." new)";

         //XXX: Do a hard fail or error querying the database?
         if(!($res=db_query($sql)))
             return $this->error('Unable to query DB for Thread Entry Email migration!');

         $count = db_result($res);

         // Force the log message to the database
         $ost->logDebug("Thread Entry Email Migration", 'Found '.$count
             .' records to migrate', true);

         if($count == 0)
             return 0;  //Nothing else to do!!

         // Get id of the next record to be inserted into the new table
         $start = db_result(db_query("SELECT id
         FROM ". THREAD_ENTRY_EMAIL_TABLE ."
         WHERE id > (SELECT MAX(id) FROM ". THREAD_ENTRY_EMAIL_NEW_TABLE .")
         LIMIT 1"));
         $start = intval($start);

         $this->queue = array();
         $info=array(
             'count'        => $count,
             'start'        => $start,
             'end'          => $start + $limit
         );
         $this->enqueue($info);

         return $this->getQueueLength();
     }

     function skip($id, $error) {
         $this->skipList[] = $id;

         return $this->error($error." (ID #$id)");
     }

     function error($what) {
         global $ost;

         $this->errors++;
         $this->errorList[] = $what;
         // Log the error but don't send the alert email
         $ost->logError('Upgrader: Thread Entry Email Migrater', $what, false);
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
         return $this->queue[0]['count'];
     }

     function next() {
         # Fetch next item -- use the last item so the array indices don't
         # need to be recalculated for every shift() operation.
         $info = array_pop($this->queue);

         // Insert rows into the new table starting at the start and stopping at the end id
         $sql = "INSERT INTO ". THREAD_ENTRY_EMAIL_NEW_TABLE ."
             SELECT B.id, B.thread_entry_id, NULL, B.mid, B.headers
             FROM ". THREAD_ENTRY_EMAIL_TABLE . " B
             WHERE B.id >= ".$info['start']." AND B.id < ". $info['end'];

         db_query($sql);

         return true;
     }
 }
 return 'ThreadEntryEmailMigration';

?>
