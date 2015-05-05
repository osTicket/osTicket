<?php
/*********************************************************************
    class.priority.php

    Priority handle

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Priority extends VerySimpleModel
implements TemplateVariable {

    static $meta = array(
        'table' => PRIORITY_TABLE,
        'pk' => array('priority_id'),
        'ordering' => array('-priority_urgency')
    );

    function getId() {
        return $this->priority_id;
    }

    function getTag() {
        return $this->priority;
    }

    function getDesc() {
        return $this->priority_desc;
    }

    function getColor() {
        return $this->priority_color;
    }

    function getUrgency() {
        return $this->priority_urgency;
    }

    function isPublic() {
        return $this->ispublic;
    }

    // TemplateVariable interface
    function asVar() { return $this->getDesc(); }
    static function getVarScope() {
        return array(
            'desc' => __('Priority Level'),
        );
    }

    function __toString() {
        return $this->getDesc();
    }

    /* ------------- Static ---------------*/
    static function getPriorities( $publicOnly=false) {
        $priorities=array();

        $objects = static::objects()->values_flat('priority_id', 'priority_desc');
        if ($publicOnly)
            $objects->filter(array('ispublic'=>1));

        foreach ($objects as $row) {
            $priorities[$row[0]] = $row[1];
        }

        return $priorities;
    }

    function getPublicPriorities() {
        return self::getPriorities(true);
    }
}
?>
