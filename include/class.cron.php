<?php
/*********************************************************************
    class.cron.php

    Nothing special...just a central location for all cron calls.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    TODO: The plan is to make cron jobs db based.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
//TODO: Make it DB based!
require_once INCLUDE_DIR.'class.signal.php';

class Cron {

    static function MailFetcher() {
        require_once(INCLUDE_DIR.'class.email.php');
        osTicket\Mail\Fetcher::run(); //Fetch mail..frequency is limited by email account setting.
    }

    static function TicketMonitor() {
        require_once(INCLUDE_DIR.'class.ticket.php');
        Ticket::checkOverdue(); //Make stale tickets overdue
        // Cleanup any expired locks
        require_once(INCLUDE_DIR.'class.lock.php');
        Lock::cleanup();

    }

    static function PurgeLogs() {
        global $ost;
        // Once a day on a 5-minute cron
        if (rand(1,300) == 42)
            if($ost) $ost->purgeLogs();
    }

    static function PurgeDrafts() {
        require_once(INCLUDE_DIR.'class.draft.php');
        Draft::cleanup();
    }

    static function CleanOrphanedFiles() {
        require_once(INCLUDE_DIR.'class.file.php');
        AttachmentFile::deleteOrphans();
    }

    static function CleanExpiredSessions() {
        require_once(INCLUDE_DIR.'class.ostsession.php');
        osTicketSession::cleanup();
    }

    static function CleanPwResets() {
        require_once(INCLUDE_DIR.'class.config.php');
        ConfigItem::cleanPwResets();
    }

    static function MaybeOptimizeTables() {
        // Once a week on a 5-minute cron
        $chance = rand(1,2000);
        switch ($chance) {
        case 42:
            @db_query('OPTIMIZE TABLE `'.LOCK_TABLE.'`');
            break;
        case 242:
            @db_query('OPTIMIZE TABLE '.SYSLOG_TABLE);
            break;
        case 442:
            @db_query('OPTIMIZE TABLE '.DRAFT_TABLE);
            break;

        // Start optimizing core ticket tables when we have an archiving
        // system available
        case 142:
            #@db_query('OPTIMIZE TABLE '.TICKET_TABLE);
            break;
        case 542:
            #@db_query('OPTIMIZE TABLE '.FORM_ENTRY_TABLE);
            break;
        case 642:
            #@db_query('OPTIMIZE TABLE '.FORM_ANSWER_TABLE);
            break;
        case 342:
            #@db_query('OPTIMIZE TABLE '.FILE_TABLE);
            # XXX: Please do not add an OPTIMIZE for the file_chunk table!
            break;

        // Start optimizing user tables when we have a user directory
        // sporting deletes
        case 742:
            #@db_query('OPTIMIZE TABLE '.USER_TABLE);
            break;
        case 842:
            #@db_query('OPTIMIZE TABLE '.USER_EMAIL_TABLE);
            break;
        }
    }

    static function run(){ //called by outside cron NOT autocron
        global $ost;
        if (!$ost || $ost->isUpgradePending())
            return;

        self::MailFetcher();
        self::TicketMonitor();
        self::PurgeLogs();
        self::CleanExpiredSessions();
        self::CleanPwResets();
        // Run file purging about every 10 cron runs
        if (mt_rand(1, 9) == 4)
            self::CleanOrphanedFiles();
        self::PurgeDrafts();
        self::MaybeOptimizeTables();

        $data = array('autocron'=>false);
        Signal::send('cron', null, $data);
    }
}
?>
