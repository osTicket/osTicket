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
    use Laminas\Mail\Header;
    use osTicket\Mail\Header\ReturnPath;

    class  Message extends MailMessage {
        // Message Id (mid)
        private $mid;
        // MimeMessage Parts
        private $mimeParts = null;
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

        public function getId() {
             if (!isset($this->mid)
                     &&  ($header=$this->getHeader('message-id')))
                 $this->mid = $header->getId();
             return $this->mid;
        }

        public function getMimeMessageParts() {
            if  (!isset($this->mimeParts))
                $this->mimeParts = new MimeMessage();

            return $this->mimeParts;
        }

        public function getMimeMessageContent() {
            if  (!isset($this->mimeContent))
                $this->mimeContent = new ContentMimeMessage();

            return $this->mimeContent;
        }

        public function getHeader(string $name) {
            return $this->getHeaders()->getHeader($name);
        }

        public function addHeader($header, $value=null) {
            if (isset($value))
                $this->getHeaders()->addHeaderLine($header, $value);
            else
                $this->getHeaders()->addHeader($header);
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

        // Expects a  valid date e.g date('r')
        public function setDate(string $date) {
            $d = new Header\Date($date);
            // Laminas auto adds Date upstream when any header is added
            // We're clearing it here to we back that-date up like it's
            // 99 & 2000 ~ Juvenile
            $this->getHeaders()->removeHeader('date');
            $this->addHeader($d);
        }

        // Please use this method to set Message-Id otherwise it will be
        // utf-8 endcoded and results is an invalid email & bounces
        public function setMessageId(string $id) {
            try {
                $header = new Header\MessageId();
                $header->setId($id);
                $this->addHeader($header);
                $this->mid = $header->getId();
            } catch (\Throwable $t)  {
                // Ignore invalid mid. Upstream will generate one
            }
        }

        public function getMessageId() {
            return $this->getId();
        }

        // Valid email address required or no return "<>" tag
        public function setReturnPath($email) {
            try {
                // Exception is thrown on invalid email address
                $header = new ReturnPath();
                $header->addAddress($email);
                $this->getHeaders()->removeHeader($header->getType());
                $this->addHeader($header);
            } catch (\Throwable $t) {
                // It's not email - perhaps it's a tag?
                if (!strcmp($email, '<>'))
                    $this->addHeader($header->getFieldName(), $email);
                // Silently dropping the invalid path
            }
        }

        public function addInReplyTo($inReplyTo) {
             if (!is_array($inReplyTo))
                $inReplyTo = explode(' ', $inReplyTo);
            try {
                $header = new Header\InReplyTo();
                $header->setIds($inReplyTo); #nolint
                $this->addHeader($header);
            } catch (\Throwable $t) {
                // Mshenzi
            }
        }

        public function addReferences($references) {
            if (!is_array($references))
                $references = explode(' ', $references);
            try {
                $header = new Header\References();
                $header->setIds($references); #nolint
                $this->addHeader($header);
            } catch (\Throwable $t) {
                // Mshenzi
            }
        }

        // Set Message Sender is useful for SendMail transport, its basically -f
        // parameter in the mail() interface
        public function setSender($email, $name=null) {
            try {
                // Exception is thrown on invalid email address
                $header = new Header\Sender();
                $header->setAddress($email, $name); #nolint
                $this->addHeader($header);
            } catch (\Throwable $t) {
                // Silently ignore invalid email sender defaults to FROM
                // addresses
            }
        }

        public function setFrom($email, $name=null) {
            // We're resetting the body here when FROM address changes - e.g
            // after failed send attempt while trying multiple SMTP accounts
            unset($this->body);
            return parent::setFrom($email, $name);
        }

        // This is used to set FROM & and clear Sender to a new Email Address
        public function setOriginator($email, $name=null) {
            // Set the FROM  Header
            $this->setFrom($email, $name);
            // Remove Sender Header
            $this->getHeaders()->removeHeader('sender');
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
            parent::__construct($connect['host'], $connect['port'],
                    $connect['ssl'], true);
            // Attempt authentication based on MailBoxAccount settings
            $auth = $setting->getAuthCredentials();
            switch (true) {
                case $auth instanceof BasicAuthCredentials:
                    if (!$this->basicAuth($auth->getUsername(), $auth->getPassword()))
                        throw new Exception('cannot login, user or password wrong');
                    break;
                case $auth instanceof OAuth2AuthCredentials:
                    // Get OAuth2 Authentication Request
                    $authen = $auth->getAuthRequest($setting->getUser());
                    if (!$this->oauth2Auth($authen))
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
        abstract protected function oauth2Auth($authen);
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
         private function oauth2Auth($authen) {
             $this->sendRequest('AUTHENTICATE', ['XOAUTH2', $authen]);
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
         public function oauth2Auth($authen) {
             $this->sendRequest('AUTH XOAUTH2');
             while (true) {
                $response = $this->readLine();
                $matches = [];
                if ($response == '+') {
                    // Send xOAuthRequest
                    $this->sendRequest($authen);
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

         public function login($user, $password, $tryApop = true) {
             try {
                parent::login($user, $password, $tryApop);
                return true;
             } catch (\Throwable $e) {
                 throw new Exception(__('login failed').': '.$e->getMessage());
             }
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
         * Caller should catch possible exception
         */
        public function moveMessage($i, $folder) {
            parent::moveMessage($i, $folder);
            return true;
        }

        /*
         * Remove a message from server.
         *
         * Caller should catch possible exception
         */
        public function removeMessage($i) {
            parent::removeMessage($i);
            return true;
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

        /**
         * Remove a message from server without expunging the mailbox
         *
         * Laminas Mail (upstream) auto expunges the mailbox on message
         * removal or move (copy + remove) - which can cause major issues
         * for us since we fetcher uses message sequence numbers to fetch
         * messages / emails.
         *
         * We expunge the mailbox at the end if fetch session.
         *
         * TODO: Make PR upstream to support calling removeMessage with
         * a boolean flag i.e removeMessage(int $id, bool $expunge = true)
         *
         */
        public function removeMessage($i) {
            if (! $this->protocol->store([Storage::FLAG_DELETED], $i, null, '+')) {
                throw new Exception('cannot set deleted flag');
            }
            return true;
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

        // Build out SmtpOptions options based on SmtpAccount Settings
        private function buildOptions(AccountSetting $setting) {
            // Dont send 'QUIT' on __destruct()
            $config = [
                'use_complete_quit' => false,
                'novalidatecert' => true
            ];
            $connect = $setting->getConnectionConfig();
            $auth = $setting->getAuthCredentials();
            switch (true) {
                case $auth instanceof NoAuthCredentials:
                    // No Authentication - simply return host and port
                    return [
                        'host' => $connect['host'],
                        'port' => $connect['port'],
                        'name' => $connect['name'],
                    ];
                    break;
                case $auth instanceof BasicAuthCredentials:
                    $config += [
                        'username' => $auth->getUsername(),
                        'password' => $auth->getPasswd(),
                        'ssl' => $connect['ssl'],
                    ];
                    break;
                case $auth instanceof OAuth2AuthCredentials:
                    $token = $auth->getAccessToken();
                    if ($token->hasExpired())
                        throw new Exception('Access Token is Expired');
                    $config += [
                        'xoauth2' => $token->getAuthRequest(),
                        'ssl' => $connect['ssl'],
                    ];
                    break;
                default:
                    throw new Exception('Unknown Authentication Type');
            }

            return [
                'host' => $connect['host'],
                'port' => $connect['port'],
                'name' => $connect['name'],
                'connection_time_limit' => 300, # 5 minutes limit
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

        public function getAuthRequest($user=null) {
            return $this->getToken()
                ? $this->getToken()->getAuthRequest($user)
                : null;
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
            $port = (int) $account->getPort();
            $ssl = null;
            $matches = [];
            if (preg_match('~^(ssl|tls|plain)://(.*+)$~iu',
                        strtolower($host), $matches)) {
                list(, $ssl, $host) = $matches;
                // Clear ssl when "plain" connection is being forced without
                // using port number as the indicator!
                $ssl = strcmp($ssl, 'plain') ? $ssl : null;
                // Why would someone use a standard encryption based port
                // for unencrypted connection is beyond me - but apparently
                // it's a thing!!
            } elseif (!$ssl && $port) {
                // Set ssl or tls on based on standard ports
                if (in_array($port, [465, 993, 995]))
                    $ssl = 'ssl';
                elseif (in_array($port, [587]))
                    $ssl = 'tls';
            }

            // Set the connection settings
            $this->connection = [
                'host' => $host,
                'port' => $port,
                'ssl' => $ssl,
                'protocol' => strtoupper($account->getProtocol()),
                'name' => self::get_hostname(),
            ];

            // Set errors to null to clear validation
            $this->errors = null;
        }

        public function getUser() {
            return $this->account->getEmail()->getEmail();
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
                    $this->getSsl() ?: 'plain',
                    $this->getHost(),
                    $this->getPort(),
                    $this->getProtocol());
        }

        private function validate() {

            if (!isset($this->errors)) {
                // Set errors to an array to to make sure don't
                // unneccesarily validate valid info again.
                $this->errors = [];
                // We're simply making sure required info are set. True
                // validation will happen at the protocol connection level
                $info = $this->getConnectionConfig();
                foreach (['host', 'port', 'protocol'] as $p ) {
                    if (!isset($info[$p]) || !$info[$p])
                        $this->errors[$p] = sprintf('%s %s',
                                strtoupper($p), __('Required'));
                }
            }
            return !count($this->errors);
        }

        public function isValid() {
            return $this->validate();
        }

        public function getErrors() {
            return $this->errors;
        }

        /*
         * get_hostname
         *
         * Please note that this is different from getHost above
         *
         * Here we're getting the hostname to use on HELO/EHLO when
         * initiating an SMTP connection. It should be a valid hostname with
         * valid reverse-lookup for better deliverability.
         *
         * Perhaps this can be a setting in the future but allowing users
         * to set it to **anything** will results in more mail issues than just
         * defaulting to what the OS tells us or localhost for that matter.
         *
         * For now, we're simply asking core osTicket to give us the OS hostname
         * otherwise it will default to localhost which some mail servers frawns
         * upon since it won't have a valid reverse-lookup.
         *
         */
        private static function get_hostname() {
            // We're simply returning what the OS is telling us!
            return php_uname('n');
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

namespace osTicket\Mail\Header {
    use Laminas\Mail\Header\AbstractAddressList;
    use Laminas\Mail\Header\HeaderInterface;
    use Laminas\Mail\Address;

    class ReturnPath extends AbstractAddressList {
        protected $fieldName = 'Return-Path';
        protected static $type = 'return-path';

        public function addAddress($email) {
            $this->getAddressList()->add(new Address($email)); #nolint
        }

        public function getFieldValue($format = HeaderInterface::FORMAT_RAW) {
            // We're simply intercepting Value here to add <> to the email
            return sprintf('<%s>', parent::getFieldValue($format));
        }

        public function getType() {
            return self::$type;
        }
    }
}
