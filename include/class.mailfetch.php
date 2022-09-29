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
        if ($folder = $this->getFolder())
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

    function getFolder() {
        return $this->account->getFolder();
    }

    function getArchiveFolder() {
        return $this->account->getArchiveFolder();
    }

    function canDeleteEmails() {
         return $this->account->canDeleteEmails();
    }

    function getTicketsApi() {
        if (!isset($this->api))
            $this->api = new \TicketApiController();

        return $this->api;
    }

    function noop() {
        return ($this->mbox && $this->mbox->noop());
    }

    function createTicket(int $i) {
        try {
            return $this->getTicketsApi()->processEmail(
                    $this->mbox->getRawEmail($i));
        } catch (\TicketDenied $ex) {
            // If a ticket is denied we're going to report it as processed
            // so it can be moved out of the inbox or deleted.
            return true;
        } catch (\EmailParseError $ex) {
            // Log the parse error + headers as a warning
            $this->log(sprintf("%s\n\n%s",
                        $ex->getMessage(),
                        $this->mbox->getRawHeader($i)));
        } catch (\Throwable $t) {
            //noop
        }
        return false;
    }

    function processEmails() {
        // We need a connection
        if(!$this->mbox)
            return false;

        // Get basic fetch settings
        $archiveFolder = $this->getArchiveFolder();
        $delete = $this->canDeleteEmails();
        $max = $this->getMaxFetch();
        // Get full message count
        $messageCount = $this->mbox->countMessages();
        $msgs = $errors = 0;
        for($i = $messageCount; $i > 0; $i--) { // Process messages in reverse.
            // Okay, let's create the ticket now
            if ($this->createTicket($i)) {
                // Mark the message as "Seen" (IMAP only)
                $this->mbox->markAsSeen($i);
                // Attempt to move the message else attempt to delete
                if((!$archiveFolder || !$this->mbox->moveMessage($i, $archiveFolder)) && $delete)
                    $this->mbox->removeMessage($i);

                $msgs++;
                $errors = 0; // We are only interested in consecutive errors.
            } else {
                $errors++;
            }

            if($max && ($msgs>=$max || $errors>($max*0.8)))
                break;
        }
		$this->mbox->expunge();

        // Warn on excessive errors
        if ($errors > $msgs) {
            $warn = sprintf(_S('Excessive errors processing emails for %1$s (%2$s). Please manually check the inbox.'),
                    $this->mbox->getHostInfo(), $this->getEmail());
            $this->log($warn);
        }

        return $msgs;
    }

    function log($error) {
        global $ost;
        $ost->logWarning(_S('Mail Fetcher'), $error);
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
            //Break if we're 80% into max execution time
            if ((\Misc::micro_time()-$start_time) > ($max_time*0.80))
                break;
            try {
                $mailbox->fetchEmails();
            } catch (\Throwable $t) {
                if ($mailbox->getNumErrors() >= $MAXERRORS && $ost) {
                    //We've reached the MAX consecutive errors...will attempt logins at delayed intervals
                    // XXX: Translate me
                    $msg="\nosTicket is having trouble fetching emails from the following mail account: \n".
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
