<?php
/*********************************************************************
    class.ai.assistant.php

    AI Assistant for osTicket - helps staff analyze tickets using AI
    Uses Microsoft 365 Copilot API with Azure AD authentication

    Copyright (c)  2024 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require_once INCLUDE_DIR . 'class.ticket.php';
require_once INCLUDE_DIR . 'class.thread.php';

class AIAssistant {

    // Microsoft 365 Copilot API endpoint
    const API_ENDPOINT = 'https://api.business.microsoft.com/v1.0/chat/completions';
    const TOKEN_ENDPOINT = 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token';
    const TOKEN_SCOPE = 'https://api.business.microsoft.com/.default';

    const DEFAULT_MODEL = 'gpt-4';
    const DEFAULT_TEMPERATURE = 0.7;
    const DEFAULT_MAX_TOKENS = 1000;

    private $config;
    private $staff;
    private $errors = array();
    private $accessToken = null;

    function __construct($staff=null) {
        global $thisstaff;

        $this->staff = $staff ?: $thisstaff;
        $this->config = new Config('ai.assistant', array(
            'enabled' => false,
            'tenant_id' => '',
            'client_id' => '',
            'client_secret' => '',
            'model' => self::DEFAULT_MODEL,
            'temperature' => self::DEFAULT_TEMPERATURE,
            'max_tokens' => self::DEFAULT_MAX_TOKENS,
            'rate_limit' => 10, // requests per hour per staff
            'token_cache' => '', // Cached access token
            'token_expires' => 0, // Token expiration timestamp
        ));
    }

    /**
     * Check if AI assistant is enabled
     */
    function isEnabled() {
        return $this->config->get('enabled')
            && $this->config->get('tenant_id')
            && $this->config->get('client_id')
            && $this->config->get('client_secret');
    }

    /**
     * Check if staff member can use AI assistant
     */
    function canUseAssistant($staff=null) {
        if (!$staff)
            $staff = $this->staff;

        if (!($staff instanceof Staff))
            return false;

        // Check if feature is enabled
        if (!$this->isEnabled())
            return false;

        // Check rate limit
        if (!$this->checkRateLimit($staff))
            return false;

        return true;
    }

    /**
     * Check rate limit for staff member
     */
    function checkRateLimit($staff) {
        $limit = $this->config->get('rate_limit', 10);

        // Get count of requests in last hour
        $sql = 'SELECT COUNT(*) FROM '.AI_LOG_TABLE.' '
             . 'WHERE staff_id='.db_input($staff->getId()).' '
             . 'AND created >= DATE_SUB(NOW(), INTERVAL 1 HOUR)';

        $count = db_result(db_query($sql));

        return $count < $limit;
    }

    /**
     * Get Azure AD OAuth access token
     */
    private function getAccessToken() {
        // Check if we have a cached valid token
        $cachedToken = $this->config->get('token_cache');
        $tokenExpires = $this->config->get('token_expires', 0);

        if ($cachedToken && time() < $tokenExpires - 300) { // 5 min buffer
            return $cachedToken;
        }

        // Get new token from Azure AD
        $tenantId = $this->config->get('tenant_id');
        $clientId = $this->config->get('client_id');
        $clientSecret = $this->config->get('client_secret');

        if (!$tenantId || !$clientId || !$clientSecret) {
            $this->errors[] = 'Azure AD credentials not configured';
            return null;
        }

        $tokenUrl = str_replace('{tenant_id}', $tenantId, self::TOKEN_ENDPOINT);

        $postData = array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => self::TOKEN_SCOPE,
            'grant_type' => 'client_credentials'
        );

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            $this->errors[] = 'OAuth token request failed: ' . $curlError;
            return null;
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = isset($errorData['error_description'])
                ? $errorData['error_description']
                : 'OAuth token request returned HTTP ' . $httpCode;
            $this->errors[] = $errorMsg;
            return null;
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            $this->errors[] = 'Invalid OAuth token response';
            return null;
        }

        // Cache the token
        $accessToken = $data['access_token'];
        $expiresIn = isset($data['expires_in']) ? $data['expires_in'] : 3600;
        $expiresAt = time() + $expiresIn;

        $this->config->set('token_cache', $accessToken);
        $this->config->set('token_expires', $expiresAt);

        return $accessToken;
    }

    /**
     * Get formatted ticket thread for AI context
     */
    function getTicketContext($ticket) {
        if (!($ticket instanceof Ticket))
            $ticket = Ticket::lookup($ticket);

        if (!$ticket)
            return null;

        // Check staff permission
        if (!$ticket->checkStaffPerm($this->staff)) {
            $this->errors[] = 'Access denied to ticket';
            return null;
        }

        $thread = $ticket->getThread();
        $entries = $thread->getEntries();

        $context = array();
        $context[] = "Ticket #" . $ticket->getNumber();
        $context[] = "Subject: " . $ticket->getSubject();
        $context[] = "Status: " . $ticket->getStatus();
        $context[] = "Department: " . $ticket->getDept()->getName();
        $context[] = "Created: " . Format::datetime($ticket->getCreateDate());
        $context[] = "\nTicket Thread History:\n";

        foreach ($entries as $entry) {
            $author = 'Unknown';
            $entry_type = 'Message';

            // Get author info
            if ($entry->staff_id) {
                $staff = Staff::lookup($entry->staff_id);
                $author = $staff ? $staff->getName() : 'Staff';
                $entry_type = ($entry->type == 'N') ? 'Internal Note' : 'Response';
            } elseif ($entry->user_id) {
                $user = User::lookup($entry->user_id);
                $author = $user ? $user->getName() : 'User';
                $entry_type = 'Customer Message';
            }

            $context[] = "\n[" . Format::datetime($entry->created) . "] $entry_type by $author:";
            $context[] = strip_tags($entry->getBody());

            // Add attachment info if exists
            if ($entry->has_attachments) {
                $context[] = "(Contains " . $entry->has_attachments . " attachment(s))";
            }
        }

        return implode("\n", $context);
    }

    /**
     * Send question to AI API
     */
    function askQuestion($ticket_id, $question, $options=array()) {
        if (!$this->canUseAssistant()) {
            $this->errors[] = 'AI Assistant is not available';
            return null;
        }

        // Sanitize question
        $question = trim(strip_tags($question));
        if (empty($question)) {
            $this->errors[] = 'Question cannot be empty';
            return null;
        }

        // Get ticket context
        $context = $this->getTicketContext($ticket_id);
        if (!$context) {
            if (!$this->errors)
                $this->errors[] = 'Unable to retrieve ticket information';
            return null;
        }

        // Get OAuth access token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            if (!$this->errors)
                $this->errors[] = 'Failed to authenticate with Microsoft Copilot';
            return null;
        }

        // Prepare API request
        $model = $this->config->get('model', self::DEFAULT_MODEL);
        $temperature = $this->config->get('temperature', self::DEFAULT_TEMPERATURE);
        $max_tokens = $this->config->get('max_tokens', self::DEFAULT_MAX_TOKENS);

        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are an AI assistant helping support staff analyze and respond to customer support tickets. Provide helpful, professional insights based on the ticket history. Be concise and actionable.'
            ),
            array(
                'role' => 'user',
                'content' => $context . "\n\nStaff Question: " . $question
            )
        );

        $payload = array(
            'messages' => $messages,
            'model' => $model,
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens)
        );

        // Make API call
        $response = $this->callAPI($accessToken, $payload);

        // Log the interaction
        if ($response) {
            $this->logInteraction($ticket_id, $question, $response);
        }

        return $response;
    }

    /**
     * Call Microsoft 365 Copilot API
     */
    private function callAPI($accessToken, $payload) {
        $ch = curl_init(self::API_ENDPOINT);

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: osTicket-AI-Assistant/1.0'
        );

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $this->errors[] = 'API Connection Error: ' . $curl_error;
            return null;
        }

        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            $error_msg = isset($error_data['error']['message'])
                ? $error_data['error']['message']
                : 'API returned HTTP ' . $http_code;
            $this->errors[] = $error_msg;

            // If token expired, clear cache and suggest retry
            if ($http_code === 401) {
                $this->config->set('token_cache', '');
                $this->config->set('token_expires', 0);
                $this->errors[] = 'Authentication token expired. Please try again.';
            }

            return null;
        }

        $data = json_decode($response, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            $this->errors[] = 'Invalid API response format';
            return null;
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Log AI interaction
     */
    private function logInteraction($ticket_id, $question, $response) {
        $sql = 'INSERT INTO '.AI_LOG_TABLE.' SET '
             . 'ticket_id='.db_input($ticket_id).', '
             . 'staff_id='.db_input($this->staff->getId()).', '
             . 'question='.db_input($question).', '
             . 'response='.db_input($response).', '
             . 'created=NOW()';

        return db_query($sql);
    }

    /**
     * Get AI interaction history for a ticket
     */
    function getTicketHistory($ticket_id, $limit=10) {
        $sql = 'SELECT ai.*, s.firstname, s.lastname '
             . 'FROM '.AI_LOG_TABLE.' ai '
             . 'LEFT JOIN '.STAFF_TABLE.' s ON s.staff_id = ai.staff_id '
             . 'WHERE ai.ticket_id='.db_input($ticket_id).' '
             . 'ORDER BY ai.created DESC '
             . 'LIMIT '.db_input($limit);

        $history = array();
        if ($res = db_query($sql)) {
            while ($row = db_fetch_array($res)) {
                $history[] = $row;
            }
        }

        return $history;
    }

    /**
     * Test Azure AD connection
     */
    function testConnection() {
        $token = $this->getAccessToken();
        if (!$token) {
            return array(
                'success' => false,
                'error' => $this->getLastError()
            );
        }

        return array(
            'success' => true,
            'message' => 'Successfully authenticated with Azure AD'
        );
    }

    /**
     * Clear token cache (useful for troubleshooting)
     */
    function clearTokenCache() {
        $this->config->set('token_cache', '');
        $this->config->set('token_expires', 0);
    }

    /**
     * Get last error
     */
    function getErrors() {
        return $this->errors;
    }

    function getLastError() {
        return end($this->errors);
    }
}

/**
 * AI Log Model for tracking interactions
 */
class AILog extends VerySimpleModel {
    static $meta = array(
        'table' => AI_LOG_TABLE,
        'pk' => array('id'),
        'joins' => array(
            'ticket' => array(
                'constraint' => array('ticket_id' => 'Ticket.ticket_id'),
            ),
            'staff' => array(
                'constraint' => array('staff_id' => 'Staff.staff_id'),
            ),
        ),
    );
}
