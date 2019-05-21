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

class Cron {

    function MailFetcher() {
        MailFetcher::run(); //Fetch mail..frequency is limited by email account setting.
    }

    function TicketMonitor() {
        Ticket::checkOverdue(); //Make stale tickets overdue
        // Cleanup any expired locks
        Lock::cleanup();

    }

    function PurgeLogs() {
        global $ost;
        // Once a day on a 5-minute cron
        if (rand(1,300) == 42)
            if($ost) $ost->purgeLogs();
    }

    function PurgeDrafts() {
        Draft::cleanup();
    }

    function CleanOrphanedFiles() {
        AttachmentFile::deleteOrphans();
    }

    function CleanExpiredSessions() {
        require_once(INCLUDE_DIR.'class.ostsession.php');
        DbSessionBackend::cleanup();
    }

    function CleanPwResets() {
        require_once(INCLUDE_DIR.'class.config.php');
        ConfigItem::cleanPwResets();
    }

    function MaybeOptimizeTables() {
        // Once a week on a 5-minute cron
        $chance = rand(1,2000);
        switch ($chance) {
        case 42:
            @db_query('OPTIMIZE TABLE '.LOCK_TABLE);
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

    function run(){ //called by outside cron NOT autocron
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
