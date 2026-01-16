<?php
/*********************************************************************
    ajax.ai.php

    AJAX interface for AI Assistant

    Copyright (c)  2024 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

if(!defined('INCLUDE_DIR')) die('403');

require_once(INCLUDE_DIR.'class.ajax.php');
require_once(INCLUDE_DIR.'class.ai.assistant.php');
require_once(INCLUDE_DIR.'class.ticket.php');

class AIAjaxAPI extends AjaxController {

    /**
     * Ask AI assistant a question about a ticket
     * POST /tickets/{id}/ai/ask
     */
    function ask($ticket_id) {
        global $thisstaff;

        if (!$thisstaff) {
            return $this->exerr(401, __('Authentication required'));
        }

        // Lookup ticket
        if (!($ticket = Ticket::lookup($ticket_id))) {
            return $this->exerr(404, __('Ticket not found'));
        }

        // Check staff permission
        if (!$ticket->checkStaffPerm($thisstaff)) {
            return $this->exerr(403, __('Access denied'));
        }

        // Get question from POST data
        $question = isset($_POST['question']) ? trim($_POST['question']) : '';

        if (empty($question)) {
            return $this->json_encode(array(
                'success' => false,
                'error' => __('Question is required')
            ));
        }

        // Initialize AI assistant
        $ai = new AIAssistant($thisstaff);

        // Check if enabled
        if (!$ai->isEnabled()) {
            return $this->json_encode(array(
                'success' => false,
                'error' => __('AI Assistant is not enabled. Please configure it in admin settings.')
            ));
        }

        // Check if staff can use assistant
        if (!$ai->canUseAssistant($thisstaff)) {
            return $this->json_encode(array(
                'success' => false,
                'error' => __('You have reached the rate limit. Please try again later.')
            ));
        }

        // Ask question
        $response = $ai->askQuestion($ticket_id, $question);

        if ($response === null) {
            return $this->json_encode(array(
                'success' => false,
                'error' => $ai->getLastError() ?: __('Failed to get AI response')
            ));
        }

        return $this->json_encode(array(
            'success' => true,
            'response' => $response,
            'timestamp' => Misc::gmtime()
        ));
    }

    /**
     * Get AI interaction history for a ticket
     * GET /tickets/{id}/ai/history
     */
    function getHistory($ticket_id) {
        global $thisstaff;

        if (!$thisstaff) {
            return $this->exerr(401, __('Authentication required'));
        }

        // Lookup ticket
        if (!($ticket = Ticket::lookup($ticket_id))) {
            return $this->exerr(404, __('Ticket not found'));
        }

        // Check staff permission
        if (!$ticket->checkStaffPerm($thisstaff)) {
            return $this->exerr(403, __('Access denied'));
        }

        // Get history
        $ai = new AIAssistant($thisstaff);
        $history = $ai->getTicketHistory($ticket_id);

        return $this->json_encode(array(
            'success' => true,
            'history' => $history
        ));
    }

    /**
     * Check if AI assistant is available
     * GET /ai/status
     */
    function getStatus() {
        global $thisstaff;

        if (!$thisstaff) {
            return $this->exerr(401, __('Authentication required'));
        }

        $ai = new AIAssistant($thisstaff);

        return $this->json_encode(array(
            'success' => true,
            'enabled' => $ai->isEnabled(),
            'canUse' => $ai->canUseAssistant($thisstaff)
        ));
    }
}
