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
        private $mimeMessage = null;
        // Charset
        private $charset = 'utf-8';
        // Internal flags used to set Content-Type
        private $hasHtml = false;
        private $hasAttachments = false;
        private $hosInlineImages = false;

        public function getMimeMessage() {
            if  (!isset($this->mimeMessage))
                $this->mimeMessage = new MimeMessage();

            return $this->mimeMessage;
        }

        public function addHeader($key, $value) {
            return $this->getHeaders()->addHeaderLine($key, $value);
        }

        public function addHeaders(array $headers)  {
            foreach ($headers as $k => $v)
                $this->addHeader($k, $v);
        }

        private function addMimePart(MimePart $part) {
            $this->getMimeMessage()->addPart($part);
        }

        public function setTextBody($text, $encoding=false) {
            $part = new MimePart($text);
            $part->type = Mime::TYPE_TEXT;
            $part->charset = $this->charset;
            $part->encoding = $encoding ?: Mime::ENCODING_BASE64;
            $this->addMimePart($part);
            //$this->setContentType($alt ? 'multipart/alternative' : 'text/plain');
        }

        public function setHtmlBody($html, $encoding=false) {
            $part = new MimePart($html);
            $part->type = Mime::TYPE_HTML;
            $part->charset = $this->charset;
            $part->encoding = $encoding ?: Mime::ENCODING_BASE64;
            $this->addMimePart($part);
            $this->hasHtml = true;
        }

        public function addInlineImage($id, $file) {
            $f = new MimePart($file->getData());
            $f->id = $id;
            $f->type = $file->getMimeType();
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

        public function setContentType($type=null) {
            // We can only set content type for multipart message
            if ($this->body->isMultiPart())  {
                // Set Content-Type
                $contentType = $type ?: 'text/plain';
                if ($this->hasHtml)
                    $contentType = 'multipart/alternative';
                if ($this->hasAttachments)
                    $contentType = 'multipart/related';

               if (($header=$this->getHeaders()->get('Content-Type')))
                   $header->setType($contentType); #nolint
               else
                   $this->addHeader('Content-Type', $contentType);
            }
        }

        public function setBody($body=null) {
            $body = $body ?: $this->getMimeMessage();
            parent::setBody($body);
            $this->setContentType();
        }
    }

    // MailBoxProtocolTrait
    use Laminas\Mail\Protocol\Imap as ImapProtocol;
    use Laminas\Mail\Protocol\Pop3 as Pop3Protocol;
    trait MailBoxProtocolTrait {
        final public function init(AccountOptions $accountOptions) {
            // Attempt to connect to the mail server
            $connect = $accountOptions->getConnectioOptions();
            // Let's go Brandon
            parent::connect($connect['host'], $connect['port'],
                    $connect['ssl']);
            // Attempt authentication based on MailBoxAccount settings
            $auth = $accountOptions->getAuth();
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

        abstract public function __construct($accountOptions);
        abstract private function oauth2Auth(AccessToken $token);
    }

    class ImapMailboxProtocol extends ImapProtocol {
        use MailBoxProtocolTrait;
         public function __construct($accountOptions) {
             $this->init($accountOptions);
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
         public function __construct($accountOptions) {
             $this->init($accountOptions);
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

        private function init(\MailBoxAccount $mailbox) {
            $this->folder = $mailbox->getFolder();
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
                    && in_array(strtolower($folder),
                        array_map('strtolower', $folders)))
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
                    $this->folders[] =  $folder->getGlobalName(); #nolint
                }
            }
            return $this->folders;
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

        public function __construct($accountOptions) {
            $protocol = new ImapMailBoxProtocol($accountOptions);
            parent::__construct($protocol);
            $this->init($accountOptions->getAccount());
        }

        // Mark message as seen
        public function markAsSeen($i) {
            $this->setFlags($i, [Storage::FLAG_SEEN]);
        }

        // Expunge mailbox
        public function expunge() {
            return $this->protocol->expunge();
        }
    }

    // Pop3
    class Pop3 extends Pop3Storage {
        use MailBoxStorageTrait;

        public function __construct($accountOptions) {
            $protocol = new Pop3MailboxProtocol($accountOptions);
            parent::__construct($protocol);
            $this->init($accountOptions->getAccount());
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
            // Make sure the body is set
            if (!isset($message->body))
                $message->setBody();

            try {
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
        public function __construct(AccountOptions $options) {
            parent::__construct($this->buildOptions($options));
        }

        private function buildOptions(AccountOptions $options) {
            // Build out SmtpOptions options based on SmtpAccount Settings
            $config = [];
            $connect = $options->getConnectioOptions();
            $auth = $options->getAuth();
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
            // Make sure the body is set
            if (!isset($message->body))
                $message->setBody();

            try {
                parent::send($message);
                return true;
            } catch (\Throwable $ex) {
                throw $ex;
            }
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

        public function getAccessToken() {
            return $this->getToken();
        }

        public function toArray() {
            return $this->token->toArray();
        }
    }

    // osTicket/Mail/AccountOptions
    class AccountOptions {
        private $account;
        private $connectOptions = [];

        public function __construct(\EmailAccount $account) {
            // Set the account
            $this->account = $account;
            // Parse Connection Options
            // We allow scheme to hint for encryption for people using ssl or tls
            // on nonstandard ports.
            $host = $account->getHost();
            $ssl = $account->getEncryption();
            $matches = [];
            if (preg_match('~^(ssl|tls)://(.*+)$~iu', $host, $matches))
                list(, $host, $ssl) = $matches;
            // Set ssl or tls on based on standard ports
            $port = $account->getPort();
            if (!$ssl && $port) {
                if (in_array($port, [465, 993, 995]))
                    $ssl = 'ssl';
                elseif (in_array($port, [587]))
                    $ssl = 'tls';
            }

            $this->connectOptions = [
                'host' => $host,
                'port' => (int) $port,
                'ssl' => $ssl,
                'name' => null
            ];
        }

        public function getName() {
            return $this->connectOptions['name'];
        }

        public function getHost() {
            return $this->connectOptions['host'];
        }

        public function getPort() {
            return $this->connectOptions['port'];
        }

        public function getSsl() {
            return $this->connectOptions['ssl'];
        }

        public function getCredentials() {
            return $this->account->getCredentials();
        }

        public function getAuth() {
            return $this->getCredentials();
        }

        public function getAccount() {
            return $this->account;
        }

        public function getConnectioOptions() {
            return $this->connectOptions;
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
            $this->_expect(234);
            $this->_send($xoauth2);
            $this->_expect(235);
            $this->auth = true;
        }
    }
}
