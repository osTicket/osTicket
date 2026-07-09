<?php
/*********************************************************************
    SlaPriorityEscalationResolver.php

    Delegates SLA priority-escalation flag evaluation to the existing
    SLA::priorityEscalation() expression. Flag loading and facade concerns
    stay with the caller.

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class SlaPriorityEscalationResolver {

    /**
     * Evaluate whether priority escalation is enabled for the bound SLA.
     *
     * Lifts the legacy `$this->flags && self::FLAG_ESCALATE` expression from
     * SLA::priorityEscalation(). Does not call priorityEscalation() — the
     * strangler stage routes the facade method here.
     *
     * @param SLA $sla Hydrated SLA instance with flags already loaded
     * @return int|bool Raw expression result (truthy/falsy contract)
     */
    public function resolve($sla) {
        return $sla->flags && SLA::FLAG_ESCALATE;
    }
}

?>
