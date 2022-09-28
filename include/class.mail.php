<?php
/**
 * class.mail.php
 *
 * osTicket Laminas/Mail Wrapper and Mail/Auth Utils & Helpers
 *
 * @author Peter Rotich <peter@osticket.com>
 * @copyright Copyright (c) osTicket <gpl@osticket.com>
 *
 */

// osTicket/Mail namespace
namespace osTicket\Mail {
    use osTicket\OAuth2\AccessToken;
    // Exception as Mail\RuntimeException
    use Laminas\Mail;
    class Exception extends Mail\Exception\RuntimeException { }

    // Message
    use Laminas\Mail\Message as MailMessage;
    use Laminas\Mime\Message as MimeMessage;
    use Laminas\Mime\Mime;
    use Laminas\Mime\Part as MimePart;

    class  Message extends MailMessage {
        // MimeMessage Parts
        private $mimeMessage = null;
        // MimeMessage Content
        private $mimeContent = null;
        // Default Charset
        protected $charset = 'utf-8';
        // Default Encoding (upstream is ASCII)
        protected $encoding = 'utf-8';

        // Internal flags used to set Content-Type
        private $hasHtml = false;
        private $hasAttachments = false;
        private $hasInlineImages = false;

        public function hasAttachments() {
            return $this->hasAttachments;
        }

        public function hasInlineImages() {
            return $this->hasInlineImages;
        }

        public function hasHtml() {
            return $this->hasHtml;
        }
        // Files either attached or inline
        public function hasFiles() {
            return ($this->hasAttachments() || $this->hasInlineImages());
        }

        public function getMimeMessageParts() {
            if  (!isset($this->mimeMessage))
                $this->mimeMessage = new MimeMessage();

            return $this->mimeMessage;
        }

        public function getMimeMessageContent() {
            if  (!isset($this->mimeContent))
                $this->mimeContent = new ContentMimeMessage();

            return $this->mimeContent;
        }

        public function addHeader($key, $value) {
            return $this->getHeaders()->addHeaderLine($key, $value);
        }

        public function addHeaders(array $headers)  {
            foreach ($headers as $k => $v)
                $this->addHeader($k, $v);
        }

        private function addMimePart(MimePart $part) {
            $this->getMimeMessageParts()->addPart($part);
        }

        private function addMimeContent(MimePart $part) {
            $this->getMimeMessageContent()->addPart($part);
        }

        public function setTextBody($text, $encoding=false) {
            $part = new MimePart($text);
            $part->type = Mime::TYPE_TEXT;
            $part->charset = $this->charset;
            $part->encoding = $encoding ?: Mime::ENCODING_BASE64;
            $this->addMimeContent($part);
        }

        public function setHtmlBody($html, $encoding=false) {
            $part = new MimePart($html);
            $part->type = Mime::TYPE_HTML;
            $part->charset = $this->charset;
            $part->encoding = $encoding ?: Mime::ENCODING_BASE64;
            $this->addMimeContent($part);
            $this->hasHtml = true;
        }

        public function addInlineImage($id, $file) {
            $f = new MimePart($file->getData());
            $f->id = $id;
            $f->type = sprintf('%s; name="%s"',
                    $file->getMimeType(),
                    $file->getName());
            $f->filename = $file->getName();
            $f->disposition = Mime::DISPOSITION_INLINE;
            $f->encoding = Mime::ENCODING_BASE64;
            $this->addMimePart($f);
            $this->hasInlineImages = true;
        }

        public function addAttachment($file, $name=null)  {
            $f = new MimePart($file->getData());
            $f->type = $file->getMimeType();
            $f->filename = $name ?: $file->getName();
            $f->disposition = Mime::DISPOSITION_ATTACHMENT;
            $f->encoding = Mime::ENCODING_BASE64;
            $this->addMimePart($f);
            $this->hasAttachments = true;
        }

        public function setFrom($emailOrAddressList, $name=null) {
            // We're resetting the body here when FROM address changes - e.g
            // after failed send attempt while trying multiple SMTP accounts
            unset($this->body);
            return parent::setFrom($emailOrAddressList, $name);
        }

        public function setContentType($contentType) {
            // We can only set content type for multipart message
            if (isset($this->body)
                    &&  $this->body->isMultiPart()
                    && $contentType)  {
               if (($header=$this->getHeaders()->get('Content-Type')))
                   $header->setType($contentType); #nolint
               else
                   $this->addHeader('Content-Type', $contentType);
            }
        }

        public function setBody($body=null) {
            // We're ignoring $body param on purpose  - only added for
            // upstream compatibility - local interfaces should use
            // prepare() to set the body
            $body = $this->getMimeMessageContent();
            $contentType = $this->hasHtml()
                ? Mime::MULTIPART_ALTERNATIVE
                : Mime::TYPE_TEXT;
            // if we have files (inline images or attachments)
            if ($this->hasFiles()) {
                // Content MimePart
                $content = $body->getContentMimePart();
                // Get attachments parts (inline and files)
                $parts = $this->getMimeMessageParts()->getParts();
                // prepend content part to files parts
                array_unshift($parts, $content);
                // Create a new Mime Message and set parts
                $body = new MimeMessage();
                $body->setParts($parts); #nolint
                // We we only have inline images then content type is related
                // otherwise it's mixed.
                $contentType = $this->hasAttachments()
                    ? Mime::MULTIPART_MIXED
                    : Mime::MULTIPART_RELATED;
            }
            // Set body beaches
            parent::setBody($body);
            // Set the content type
            $this->setContentType($contentType);
        }

        public function prepare() {
            if (!isset($this->body))
                $this->setBody();
        }

    }

    // This is a wrapper class for Mime/Message that generates multipart
    // alternative content when email is multipart
    class ContentMimeMessage extends MimeMessage {
        public function getContent() {
            // unpack content parts to a content mime part
            return $this->generateMessage(); #nolint
        }

        public function getContentMimePart($type=null) {
            $part = new MimePart($this->getContent()); #nolint
            $part->type = $type ?: Mime::MULTIPART_ALTERNATIVE;
            // Set the alternate content boundary
            $part->setBoundary($this->getMime()->boundary()); #nolint
            // Clear the encoding
            $part->encoding =  "";
            return $part;
        }
    }

    // MailBoxProtocolTrait
    use Laminas\Mail\Protocol\Imap as ImapProtocol;
    use Laminas\Mail\Protocol\Pop3 as Pop3Protocol;
    trait MailBoxProtocolTrait {
        final public function init(AccountSetting $setting) {
            // Attempt to connect to the mail server
            $connect = $setting->getConnectionConfig();
            // Let's go Brandon
            parent::connect($connect['host'], $connect['port'],
                    $connect['ssl']);
            // Attempt authentication based on MailBoxAccount settings
            $auth = $setting->getAuthCredentials();
            switch (true) {
                case $auth instanceof BasicAuthCredentials:
                    if (!$this->basicAuth($auth->getUsername(), $auth->getPassword()))
                        throw new Exception('cannot login, user or password wrong');
                    break;
                case $auth instanceof OAuth2AuthCredentials:
                    if (!$this->oauth2Auth($auth->getAccessToken()))
                        throw new Exception('OAuth2 Authentication Error');
                    break;
                default:
                    throw new Exception('Unknown Credentials Type');
            }
            return true;
        }

        /*
         * Basic Authentication (Legacy) for the OG
         */
        private function basicAuth($username, $password) {
            return $this->login($username, $password);
        }

        abstract public function __construct($accountSetting);
        abstract private function oauth2Auth(AccessToken $token);
    }

    class ImapMailboxProtocol extends ImapProtocol {
        use MailBoxProtocolTrait;
         public function __construct($accountSetting) {
             $this->init($accountSetting);
         }

         /*
          * [connection begins]
          * C: C01 CAPABILITY
          * S: * CAPABILITY … AUTH=XOAUTH2
          * S: C01 OK Completed
          * C: A01 AUTHENTICATE XOAUTH2 {XOAUTH2}
          * S: A01 (OK|NO|BAD)
          * [connection continues...]
          */
         private function oauth2Auth(AccessToken $token) {
             $this->sendRequest('AUTHENTICATE', ['XOAUTH2',
                    $token->getAuthRequest()]);
             while (true) {
                 $matches = [];
                 $response = '';
                 if ($this->readLine($response, '+', true)) {
                     $this->sendRequest('');
                 } elseif (preg_match("/^CAPABILITY /i", $response)) {
                     continue;
                 } elseif (preg_match("/^OK /i", $response)) {
                     return true;
                 } elseif (preg_match('/^(NO|BAD) (.*+)$/i',
                            $response, $matches)) {
                     throw new Exception($matches[2]);
                 } else {
                     throw new Exception('Unknown Oauth2 Error:
                             '.$response);
                 }
            }
            return false;
         }

    }

    class Pop3MailboxProtocol extends Pop3Protocol {
        use MailBoxProtocolTrait;
         public function __construct($accountSetting) {
             $this->init($accountSetting);
         }

         /*
          * [connection begins]
          * C: AUTH XOAUTH2
          * S: +
          * C: {XOAUTH2}
          * S: (+OK|-ERR|+ {msg})
          * [connection continues...]
          */
         public function oauth2Auth(AccessToken $token) {
             $this->sendRequest('AUTH XOAUTH2');
             while (true) {
                $response = $this->readLine();
                $matches = [];
                if ($response == '+') {
                    // Send xOAuthRequest
                    $this->sendRequest($token->getAuthRequest());
                } elseif (preg_match("/^\+OK /i", $response)) {
                    return true;
                } elseif (preg_match('/^-ERR (.*+)$/i',
                            $response, $matches)) {
                    throw new Exception($matches[2]);
                } else {
                    break;
                }
             }
             return false;
         }

         /*
          * readLine
          *
          * Pop3 Protocol doesn't have readLine function and readRresponse
          * has hardcoded status of "+OK" whereas Oauth2 response returns "+"
          * on AUTH XOAUTH2 command.
          */
         public function readLine() {
             $result = fgets($this->socket);
             if (!is_string($result))
                throw new Exception('read failed - connection closed');
             return trim($result);
         }

    }

    // MailBoxStorageTrait
    use Laminas\Mail\Storage\Imap as ImapStorage;
    use Laminas\Mail\Storage\Pop3 as Pop3Storage;
    use RecursiveIteratorIterator;
    trait MailBoxStorageTrait {
        private $folder;
        private $hostInfo;

        private function init(AccountSetting $setting) {
            $this->folder = $setting->getAccount()->getFolder();
            $this->hostInfo =  $setting->getHostInfo();
        }

        public function getHostInfo() {
            return $this->hostInfo;
        }

        private function getFolder() {
            return $this->folder;
        }

        public function createFolder($name, $parentFolder = null)  {
            try {
                parent::createFolder($name, $parentFolder);
                $this->folders = null;
                return true;
            } catch (\Exception $ex) {
                // noop
            }
            return false;
        }

        public function hasFolder($folder, $rootFolder = null) {
            $folders = $this->getFolders($rootFolder);
            if (is_array($folders)
                    && in_array(strtolower($folder), $folders))
                return true;

            // Try selecting the folder.
            try {
                $this->selectFolder($folder);
                return true;
            } catch (\Exception $ex) {
                //noop
            }
            return false;
        }

        public  function getFolders($rootFolder = null)  {
            if (!isset($this->folders)) {
                $folders = new RecursiveIteratorIterator(
                    parent::getFolders(),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                $this->folders = [];
                foreach ($folders as $name => $folder) {
                    if (!$folder->isSelectable()) #nolint
                        continue;
                    $this->folders[] =  strtolower($folder->getGlobalName()); #nolint
                }
            }
            return $this->folders;
        }

        /*
         * getRawEmail
         *
         * Given message number - get full raw email (headers + content)
         *
         */
        public function getRawEmail(int $i) {
            return $this->getRawHeader($i) . $this->getRawContent($i);
        }

        /*
         * move an existing message to a folder
         *
         */
        public function moveMessage($id, $folder) {
            try {
                return parent::moveMessage($id, $folder);
            } catch (\Throwable $t) {
                // noop
            }
            return false;
        }

        /*
         * Remove a message from server.
         *
         */
        public function removeMessage($i) {
            try {
                return parent::removeMessage($i);
            } catch (\Throwable $t) {
                // noop
            }
            return false;
        }

        /*
         * markAsSeen
         */
        public function markAsSeen($i) {
            // noop - storage that implement it should define it
        }

        public function expunge() {
             // noop - only IMAP
        }
    }

    // Imap
    use Laminas\Mail\Storage;
    class Imap extends ImapStorage {
        use MailBoxStorageTrait;
        private $folders;

        public function __construct($accountSetting) {
            $protocol = new ImapMailBoxProtocol($accountSetting);
            parent::__construct($protocol);
            $this->init($accountSetting);
        }

        // Mark message as seen
        public function markAsSeen($i) {
            try {
                return $this->setFlags($i, [Storage::FLAG_SEEN]);
            } catch (\Throwable $t) {
                return false;
            }
        }

        // Expunge mailbox
        public function expunge() {
            return $this->protocol->expunge();
        }
    }

    // Pop3
    class Pop3 extends Pop3Storage {
        use MailBoxStorageTrait;

        public function __construct($accountSetting) {
            $protocol = new Pop3MailboxProtocol($accountSetting);
            parent::__construct($protocol);
            $this->init($accountSetting);
        }
    }

    // Smtp
    use Laminas\Mail\Transport\Smtp as SmtpTransport;
    class Smtp extends SmtpTransport {
        private $connected = false;
        public function __construct(SmtpOptions $options) {
            parent::__construct($options);
        }

        private function isConnected() {
            return $this->connected;
        }

        public function connect() {
            try {
                if (!$this->isConnected() && parent::connect())
                    $this->connected = true;
                return $this->isConnected();
            } catch (\Throwable $ex) {
                // Smtp protocol throws an Exception via error handler
                // resulting in unrestored handler on socket error
                restore_error_handler();
                throw $ex;
            }
        }

        public function sendMessage(Message $message) {
            try {
                // Make sure the body is set
                $message->prepare();
                parent::send($message);
            } catch (\Throwable $ex) {
                $this->connected = false;
                throw $ex;
            }
            return true;
        }
    }

    // SmtpOptions
    use Laminas\Mail\Transport\SmtpOptions as SmtpSettings;
    class SmtpOptions extends SmtpSettings {
        public function __construct(AccountSetting $setting) {
            parent::__construct($this->buildOptions($setting));
        }

        private function buildOptions(AccountSetting $setting) {
            // Build out SmtpOptions options based on SmtpAccount Settings
            $config = [];
            $connect = $setting->getConnectionConfig();
            $auth = $setting->getAuthCredentials();
            switch (true) {
                case $auth instanceof NoAuthCredentials:
                    // No Authentication - simply return host and port
                    return [
                        'host'      => $connect['host'],
                        'port'      => $connect['port']
                    ];
                    break;
                case $auth instanceof BasicAuthCredentials:
                    $config = [
                        'username' => $auth->getUsername(),
                        'password' => $auth->getPasswd(),
                        'ssl' => $connect['ssl'],
                    ];
                    break;
                case $auth instanceof OAuth2AuthCredentials:
                    $token = $auth->getAccessToken();
                    if ($token->hasExpired())
                        throw new Exception('Access Token is Expired');
                    $config = [
                        'xoauth2' => $token->getAuthRequest(),
                        'ssl' => $connect['ssl'],
                    ];
                    break;
                default:
                    throw new Exception('Unknown Authentication Type');
            }

            return [
                'host'      => $connect['host'],
                'port'      => $connect['port'],
                'connection_class'  => $auth->getConnectionClass(),
                'connection_config' => $config
            ];
        }
    }

    // Sendmail
    use Laminas\Mail\Transport\Sendmail as SendmailTransport;
    class Sendmail extends SendmailTransport {
        public function __construct($options) {
            parent::__construct($options);
        }

        public function sendMessage(Message $message) {
            try {
                // Make sure the body is set
                $message->prepare();
                parent::send($message);
                return true;
            } catch (\Throwable $ex) {
                throw $ex;
            }
            return true;
        }
    }

    // Credentials
    abstract class AuthCredentials {
        static $class = 'plain';

        public function getConnectionClass() {
            return static::$class;
        }

        public function serialize() {
            return json_encode($this->__serialize());
        }

        public function __serialize() {
            return $this->toArray();
        }

        public static function init(array $options) {
            return new static($options);
        }

        abstract function __construct(array $options);
        abstract function toArray();
    }

    class NoAuthCredentials extends AuthCredentials {
        private $username;

        public function __construct(array $options) {
            if (empty($options['username'])) {
                throw new Exception(sprintf(
                            __('Required option not passed: "%s"'),
                            'username'));
            }
            $this->username = $options['username'];
        }

        public function getUsername() {
            return $this->username;
        }
        public function toArray() {
            return [
                'username' => $this->getUsername()
            ];
        }
    }

    class BasicAuthCredentials extends AuthCredentials {
        static $class = 'login';
        private $username;
        private $password;

        public function __construct(array $options) {
            if (empty($options['username'])) {
                throw new Exception(sprintf(
                            __('Required option not passed: "%s"'),
                            'username'));
            }

            if (empty($options['password'])) {
                throw new Exception(sprintf(
                            __('Required option not passed: "%s"'),
                            'password'));
            }
            $this->username = $options['username'];
            $this->password = $options['password'];
        }

        public function getUsername() {
            return $this->username;
        }

        public function getPasswd() {
            return $this->getPassword();
        }

        public function getPassword() {
            return $this->password;
        }

        public function toArray() {
            return [
                'username' => $this->getUsername(),
                'password' => $this->getPassword()
            ];
        }
    }

    class OAuth2AuthCredentials extends AuthCredentials {
        static $class = 'osTicket\Mail\Protocol\Smtp\Auth\OAuth2';
        private $token;
        public function __construct(array $options) {
            if (empty($options['access_token'])) {
                throw new Exception(sprintf(
                            __('Required option not passed: "%s"'),
                            'access_token'));
            }

            if (empty($options['resource_owner_email'])) {
                throw new Exception(sprintf(
                            __('Required option not passed: "%s"'),
                            'resource_owner_email'));
            }
            $this->token = new AccessToken($options);
        }

        public function getToken() {
            return $this->token;
        }

        public function getAccessToken($signature=false) {
           $token = $this->getToken();
           // check signature if requested
           return (!$signature
                   || !strcmp($signature, $token->getConfigSignature()))
               ? $token : null;
        }

        public function toArray() {
            return $this->token->toArray();
        }
    }

    // osTicket/Mail/AccountSetting
    class AccountSetting {
        private $account;
        private $creds;
        private $connection = [];
        private $errors = [];

        public function __construct(\EmailAccount $account) {
            // Set the account
            $this->account = &$account;
            // Parse Connection Options
            // We allow scheme to hint for encryption for people using ssl or tls
            // on nonstandard ports.
            $host = $account->getHost();
            $ssl = null;
            $matches = [];
            if (preg_match('~^(ssl|tls)://(.*+)$~iu', $host, $matches))
                list(, $ssl, $host) = $matches;
            // Set ssl or tls on based on standard ports
            $port = $account->getPort();
            if (!$ssl && $port) {
                if (in_array($port, [465, 993, 995]))
                    $ssl = 'ssl';
                elseif (in_array($port, [587]))
                    $ssl = 'tls';
            }

            $this->connection = [
                'host' => $host,
                'port' => (int) $port,
                'ssl' => $ssl,
                'protocol' => strtoupper($account->getProtocol()),
                'name' => null
            ];

            // Set errors to null to clear validation
            $this->errors = null;
        }

        public function getName() {
            return $this->connection['name'];
        }

        public function getHost() {
            return $this->connection['host'];
        }

        public function getPort() {
            return $this->connection['port'];
        }

        public function getSsl() {
            return $this->connection['ssl'];
        }

        public function getProtocol() {
            return $this->connection['protocol'];
        }

        public function setCredentials(AuthCredentials $creds) {
            $this->creds = $creds;
        }

        public function getCredentials() {
            if (!isset($this->creds))
                $this->creds = $this->account->getCredentials();
            return $this->creds;
        }

        public function getAuthCredentials() {
            return $this->getCredentials();
        }

        public function getAccount() {
            return $this->account;
        }

        public function getConnectionConfig() {
            return $this->connection;
        }

        public function getHostInfo() {
            return $this->describe();
        }

        public function asArray() {
            return $this->getConnectionConfig();
        }

        public function describe() {
            return sprintf('%s://%s:%s/%s',
                    $this->getSsl() ?: 'none',
                    $this->getHost(),
                    $this->getPort(),
                    $this->getProtocol());
        }

        private function validate() {

            if (!isset($this->errors)) {
                $this->errors = [];
                $info = $this->getConnectionConfig();
                foreach (['host', 'port', 'protocol'] as $p ) {
                    if (!isset($info[$p]) || !$info[$p])
                        $this->errors[$p] = sprintf('%s %s',
                                strtoupper($p), __('Required'));
                }
                // TODO: Validate hostname - for now we're punting to be
                // validated at the protocol connection level
            }
            return !count($this->errors);
        }

        public function isValid() {
            return $this->validate();
        }

        public function getErrors() {
            return $this->errors;
        }
    }
}

namespace osTicket\Mail\Protocol\Smtp\Auth {
    // Exception as Mail\RuntimeException
    use Laminas\Mail;
    class Exception extends Mail\Exception\RuntimeException { }
    use Laminas\Mail\Protocol\Smtp;
    class OAuth2 extends Smtp {
        private $xoauth2;
        public function __construct($host = null, $port = null, $config = null) {
            $this->setParams($host, $config);
            parent::__construct($host, $port, $config);
        }

        private function setParams($host, $config) {
            $_config = [];
             if (is_array($host))
                 $_config = is_array($config)
                     ? array_replace_recursive($host, $config)
                     : $host;
             if (is_array($_config) && isset($_config['xoauth2']))
                 $this->xoauth2 = $_config['xoauth2'];
        }

        private function getAuthRequest() {
            return $this->xoauth2;
        }

        /*
         * [connection begins]
         * C: auth xoauth2
         * S: 334
         * C: {XOAUTH2}
         * S: (235|XXX)
         * [connection continues...]
        */
        public function auth() {
            // Check Parent
            parent::auth();
            // Make sure we have XOAUTH2
            if (!($xoauth2=$this->getAuthRequest()))
                throw new Exception('XOAUTH2 Required');
            $this->_send('AUTH XOAUTH2');
            $this->_expect(334);
            $this->_send($xoauth2);
            $this->_expect(235);
            $this->auth = true;
        }
    }
}
