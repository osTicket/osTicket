<?php
/*********************************************************************
    class.lock.php

    Ticket lock handle.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

/*
 * Mainly used as a helper...
 */

class TicketLock extends VerySimpleModel {

    static $meta = array(
        'table' => TICKET_LOCK_TABLE,
        'pk' => array('lock_id'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array('ticket_id' => 'TicketModel.ticket_id'),
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
            ),
        ),
    );

    var $expiretime;

    function __onload() {
        if (isset($this->expire))
            $this->expiretime = strtotime($this->expire);
    }

    function getId() {
        return $this->lock_id;
    }

    function getStaffId() {
        return $this->staff_id;
    }

    function getStaffName() {
        return $this->staff->getName();
    }

    function getCreateTime() {
        return $this->created;
    }

    function getExpireTime() {
        return $this->expire;
    }
    //Get remaiming time before the lock expires
    function getTime() {
        return $this->isExpired()?0:($this->expiretime-time());
    }

    //Should we be doing realtime check here? (Ans: not really....expiretime is local & based on loadtime)
    function isExpired() {
        return (time()>$this->expiretime);
    }

    //Renew existing lock.
    function renew($lockTime=0) {
        global $cfg;

        if(!$lockTime || !is_numeric($lockTime)) //XXX: test to  make it works.
            $lockTime = $cfg->getLockTime();

        $this->expire = SqlExpression::plus(
            SqlFunction::NOW(),
            SqlInterval::MINUTE($lockTime)
        );
        return $this->save();
    }

    //release aka delete a lock.
    function release() {
        return $this->delete();
    }

    /* ----------------------- Static functions ---------------------------*/
    static function lookup($id, $tid=false) {
        if ($tid)
            return parent::lookup(array('lock_id' => $id, 'ticket_id' => $tid));
        else
            return parent::lookup($id);
    }

    //Create a ticket lock...this function assumes the caller checked for access & validity of ticket & staff x-ship.
    static function acquire($ticketId, $staffId, $lockTime) {

        if (!$ticketId or !$staffId or !$lockTime)
            return 0;

        // Cleanup any expired locks on the ticket.
        static::objects()->filter(array(
            'ticket_id' => $ticketId,
            'expire__lt' => SqlFunction::NOW()
        ))->delete();

        // Create the new lock.
        $lock = parent::create(array(
            'created' => SqlFunction::NOW(),
            'ticket_id' => $ticketId,
            'staff_id' => $staffId,
            'expire' => SqlExpression::plus(
                SqlFunction::NOW(),
                SqlInterval::MINUTE($lockTime)
            ),
        ));
        if ($lock->save(true))
            return $lock;
    }

    static function create($ticketId, $staffId, $lockTime) {
        if ($lock = self::acquire($ticketId, $staffId, $lockTime))
            return $lock;
    }

    // Simply remove ALL locks a user (staff) holds on a ticket(s).
    static function removeStaffLocks($staffId, $ticketId=0) {
        $locks = static::objects()->filter(array(
            'staff_id' => $staffId,
        ));
        if ($ticketId)
            $locks->filter(array('ticket_id' => $ticketId));

        return $locks->delete();
    }

    // Called via cron
    static function cleanup() {
        static::objects()->filter(array(
            'expire__lt' => SqlFunction::NOW()
        ))->delete();
    }
}
?>
