<?php
/*********************************************************************
    class.mailfetcher.php

    osTicket/Mail/Fetcher

    Peter Rotich <peter@osticket.com>, Kevin Thorne <kevin@osticket.com>
    Copyright (c)  osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

namespace osTicket\Mail;

class Fetcher {
    private $account;
    private $mbox;
    private $api;

    function __construct(\MailboxAccount $account, $charset='UTF-8') {
        $this->account = $account;
        $this->mbox = $account->getMailBox();
        if (($folder = $this->getFetchFolder()))
            $this->mbox->selectFolder($folder);
    }

    function getEmailId() {
        return $this->account->getEmailId();
    }

    function getEmail() {
        return $this->account->getEmail();
    }

    function getEmailAddress() {
        return $this->account->getEmail()->getAddress();
    }

    function getMaxFetch() {
        return $this->account->getMaxFetch();
    }

    function getFetchFolder() {
        return $this->account->getFetchFolder();
    }

    function getArchiveFolder() {
        return $this->account->getArchiveFolder();
    }

    function canDeleteEmails() {
         return $this->account->canDeleteEmails();
    }


    function getTicketsApi() {
        // We're forcing CLI interface - this is absolutely necessary since
        // Email Fetching is considered a CLI operation regardless of how
        // it's triggered (cron job / task or autocron)

        // Please note that PHP_SAPI cannot be trusted for installations
        // using php-fpm or php-cgi binaries for php CLI executable.

        if (!isset($this->api))
            $this->api = new \TicketApiController('cli');

        return $this->api;
    }

    function noop() {
        return ($this->mbox && $this->mbox->noop());
    }

    function processMessage(int $i, array $defaults = []) {
        try {
            // Please note that the returned object could be anything from
            // ticket, task to thread entry or a boolean.
            // Don't let TicketApi call fool you!
            return $this->getTicketsApi()->processEmail(
                    $this->mbox->getRawEmail($i), $defaults);
        } catch (\TicketDenied $ex) {
            // If a ticket is denied we're going to report it as processed
            // so it can be moved out of the Fetch Folder or Deleted based
            // on the MailBox settings.
            return true;
        } catch (\EmailParseError $ex) {
            // Upstream we try to create a ticket on email parse error - if
            // it fails then that means we have invalid headers.
            // For Debug purposes log the parse error + headers as a warning
            $this->logWarning(sprintf("%s\n\n%s",
                        $ex->getMessage(),
                        $this->mbox->getRawHeader($i)));
        }
        return false;
    }

    function processEmails() {
        // We need a connection
        if (!$this->mbox)
            return false;

        // Get basic fetch settings
        $archiveFolder = $this->getArchiveFolder();
        $deleteFetched =  $this->canDeleteEmails();
        $max = $this->getMaxFetch() ?: 30; // default to 30 if not set

        // Get message count in the Fetch Folder
        if (!($messageCount = $this->mbox->countMessages()))
            return 0;

        // If the number of emails in the folder are more than Max Fetch
        // then process the latest $max emails - this is necessary when
        // fetched emails are not getting archived or deleted, which might
        // lead to fetcher being stuck 4ever processing old emails already
        // fetched
        if ($messageCount > $max) {
            // Latest $max messages
            $messages = range($messageCount-$max, $messageCount);
        } else {
            // Create a range of message sequence numbers (msgno) to fetch
            // starting from the oldest taking max fetch into account
            $messages = range(1, min($max, $messageCount));
        }

        $defaults = [
            'emailId' => $this->getEmailId()
        ];
        $msgs = $errors = 0;
        // TODO: Use message UIDs instead of ids
        foreach ($messages as $i) {
            try {
                // Okay, let's try to create a ticket
                if (($result=$this->processMessage($i, $defaults))) {
                    // Mark the message as "Seen" (IMAP only)
                    $this->mbox->markAsSeen($i);
                    // Attempt to move the message if archive folder is set or
                    if ($archiveFolder)
                        $this->mbox->moveMessage($i, $archiveFolder);
                    elseif ($deleteFetched)  // else delete if deletion is desired
                        $this->mbox->removeMessage($i);
                    $msgs++;
                    $errors = 0; // We are only interested in consecutive errors.
                } else {
                    $errors++;
                }
            } catch (\Throwable $t) {
                // If we have result then exception happened after email
                // processing and shouldn't count as an error
                if (!$result)
                    $errors++;
                // log the exception as a debug message
                $this->logDebug($t->getMessage());
            }
        }

        // Expunge the mailbox
        $this->mbox->expunge();

        // Warn on excessive errors - when errors are more than email
        // processed successfully.
        if ($errors > $msgs) {
            $warn = sprintf("%s\n\n%s [%d/%d - %d/%d]",
                    // Mailbox Info
                    sprintf(_S('Excessive errors processing emails for %1$s (%2$s).'),
                        $this->mbox->getHostInfo(), $this->getEmail()),
                    // Fetch Folder
                    sprintf('%s (%s)',
                        _S('Please manually check the Fetch Folder'),
                        $this->getFetchFolder()),
                    // Counts - sort of cryptic but useful once we document
                    // what it means
                    $messageCount, $max, $msgs, $errors);
            $this->logWarning($warn);
        }
        return $msgs;
    }

    private function logDebug($msg) {
        $this->log($msg, LOG_DEBUG);
    }

    private function logWarning($msg) {
        $this->log($msg, LOG_WARN);
    }

    private function log($msg, $level = LOG_WARN) {
        global $ost;
        $subj = _S('Mail Fetcher');
        switch ($level) {
            case LOG_WARN:
                $ost->logWarning($subj, $msg);
                break;
            case  LOG_DEBUG:
            default:
                $ost->logDebug($subj, $msg);
        }
    }

    /*
       MailFetcher::run()

       Static function called to initiate email polling
     */
    static function run() {
        global $ost;

        if(!$ost->getConfig()->isEmailPollingEnabled())
            return;

        //Hardcoded error control...
        $MAXERRORS = 5; //Max errors before we start delayed fetch attempts
        $TIMEOUT = 10; //Timeout in minutes after max errors is reached.
        $now = \SqlFunction::NOW();
        // Num errors + last error
        $interval = new \SqlInterval('MINUTE', $TIMEOUT);
        $errors_Q = \Q::any([
                'num_errors__lte' => $MAXERRORS,
                new \Q(['last_error__lte' => $now->minus($interval)])
        ]);
        // Last fetch + frequency
        $interval = new \SqlInterval('MINUTE', \SqlExpression::plus(new
                     \SqlCode('fetchfreq'), 0));
        $fetch_Q = \Q::any([
                'last_activity__isnull' => true,
                new \Q(['last_activity__lte' => $now->minus($interval)])
        ]);

        $mailboxes = \MailBoxAccount::objects()
            ->filter(['active' => 1, $errors_Q, $fetch_Q])
            ->order_by('last_activity');

        //Get max execution time so we can figure out how long we can fetch
        // take fetching emails.
        if (!($max_time = ini_get('max_execution_time')))
            $max_time = 300;

        //Start time
        $start_time = \Misc::micro_time();
        foreach ($mailboxes as $mailbox) {
            // Check if the mailbox is active 4realz by getting credentials
            if (!$mailbox->isActive())
                continue;

            // Break if we're 80% into max execution time
            if ((\Misc::micro_time()-$start_time) > ($max_time*0.80))
                break;

            // Try fetching emails
            try {
                $mailbox->fetchEmails();
            } catch (\Throwable $t) {
                if ($mailbox->getNumErrors() >= $MAXERRORS && $ost) {
                    //We've reached the MAX consecutive errors...will attempt logins at delayed intervals
                    // XXX: Translate me
                    $msg = sprintf("\n %s:\n",
                            _S('osTicket is having trouble fetching emails from the following mail account')).
                        "\n"._S('Email').": ".$mailbox->getEmail()->getAddress().
                        "\n"._S('Host Info').": ".$mailbox->getHostInfo();
                        "\n"._S('Error').": ".$t->getMessage().
                        "\n\n ".sprintf(_S('%1$d consecutive errors. Maximum of %2$d allowed'),
                                $mailbox->getNumErrors(), $MAXERRORS).
                        "\n\n ".sprintf(_S('This could be connection issues related to the mail server. Next delayed login attempt in aprox. %d minutes'), $TIMEOUT);
                    $ost->alertAdmin(_S('Mail Fetch Failure Alert'), $msg, true);
                }
            }
        } //end foreach.
    }
}
?>
