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

class Lock extends VerySimpleModel {

    static $meta = array(
        'table' => LOCK_TABLE,
        'pk' => array('lock_id'),
        'joins' => array(
            'ticket' => array(
                'reverse' => 'TicketModel.lock',
                'list' => false,
            ),
            'task' => array(
                'reverse' => 'Task.lock',
                'list' => false,
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
            ),
        ),
    );

    const MODE_DISABLED = 0;
    const MODE_ON_VIEW = 1;
    const MODE_ON_ACTIVITY = 2;

    function getId() {
        return $this->lock_id;
    }

    function getStaffId() {
        return $this->staff_id;
    }

    function getStaffName() {
        return $this->staff->getName();
    }

    function getStaff() {
        return $this->staff;
    }

    function getCreateTime() {
        return $this->created;
    }

    function getExpireTime() {
        return strtotime($this->expire);
    }
    //Get remaiming time before the lock expires
    function getTime() {
        return max(0, $this->getExpireTime() - Misc::dbtime());
    }

    //Should we be doing realtime check here? (Ans: not really....expiretime is local & based on loadtime)
    function isExpired() {
        return (Misc::dbtime() > $this->getExpireTime());
    }

    function getCode() {
        return $this->code;
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
        return $this->save(true);
    }

    //release aka delete a lock.
    function release() {
        return $this->delete();
    }

    /* ----------------------- Static functions ---------------------------*/
    static function lookup($id, $object=false) {
        if ($object instanceof Ticket)
            return parent::lookup(array('lock_id' => $id, 'ticket__ticket_id' => $object->getId()));
        elseif ($object instanceof Task)
            return parent::lookup(array('lock_id' => $id, 'task__id' => $object->getId()));
        else
            return parent::lookup($id);
    }

    //Create a ticket lock...this function assumes the caller checked for access & validity of ticket & staff x-ship.
    static function acquire($staffId, $lockTime) {

        if (!$staffId or !$lockTime)
            return null;

        // Create the new lock.
        $lock = parent::create(array(
            'created' => SqlFunction::NOW(),
            'staff_id' => $staffId,
            'expire' => SqlExpression::plus(
                SqlFunction::NOW(),
                SqlInterval::MINUTE($lockTime)
            ),
            'code' => Misc::randCode(10)
        ));
        if ($lock->save(true))
            return $lock;
    }

    static function create($staffId, $lockTime) {
        if ($lock = self::acquire($staffId, $lockTime))
            return $lock;
    }

    // Simply remove ALL locks a user (staff) holds on a ticket(s).
    static function removeStaffLocks($staffId, $object=false) {
        $locks = static::objects()->filter(array(
            'staff_id' => $staffId,
        ));
        if ($object instanceof Ticket)
            $locks->filter(array('ticket__ticket_id' => $object->getId()));
        elseif ($object instanceof Task)
            $locks->filter(array('task__id' => $object->getId()));

        return $locks->delete();
    }

    static function cleanup() {
        return static::objects()->filter(array(
            'expire__lt' => SqlFunction::NOW()
        ))->delete();
    }
}
?>
