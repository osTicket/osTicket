<?php

require_once INCLUDE_DIR . 'class.plugin.php';
require_once INCLUDE_DIR . 'class.forms.php';

class WhatsAppConvoPluginConfig extends PluginConfig {

    function getOptions() {
        return array(
            'api_secret' => new TextboxField(array(
                'label' => __('API Secret'),
                'required' => true,
                'configuration' => array(
                    'size' => 60,
                    'length' => 100,
                ),
                'hint' => __('Su clave API de ConvoChat (desde Tools → API Keys)'),
            )),
            'whatsapp_account' => new TextboxField(array(
                'label' => __('WhatsApp Account ID'),
                'required' => true,
                'configuration' => array(
                    'size' => 60,
                    'length' => 100,
                ),
                'hint' => __('ID único de su cuenta de WhatsApp vinculada en ConvoChat'),
            )),
            'notify_on_ticket_create' => new BooleanField(array(
                'label' => __('Notificar al crear ticket'),
                'default' => true,
                'configuration' => array(
                    'desc' => __('Enviar notificación de WhatsApp cuando se crea un nuevo ticket'),
                ),
            )),
            'notify_on_message' => new BooleanField(array(
                'label' => __('Notificar al recibir mensaje'),
                'default' => true,
                'configuration' => array(
                    'desc' => __('Enviar notificación de WhatsApp cuando se añade un mensaje al ticket'),
                ),
            )),
            'recipient_phone' => new TextboxField(array(
                'label' => __('Número de teléfono destinatario'),
                'required' => true,
                'configuration' => array(
                    'size' => 60,
                    'length' => 20,
                ),
                'hint' => __('Número de teléfono en formato E.164 (ej: +522221234567) para recibir notificaciones'),
            )),
            'api_base_url' => new TextboxField(array(
                'label' => __('API Base URL'),
                'default' => 'https://sms.convo.chat/api',
                'required' => true,
                'configuration' => array(
                    'size' => 60,
                    'length' => 100,
                ),
                'hint' => __('URL base de la API de ConvoChat'),
            )),
            'message_template_new_ticket' => new TextareaField(array(
                'label' => __('Plantilla mensaje nuevo ticket'),
                'default' => "🎫 *Nuevo Ticket Creado*\n\nTicket #{{ticket_number}}\nAsunto: {{subject}}\nDe: {{name}} ({{email}})\n\nMensaje:\n{{message}}",
                'required' => true,
                'configuration' => array(
                    'rows' => 6,
                    'cols' => 60,
                ),
                'hint' => __('Variables disponibles: {{ticket_number}}, {{subject}}, {{name}}, {{email}}, {{message}}'),
            )),
            'message_template_new_message' => new TextareaField(array(
                'label' => __('Plantilla mensaje nueva respuesta'),
                'default' => "💬 *Nueva Respuesta en Ticket*\n\nTicket #{{ticket_number}}\nAsunto: {{subject}}\nDe: {{name}}\n\nRespuesta:\n{{message}}",
                'required' => true,
                'configuration' => array(
                    'rows' => 6,
                    'cols' => 60,
                ),
                'hint' => __('Variables disponibles: {{ticket_number}}, {{subject}}, {{name}}, {{message}}'),
            )),
            'debug_mode' => new BooleanField(array(
                'label' => __('Modo debug'),
                'default' => false,
                'configuration' => array(
                    'desc' => __('Guardar logs de depuración en el log del sistema'),
                ),
            )),
        );
    }

    function pre_save(&$config, &$errors) {
        // Validar formato de número de teléfono
        if (isset($config['recipient_phone'])) {
            $phone = trim($config['recipient_phone']);
            // Verificar que el número empiece con +
            if (!preg_match('/^\+\d{10,15}$/', $phone)) {
                $errors['recipient_phone'] = __('El número de teléfono debe estar en formato E.164 (ej: +522221234567)');
                return false;
            }
        }

        // Validar API Secret
        if (isset($config['api_secret']) && empty(trim($config['api_secret']))) {
            $errors['api_secret'] = __('La API Secret es requerida');
            return false;
        }

        // Validar WhatsApp Account ID
        if (isset($config['whatsapp_account']) && empty(trim($config['whatsapp_account']))) {
            $errors['whatsapp_account'] = __('El WhatsApp Account ID es requerido');
            return false;
        }

        return true;
    }
}
