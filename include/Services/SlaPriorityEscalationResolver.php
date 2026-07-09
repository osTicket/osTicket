<?php
/*********************************************************************
    SlaPriorityEscalationResolver.php

    Inline lift of the SLA::priorityEscalation() flag expression. Reads the
    bound SLA instance's in-memory flags property only; flag loading and
    facade concerns stay with the caller.

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class SlaPriorityEscalationResolver {

    /**
     * Evaluate priority escalation for a hydrated SLA instance.
     *
     * Preserves the legacy `$this->flags && self::FLAG_ESCALATE` expression
     * (logical AND with 0x0002, not bitwise AND or hasFlag()). Does not call
     * priorityEscalation() — the strangler stage routes the facade method here.
     *
     * @param SLA $sla Hydrated SLA with flags already loaded on the instance
     * @return int|bool Raw expression result (0/2); callers treat as falsy/truthy
     */
    public function resolve($sla) {
        return $sla->flags && SLA::FLAG_ESCALATE;
    }
}

?>
