<?php
/*********************************************************************
    class.migrater.php

    Migration utils required by upgrader.
    
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/


/*
    DatabaseMigrater.

    SQL database migrater. This provides the engine capable of rolling the
    database for an osTicket installation forward (and perhaps even
    backward) in time using a set of included migration scripts. Each script
    will roll the database between two database checkpoints. Where possible,
    the migrater will roll several checkpoint scripts into one to be applied
    together.

*/

class DatabaseMigrater {

    var $start;
    var $end;
    var $sqldir;

    function DatabaseMigrater($start, $end, $sqldir) {
       
        $this->start = $start;
        $this->end = $end;
        $this->sqldir = $sqldir;
       
    }

    function getPatches($stop=null) {

        $start= $this->start;
        $stop = $stop?$stop:$this->end;

        $patches = array();
        while (true) {
            $next = glob($this->sqldir . substr($start, 0, 8)
                         . '-*.patch.sql');
            if (count($next) == 1) {
                $patches[] = $next[0];
                $start = substr(basename($next[0]), 9, 8);
            } elseif (count($next) == 0) {
                # There are no patches leaving the current signature. We
                # have to assume that we've applied all the available
                # patches.
                break;
            } else {
                # Problem -- more than one patch exists from this snapshot.
                # We probably need a graph approach to solve this.
                break;
            }

            # Break if we've reached our target stop.
            if(!$start || !strncasecmp($start, $stop, 8))
                break;
        }

        return $patches;
    }
}


/*
    AttachmentMigrater

    Attachment migration from file-based attachments in pre-1.7 to
    database-backed attachments in osTicket v1.7. This class provides the
    hardware to find and retrieve old attachments and move them into the new
    database scheme with the data in the actual database.

    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
*/

include_once(INCLUDE_DIR.'class.file.php');

class AttachmentMigrater {
    function AttachmentMigrater() {
        $this->queue = array();
        $this->current = 0;
        $this->errors = 0;
        $this->errorList = array();
    }
    /**
     * Identifies attachments in need of migration and queues them for
     * migration to the new database schema.
     * 
     * @see ::next() for output along the way
     *
     * Returns:
     * TRUE/FALSE to indicate if migration finished without any errors
     */
    function start_migration() {
        $this->findAttachments();
        $this->total = count($this->queue);
    }
    /**
     * Process the migration for a unit of time. This will be used to
     * overcome the execution time restriction of PHP. This instance can be
     * stashed in a session and have this method called periodically to
     * process another batch of attachments
     */
    function do_batch($max, $time=20) {
        $start = time();
        $this->errors = 0;
        while (count($this->queue) && $count++ < $max && time()-$start < $time)
            $this->next();
        # TODO: Log success/error indication of migration of attachments
        return (!$this->errors);
    }

    function queue($fileinfo) {
        $this->queue[] = $fileinfo;
    }
    function getQueueLength() { return count($this->queue); }
    /**
     * Processes the next item on the work queue. Emits a JSON messages to
     * indicate current progress.
     *
     * Returns:
     * TRUE/NULL if the migration was successful
     */
    function next() {
        # Fetch next item -- use the last item so the array indices don't
        # need to be recalculated for every shift() operation.
        $info = array_pop($this->queue);
        # Attach file to the ticket
        if (!($info['data'] = @file_get_contents($info['path']))) {
            # Continue with next file
            return $this->error(
                sprintf('%s: Cannot read file contents', $info['path']));
        }
        # Get the mime/type of each file
        # XXX: Use finfo_buffer for PHP 5.3+
        $info['type'] = mime_content_type($info['path']);
        if (!($fileId = AttachmentFile::save($info))) {
            return $this->error(
                sprintf('%s: Unable to migrate attachment', $info['path']));
        }
        # Update the ATTACHMENT_TABLE record to set file_id
        db_query('update '.TICKET_ATTACHMENT_TABLE
                .' set file_id='.db_input($fileId)
                .' where attach_id='.db_input($info['attachId']));
        # Remove disk image of the file. If this fails, the migration for
        # this file would not be retried, because the file_id in the
        # TICKET_ATTACHMENT_TABLE has a nonzero value now
        if (!@unlink($info['path']))
            $this->error(
                sprintf('%s: Unable to remove file from disk',
                $info['path']));
        # TODO: Log an internal note to the ticket?
        return true;
    }
    /**
     * From (class Ticket::fixAttachments), used to detect the locations of
     * attachment files
     */
    /* static */ function findAttachments(){
        global $cfg;

        $res=db_query('SELECT attach_id, file_name, file_key, Ti.created'
            .' FROM '.TICKET_ATTACHMENT_TABLE.' TA'
            .' JOIN '.TICKET_TABLE.' Ti ON Ti.ticket_id=TA.ticket_id'
            .' WHERE NOT file_id');
        if (!$res) {
            return $this->error('Unable to query for attached files');
        } elseif (!db_num_rows($res)) {
            return true;
        }
        $dir=$cfg->getUploadDir();
        while (list($id,$name,$key,$created)=db_fetch_row($res)) {
            $month=date('my',strtotime($created));
            $info=array(
                'name'=>        $name,
                'attachId'=>    $id,
            );
            $filename15=sprintf("%s/%s_%s",rtrim($dir,'/'),$key,$name);
            $filename16=sprintf("%s/%s/%s_%s",rtrim($dir,'/'),$month,$key,$name); //new destination.
            if (file_exists($filename15)) { 
                $info['path'] = $filename15;
            } elseif (file_exists($filename16)) {  
                $info['path'] = $filename16;
            } else {
                # XXX Cannot find file for attachment
                $this->error(sprintf('%s: Unable to locate attachment file',
                    $name));
                # No need to further process this file
                continue;
            }
            # TODO: Get the size and mime/type of each file.
            #
            # NOTE: If filesize() fails and file_get_contents() doesn't,
            # then the AttachmentFile::save() method will automatically
            # estimate the filesize based on the length of the string data
            # received in $info['data'] -- ie. no need to do that here.
            #
            # NOTE: The size is done here because it should be quick to
            # lookup out of file inode already loaded. The mime/type may
            # take a while because it will require a second IO to read the
            # file data.  To ensure this will finish before the
            # max_execution_time, perform the type match in the ::next()
            # method since the entire file content will be read there
            # anyway.
            $info['size'] = @filesize($info['path']);
            # Coroutines would be nice ..
            $this->queue($info);
        }
    }

    function error($what) {
        $this->errors++;
        $this->errorList[] = $what;
        # Assist in returning FALSE for inline returns with this method
        return false;
    }
    function getErrors() {
        return $this->errorList;
    }
}

?>
