<?php
/*********************************************************************
    class.banlist.php

    Banned email addresses handle.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once "class.filter.php";
class Banlist {

    function add($email,$submitter='') {
        return self::getSystemBanList()->addRule('email','equal',$email);
    }

    function remove($email) {
        return self::getSystemBanList()->removeRule('email','equal',$email);
    }

    /**
     * Quick function to determine if the received email-address is in the
     * banlist. Returns the filter of the filter that has the address
     * blacklisted and FALSE if the email is not blacklisted.
     *
     */
    static function isBanned($addr) {

        if (!($filter=self::getFilter()))
            return false;

        $sql='SELECT filter.id '
            .' FROM '.FILTER_TABLE.' filter'
            .' INNER JOIN '.FILTER_RULE_TABLE.' rule'
            .'  ON (filter.id=rule.filter_id)'
            .' WHERE filter.id='.db_input($filter->getId())
            .'   AND filter.isactive'
            .'   AND rule.isactive '
            .'   AND rule.what="email"'
            .'   AND rule.val='.db_input($addr);

        if (($res=db_query($sql)) && db_num_rows($res))
            return $filter;

        return false;
    }

    function includes($email) {
        return self::getSystemBanList()->containsRule('email','equal',$email);
    }

    function ensureSystemBanList() {

        if (!($id=Filter::getIdByName('SYSTEM BAN LIST')))
            $id=self::createSystemBanList();

        return $id;
    }

    function createSystemBanList() {
        # XXX: Filter::create should return the ID!!!
        $errors=array();
        return Filter::create(array(
            'execorder'     => 99,
            'name'          => 'SYSTEM BAN LIST',
            'isactive'      => 1,
            'match_all_rules' => false,
            'actions'       => array(
                'Nreject',
            ),
            'rules'         => array(),
            'notes'         => __('Internal list for email banning. Do not remove')
        ), $errors);
    }

    function getSystemBanList() {
        return new Filter(self::ensureSystemBanList());
    }

    static function getFilter() {
        return self::getSystemBanList();
    }
}
