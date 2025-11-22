# Plugin WhatsApp ConvoChat para osTicket

Plugin profesional para osTicket que envía notificaciones de WhatsApp a través de la API de ConvoChat cuando se crean tickets o se reciben mensajes.

## 🌟 Características

- ✅ Notificaciones automáticas de WhatsApp al crear tickets
- ✅ Notificaciones automáticas al recibir mensajes/respuestas en tickets
- ✅ Plantillas de mensajes personalizables
- ✅ Soporte para variables dinámicas en plantillas
- ✅ Modo debug para facilitar troubleshooting
- ✅ Validación de configuración
- ✅ Logs de errores automáticos

## 📋 Requisitos

- osTicket v1.10 o superior
- PHP 7.0 o superior
- Extensión cURL habilitada en PHP
- Cuenta activa en ConvoChat
- Cuenta de WhatsApp vinculada en ConvoChat
- API Key de ConvoChat

## 🔧 Instalación

1. **Copiar archivos del plugin**

   Los archivos del plugin ya deben estar en:
   ```
   include/plugins/whatsapp-convo/
   ```

2. **Acceder al panel de administración de osTicket**

   Ir a: `Manage` → `Plugins`

3. **Instalar el plugin**

   - Buscar "WhatsApp ConvoChat Notifications" en la lista de plugins disponibles
   - Hacer clic en "Install" o "Add New Instance"

4. **Configurar el plugin**

   Completar los siguientes campos obligatorios:

## ⚙️ Configuración

### Campos Obligatorios

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **API Secret** | Clave API de ConvoChat (desde Tools → API Keys) | `abc123def456...` |
| **WhatsApp Account ID** | ID único de su cuenta de WhatsApp en ConvoChat | `+522221234567` |
| **Número de teléfono destinatario** | Número en formato E.164 que recibirá notificaciones | `+522221234567` |

### Campos Opcionales

| Campo | Descripción | Valor por defecto |
|-------|-------------|-------------------|
| **Notificar al crear ticket** | Activar/desactivar notificación de nuevos tickets | ✅ Activado |
| **Notificar al recibir mensaje** | Activar/desactivar notificación de nuevos mensajes | ✅ Activado |
| **API Base URL** | URL base de la API de ConvoChat | `https://sms.convo.chat/api` |
| **Modo debug** | Guardar logs detallados en el sistema | ❌ Desactivado |

### Plantillas de Mensajes

#### Plantilla para Nuevo Ticket

Variables disponibles:
- `{{ticket_number}}` - Número del ticket
- `{{subject}}` - Asunto del ticket
- `{{name}}` - Nombre del usuario
- `{{email}}` - Email del usuario
- `{{message}}` - Contenido del primer mensaje

**Plantilla por defecto:**
```
🎫 *Nuevo Ticket Creado*

Ticket #{{ticket_number}}
Asunto: {{subject}}
De: {{name}} ({{email}})

Mensaje:
{{message}}
```

#### Plantilla para Nueva Respuesta

Variables disponibles:
- `{{ticket_number}}` - Número del ticket
- `{{subject}}` - Asunto del ticket
- `{{name}}` - Nombre de quien respondió
- `{{message}}` - Contenido de la respuesta

**Plantilla por defecto:**
```
💬 *Nueva Respuesta en Ticket*

Ticket #{{ticket_number}}
Asunto: {{subject}}
De: {{name}}

Respuesta:
{{message}}
```

## 🚀 Cómo Obtener las Credenciales de ConvoChat

### 1. Obtener API Secret

1. Ingresar a su cuenta de ConvoChat
2. Ir a `Tools` → `API Keys`
3. Crear una nueva API Key o copiar una existente
4. Copiar el `secret` generado

### 2. Vincular Cuenta de WhatsApp

Si aún no tiene una cuenta de WhatsApp vinculada:

```bash
# Endpoint para crear un nuevo QR
GET https://sms.convo.chat/api/create/wa.link?secret=YOUR_API_SECRET

# Respuesta incluirá:
# - qrstring: String del código QR
# - qrimageLink: URL de la imagen del QR para escanear
# - infoLink: URL para verificar el estado de la vinculación
```

Pasos:
1. Llamar al endpoint `create/wa.link` con su API Secret
2. Escanear el código QR con WhatsApp
3. Obtener el `WhatsApp Account ID` (número de teléfono vinculado)

### 3. Obtener WhatsApp Account ID

Si ya tiene una cuenta vinculada:

```bash
# Listar cuentas de WhatsApp
GET https://sms.convo.chat/api/get/wa.accounts?secret=YOUR_API_SECRET
```

El `WhatsApp Account ID` es el número de teléfono de la cuenta (ej: `+522221234567`)

## 🧪 Pruebas

1. **Activar modo debug**
   - En la configuración del plugin, activar "Modo debug"
   - Los logs se guardarán en el log de errores de PHP

2. **Crear un ticket de prueba**
   - Crear un nuevo ticket desde el panel de cliente
   - Verificar que llegue la notificación de WhatsApp
   - Revisar los logs en caso de error

3. **Enviar una respuesta**
   - Responder a un ticket existente
   - Verificar que llegue la notificación de nueva respuesta

## 🐛 Troubleshooting

### No llegan notificaciones

1. **Verificar que el plugin esté activo**
   - `Manage` → `Plugins` → verificar que esté "Active"

2. **Verificar configuración**
   - API Secret correcto
   - WhatsApp Account ID válido
   - Número de teléfono en formato E.164 (con +)

3. **Revisar logs**
   - Activar "Modo debug"
   - Revisar el log de errores de PHP
   - Buscar líneas que contengan `[WhatsAppConvo]`

4. **Verificar conectividad**
   - El servidor debe poder hacer peticiones HTTPS a `sms.convo.chat`
   - cURL debe estar habilitado

### Error: "cURL no está disponible"

Instalar/habilitar la extensión cURL de PHP:

```bash
# Ubuntu/Debian
sudo apt-get install php-curl
sudo service apache2 restart

# CentOS/RHEL
sudo yum install php-curl
sudo service httpd restart
```

### Error: "Configuración incompleta"

Verificar que todos los campos obligatorios estén completos:
- API Secret
- WhatsApp Account ID
- Número de teléfono destinatario

## 📝 Formato de Número de Teléfono

El número de teléfono debe estar en formato **E.164**:

✅ **Correcto:**
- `+522221234567` (México)
- `+13051234567` (USA)
- `+34912345678` (España)

❌ **Incorrecto:**
- `522221234567` (falta el +)
- `2221234567` (falta código de país)
- `+52 222 123 4567` (tiene espacios)
- `+52-222-123-4567` (tiene guiones)

## 🔐 Seguridad

- El API Secret se almacena en la base de datos de osTicket
- Todas las peticiones se hacen sobre HTTPS
- Se valida el certificado SSL de ConvoChat
- No se almacenan logs de mensajes por defecto (solo en modo debug)

## 📚 API de ConvoChat Utilizada

El plugin utiliza el endpoint:
```
POST https://sms.convo.chat/api/send/whatsapp
```

Parámetros enviados:
- `secret`: API Secret
- `account`: WhatsApp Account ID
- `recipient`: Número de teléfono destinatario
- `type`: "text"
- `message`: Contenido del mensaje
- `priority`: 1 (alta prioridad)

## 📄 Licencia

Este plugin se distribuye bajo la misma licencia que osTicket (GPL).

## 👨‍💻 Soporte

Para reportar problemas o sugerencias:
- Revisar los logs con modo debug activado
- Verificar la configuración de ConvoChat
- Contactar al administrador del sistema

## 🔄 Versión

**v1.0.0** - Versión inicial
- Soporte para notificaciones de tickets creados
- Soporte para notificaciones de mensajes/respuestas
- Plantillas personalizables
- Modo debug
