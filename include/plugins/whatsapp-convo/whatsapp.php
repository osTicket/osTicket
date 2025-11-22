<?php

require_once(INCLUDE_DIR . 'class.signal.php');
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once('config.php');

class WhatsAppConvoPlugin extends Plugin {
    var $config_class = 'WhatsAppConvoPluginConfig';

    /**
     * Bootstrap del plugin - se ejecuta cuando el plugin está activo
     */
    function bootstrap() {
        // Obtener la configuración
        $config = $this->getConfig();

        // Conectar señales solo si están habilitadas
        if ($config->get('notify_on_ticket_create')) {
            Signal::connect('ticket.created', array($this, 'onTicketCreated'));
        }

        if ($config->get('notify_on_message')) {
            Signal::connect('threadentry.created', array($this, 'onThreadEntryCreated'));
        }
    }

    /**
     * Manejador de evento: ticket creado
     */
    function onTicketCreated($ticket, $data = null) {
        $config = $this->getConfig();

        if (!$config->get('notify_on_ticket_create')) {
            return;
        }

        try {
            $this->log('Ticket creado: #' . $ticket->getNumber());

            // Obtener información del ticket
            $ticketInfo = array(
                'ticket_number' => $ticket->getNumber(),
                'subject' => $ticket->getSubject(),
                'name' => $ticket->getName(),
                'email' => $ticket->getEmail(),
                'message' => $this->getTicketMessage($ticket),
            );

            // Generar mensaje usando la plantilla
            $message = $this->renderTemplate(
                $config->get('message_template_new_ticket'),
                $ticketInfo
            );

            // Enviar mensaje de WhatsApp
            $this->sendWhatsAppMessage($message);

        } catch (Exception $e) {
            $this->logError('Error al procesar ticket creado: ' . $e->getMessage());
        }
    }

    /**
     * Manejador de evento: entrada de hilo creada (mensaje/respuesta)
     */
    function onThreadEntryCreated($entry, $data = null) {
        $config = $this->getConfig();

        if (!$config->get('notify_on_message')) {
            return;
        }

        try {
            // Obtener el ticket asociado
            $thread = $entry->getThread();
            if (!$thread) {
                return;
            }

            $ticket = $thread->getObject();
            if (!$ticket || !($ticket instanceof Ticket)) {
                return;
            }

            $this->log('Nueva entrada en ticket: #' . $ticket->getNumber());

            // Obtener información de la entrada
            $poster = $entry->getPoster();
            $name = is_object($poster) ? $poster->getName() : $entry->getName();

            $entryInfo = array(
                'ticket_number' => $ticket->getNumber(),
                'subject' => $ticket->getSubject(),
                'name' => $name,
                'message' => $this->cleanMessage($entry->getBody()),
            );

            // Generar mensaje usando la plantilla
            $message = $this->renderTemplate(
                $config->get('message_template_new_message'),
                $entryInfo
            );

            // Enviar mensaje de WhatsApp
            $this->sendWhatsAppMessage($message);

        } catch (Exception $e) {
            $this->logError('Error al procesar entrada de hilo: ' . $e->getMessage());
        }
    }

    /**
     * Obtener el primer mensaje del ticket
     */
    private function getTicketMessage($ticket) {
        try {
            $thread = $ticket->getThread();
            if ($thread) {
                $entries = $thread->getEntries();
                if ($entries && count($entries) > 0) {
                    $firstEntry = $entries[0];
                    return $this->cleanMessage($firstEntry->getBody());
                }
            }
        } catch (Exception $e) {
            $this->logError('Error al obtener mensaje del ticket: ' . $e->getMessage());
        }
        return '';
    }

    /**
     * Limpiar el mensaje de HTML y formatear
     */
    private function cleanMessage($message) {
        // Eliminar tags HTML
        $message = strip_tags($message);
        // Decodificar entidades HTML
        $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
        // Limitar longitud
        if (strlen($message) > 500) {
            $message = substr($message, 0, 497) . '...';
        }
        return trim($message);
    }

    /**
     * Renderizar plantilla con variables
     */
    private function renderTemplate($template, $variables) {
        $message = $template;
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value, $message);
        }
        return $message;
    }

    /**
     * Enviar mensaje de WhatsApp usando la API de ConvoChat
     */
    private function sendWhatsAppMessage($message) {
        $config = $this->getConfig();

        $apiSecret = $config->get('api_secret');
        $whatsappAccount = $config->get('whatsapp_account');
        $recipientPhone = $config->get('recipient_phone');
        $apiBaseUrl = rtrim($config->get('api_base_url'), '/');

        if (empty($apiSecret) || empty($whatsappAccount) || empty($recipientPhone)) {
            $this->logError('Configuración incompleta: API Secret, WhatsApp Account o Recipient Phone faltantes');
            return false;
        }

        $url = $apiBaseUrl . '/send/whatsapp';

        // Preparar datos para enviar
        $postData = array(
            'secret' => $apiSecret,
            'account' => $whatsappAccount,
            'recipient' => $recipientPhone,
            'type' => 'text',
            'message' => $message,
            'priority' => 1, // Alta prioridad
        );

        $this->log('Enviando WhatsApp a: ' . $recipientPhone);

        // Enviar petición HTTP
        $response = $this->sendHttpRequest($url, $postData);

        if ($response) {
            $responseData = json_decode($response, true);

            if (isset($responseData['status']) && $responseData['status'] == 200) {
                $this->log('WhatsApp enviado exitosamente. Message ID: ' .
                    (isset($responseData['data']['messageId']) ? $responseData['data']['messageId'] : 'N/A'));
                return true;
            } else {
                $errorMsg = isset($responseData['message']) ? $responseData['message'] : 'Error desconocido';
                $this->logError('Error al enviar WhatsApp: ' . $errorMsg);
                return false;
            }
        }

        return false;
    }

    /**
     * Enviar petición HTTP usando cURL
     */
    private function sendHttpRequest($url, $postData) {
        if (!function_exists('curl_init')) {
            $this->logError('cURL no está disponible en el servidor');
            return false;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            $this->logError('cURL Error: ' . $curlError);
            return false;
        }

        if ($httpCode != 200) {
            $this->logError('HTTP Error ' . $httpCode . ': ' . $response);
        }

        return $response;
    }

    /**
     * Log de mensajes (si debug está habilitado)
     */
    private function log($message) {
        $config = $this->getConfig();
        if ($config->get('debug_mode')) {
            error_log('[WhatsAppConvo] ' . $message);
        }
    }

    /**
     * Log de errores (siempre se registra)
     */
    private function logError($message) {
        error_log('[WhatsAppConvo ERROR] ' . $message);
    }
}
