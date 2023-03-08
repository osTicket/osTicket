<?php
/**
 * class.oauth2.php
 *
 * osTicket OAuth2 Utils & Helpers
 *
 * @author Peter Rotich <peter@osticket.com>
 * @copyright Copyright (c) osTicket <gpl@osticket.com>
 *
 */
namespace osTicket\OAuth2 {
    // Define exceptions as RunTimeException
    class Exception extends \RuntimeException { }
    /**
     *  osTicket OAuth2 Access Token
     *
     * Based on AcessToken class from league/oauth2-client library (MIT)
     * @link http://tools.ietf.org/html/rfc6749#section-1.4 Access Token (RFC 6749, §1.4)
     *
     */
    class AccessToken {
        protected $accessToken;
        protected $expires;
        protected $refreshToken;
        protected $resourceOwnerId;
        // osTicket specific
        protected $resourceOwnerEmail;
        protected $configSignature;

        public function __construct(array $options = []) {
            if (empty($options['access_token'])) {
                throw new \InvalidArgumentException(sprintf(
                            __('Required option not passed: "%s"'),
                            'access_token'));
            }

            $this->accessToken = $options['access_token'];
            if (!empty($options['refresh_token']))
                $this->refreshToken = $options['refresh_token'];

            if (!empty($options['expires']))
                $this->expires = $options['expires'];

            if (!empty($options['config_signature']))
                $this->configSignature = $options['config_signature'];

            if (!empty($options['resource_owner_id']))
                $this->resourceOwnerId = $options['resource_owner_id'];

            if (!empty($options['resource_owner_email']))
                $this->resourceOwnerEmail = $options['resource_owner_email'];
        }

        public function getToken() {
            return $this->accessToken;
        }

        public function getAccessToken() {
            return $this->getToken();
        }

        public function getRefreshToken() {
            return $this->refreshToken;
        }

        public function getExpires() {
            return $this->expires;
        }

        public function getResourceOwnerId() {
            return $this->resourceOwnerId;
        }

        public function getResourceOwnerEmail() {
            return $this->resourceOwnerEmail;
        }

        public function getResourceOwner() {
            return $this->getResourceOwnerEmail();
        }

        public function getConfigSignature() {
            return $this->configSignature;
        }

        public function hasExpired() {
            $expires = $this->getExpires();
            if (empty($expires))
                throw new \RuntimeException('"expires" is not set on the token');

            return $expires < time();
        }

        public function isExpired() {
            return $this->hasExpired();
        }

        public function isMatch($email, $strict=false) {
            return (!$strict || strcasecmp($this->getResourceOwnerEmail(), $email) === 0);
        }

        public function getAuthRequest($user=null) {
            if ($this->hasExpired())
                throw new Exception('Access Token is Expired');

            return base64_encode(sprintf("user=%s\1auth=Bearer %s\1\1",
                $user ?? $this->getResourceOwner(),
                $this->getAccessToken()));
        }

        public function __toString() {
            return (string) $this->getToken();
        }

        public function toArray() {
            return [
                'access_token'  => $this->getToken(),
                'refresh_token' => $this->getRefreshToken(),
                'expires' => $this->getExpires(),
                'config_signature' => $this->getConfigSignature(),
                'resource_owner_id' => $this->getResourceOwnerId(),
                'resource_owner_email' => $this->getResourceOwnerEmail(),
            ];
        }
    }
}
