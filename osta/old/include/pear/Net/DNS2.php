<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * DNS Library for handling lookups and updates.
 *
 * PHP Version 5
 *
 * Copyright (c) 2010, Mike Pultz <mike@mikepultz.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Mike Pultz nor the names of his contributors
 *     may be used to endorse or promote products derived from this
 *     software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Networking
 * @package   Net_DNS2
 * @author    Mike Pultz <mike@mikepultz.com>
 * @copyright 2010 Mike Pultz <mike@mikepultz.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   SVN: $Id: DNS2.php 198 2013-05-26 05:05:22Z mike.pultz $
 * @link      http://pear.php.net/package/Net_DNS2
 * @since     File available since Release 0.6.0
 *
 */

/*
 * register the auto-load function
 *
 */
spl_autoload_register('Net_DNS2::autoload');

/**
 * This is the base class for the Net_DNS2_Resolver and Net_DNS2_Updater
 * classes.
 *
 * @category Networking
 * @package  Net_DNS2
 * @author   Mike Pultz <mike@mikepultz.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link     http://pear.php.net/package/Net_DNS2
 * @see      Net_DNS2_Resolver, Net_DNS2_Updater
 *
 */
class Net_DNS2
{
    /*
     * the current version of this library
     */
    const VERSION = '1.3.1';

    /*
     * the default path to a resolv.conf file
     */
    const RESOLV_CONF = '/etc/resolv.conf';

    /*
     * use TCP only (true/false)
     */
    public $use_tcp = false;

    /*
     * DNS Port to use (53)
     */
    public $dns_port = 53;

    /*
     * the ip/port for use as a local socket
     */
    public $local_host = '';
    public $local_port = 0;

    /*
     * timeout value for socket connections
     */
    public $timeout = 5;

    /*
     * randomize the name servers list
     */
    public $ns_random = false;

    /*
     * default domains
     */
    public $domain = '';

    /*
     * domain search list - not actually used right now
     */
    public $search_list = array();

    /*
     * enable cache; either "shared", "file" or "none"
     */
    public $cache_type = 'none';

    /*
     * file name to use for shared memory segment or file cache
     */
    public $cache_file = '/tmp/net_dns2.cache';

    /*
     * the max size of the cache file (in bytes)
     */
    public $cache_size = 10000;

    /*
     * the method to use for storing cache data; either "serialize" or "json"
     *
     * json is faster, but can't remember the class names (everything comes back
     * as a "stdClass Object"; all the data is the same though. serialize is
     * slower, but will have all the class info.
     *
     * defaults to 'serialize'
     */
    public $cache_serializer = 'serialize';

    /*
     * by default, according to RFC 1034
     *
     * CNAME RRs cause special action in DNS software.  When a name server
     * fails to find a desired RR in the resource set associated with the
     * domain name, it checks to see if the resource set consists of a CNAME
     * record with a matching class.  If so, the name server includes the CNAME
     * record in the response and restarts the query at the domain name
     * specified in the data field of the CNAME record.
     *
     * this can cause "unexpected" behavious, since i'm sure *most* people
     * don't know DNS does this; there may be cases where Net_DNS2 returns a
     * positive response, even though the hostname the user looked up did not
     * actually exist.
     *
     * strict_query_mode means that if the hostname that was looked up isn't
     * actually in the answer section of the response, Net_DNS2 will return an
     * empty answer section, instead of an answer section that could contain
     * CNAME records.
     *
     */
    public $strict_query_mode = false;

    /*
     * if we should set the recursion desired bit to 1 or 0.
     *
     * by default this is set to true, we want the DNS server to perform a recursive
     * request. If set to false, the RD bit will be set to 0, and the server will
     * not perform recursion on the request.
     */
    public $recurse = true;

    /*
     * request DNSSEC values, by setting the DO flag to 1; this actually makes
     * the resolver add a OPT RR to the additional section, and sets the DO flag
     * in this RR to 1
     *
     */
    public $dnssec = false;

    /*
     * set the DNSSEC AD (Authentic Data) bit on/off; the AD bit on the request
     * side was previously undefined, and resolvers we instructed to always clear
     * the AD bit when sending a request.
     *
     * RFC6840 section 5.7 defines setting the AD bit in the query as a signal to
     * the server that it wants the value of the AD bit, without needed to request
     * all the DNSSEC data via the DO bit.
     *
     */
    public $dnssec_ad_flag = false;

    /*
     * set the DNSSEC CD (Checking Disabled) bit on/off; turning this off, means
     * that the DNS resolver will perform it's own signature validation- so the DNS
     * servers simply pass through all the details.
     *
     */
    public $dnssec_cd_flag = false;

    /*
     * the EDNS(0) UDP payload size to use when making DNSSEC requests
     * see RFC 2671 section 6.2.3 for more details
     *
     * http://tools.ietf.org/html/draft-ietf-dnsext-rfc2671bis-edns0-10
     *
     */
    public $dnssec_payload_size = 1280;

    /*
     * local sockets
     */
    protected $sock = array('udp' => array(), 'tcp' => array());

    /*
     * name server list
     */
    protected $nameservers = array();

    /*
     * if the socket extension is loaded
     */
    protected $sockets_enabled = false;

    /*
     * the TSIG or SIG RR object for authentication
     */
    protected $auth_signature = null;

    /*
     * the shared memory segment id for the local cache
     */
    protected $cache = null;

    /*
     * internal setting for enabling cache
     */
    protected $use_cache = false;

    /*
     * the last erro message returned by the sockets class
     */
    private $_last_socket_error = '';

    /**
     * Constructor - base constructor for the Resolver and Updater
     *
     * @param mixed $options array of options or null for none
     *
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function __construct(array $options = null)
    {
        //
        // check for the sockets extension
        //
        $this->sockets_enabled = extension_loaded('sockets');

        //
        // load any options that were provided
        //
        if (!empty($options)) {

            foreach ($options as $key => $value) {

                if ($key == 'nameservers') {

                    $this->setServers($value);
                } else {

                    $this->$key = $value;
                }
            }
        }

        //
        // if we're set to use the local shared memory cache, then
        // make sure it's been initialized
        //
        switch($this->cache_type) {
        case 'shared':
            if (extension_loaded('shmop')) {

                $this->cache = new Net_DNS2_Cache_Shm;
                $this->use_cache = true;
            } else {

                throw new Net_DNS2_Exception(
                    'shmop library is not available for cache',
                    Net_DNS2_Lookups::E_CACHE_SHM_UNAVAIL
                );
            }
            break;
        case 'file':

            $this->cache = new Net_DNS2_Cache_File;
            $this->use_cache = true;

            break;
        case 'none':
            $this->use_cache = false;
            break;
        default:

            throw new Net_DNS2_Exception(
                'un-supported cache type: ' . $this->cache_type,
                Net_DNS2_Lookups::E_CACHE_UNSUPPORTED
            );
        }
    }

    /**
     * autoload call-back function; used to auto-load classes
     *
     * @param string $name the name of the class
     *
     * @return void
     * @access public
     *
     */
    static public function autoload($name)
    {
        //
        // only auto-load our classes
        //
        if (strncmp($name, 'Net_DNS2', 8) == 0) {

            include str_replace('_', '/', $name) . '.php';
        }

        return;
    }

    /**
     * sets the name servers to be used
     *
     * @param mixed $nameservers either an array of name servers, or a file name
     *                           to parse, assuming it's in the resolv.conf format
     *
     * @return boolean
     * @throws Net_DNS2_Exception
     * @access public
     *
     */
    public function setServers($nameservers)
    {
        //
        // if it's an array, then use it directly
        //
        // otherwise, see if it's a path to a resolv.conf file and if so, load it
        //
        if (is_array($nameservers)) {

            $this->nameservers = $nameservers;

        } else {

            //
            // check to see if the file is readable
            //
            if (is_readable($nameservers) === true) {

                $data = file_get_contents($nameservers);
                if ($data === false) {
                    throw new Net_DNS2_Exception(
                        'failed to read contents of file: ' . $nameservers,
                        Net_DNS2_Lookups::E_NS_INVALID_FILE
                    );
                }

                $lines = explode("\n", $data);

                foreach ($lines as $line) {

                    $line = trim($line);

                    //
                    // ignore empty lines, and lines that are commented out
                    //
                    if ( (strlen($line) == 0)
                        || ($line[0] == '#')
                        || ($line[0] == ';')
                    ) {
                        continue;
                    }

                    list($key, $value) = preg_split('/\s+/', $line, 2);

                    $key    = trim(strtolower($key));
                    $value  = trim(strtolower($value));

                    switch($key) {
                    case 'nameserver':

                        //
                        // nameserver can be a IPv4 or IPv6 address
                        //
                        if ( (self::isIPv4($value) == true)
                            || (self::isIPv6($value) == true)
                        ) {

                            $this->nameservers[] = $value;
                        } else {

                            throw new Net_DNS2_Exception(
                                'invalid nameserver entry: ' . $value,
                                Net_DNS2_Lookups::E_NS_INVALID_ENTRY
                            );
                        }
                        break;

                    case 'domain':
                        $this->domain = $value;
                        break;

                    case 'search':
                        $this->search_list = preg_split('/\s+/', $value);
                        break;

                    default:
                        ;
                    }
                }

                //
                // if we don't have a domain, but we have a search list, then
                // take the first entry on the search list as the domain
                //
                if ( (strlen($this->domain) == 0)
                    && (count($this->search_list) > 0)
                ) {
                    $this->domain = $this->search_list[0];
                }

            } else {
                throw new Net_DNS2_Exception(
                    'resolver file file provided is not readable: ' . $nameservers,
                    Net_DNS2_Lookups::E_NS_INVALID_FILE
                );
            }
        }

        //
        // check the name servers
        //
        $this->checkServers();

        return true;
    }

    /**
     * checks the list of name servers to make sure they're set
     *
     * @param mixed $default a path to a resolv.conf file or an array of servers.
     *
     * @return boolean
     * @throws Net_DNS2_Exception
     * @access protected
     *
     */
    protected function checkServers($default = null)
    {
        if (empty($this->nameservers)) {

            if (isset($default)) {

                $this->setServers($default);
            } else {

                throw new Net_DNS2_Exception(
                    'empty name servers list; you must provide a list of name '.
                    'servers, or the path to a resolv.conf file.',
                    Net_DNS2_Lookups::E_NS_INVALID_ENTRY
                );
            }
        }

        return true;
    }

    /**
     * adds a TSIG RR object for authentication
     *
     * @param string $keyname   the key name to use for the TSIG RR
     * @param string $signature the key to sign the request.
     * @param string $algorithm the algorithm to use
     *
     * @return boolean
     * @access public
     * @since  function available since release 1.1.0
     *
     */
    public function signTSIG(
        $keyname, $signature = '', $algorithm = Net_DNS2_RR_TSIG::HMAC_MD5
    ) {
        //
        // if the TSIG was pre-created and passed in, then we can just used
        // it as provided.
        //
        if ($keyname instanceof Net_DNS2_RR_TSIG) {

            $this->auth_signature = $keyname;

        } else {

            //
            // otherwise create the TSIG RR, but don't add it just yet; TSIG needs
            // to be added as the last additional entry- so we'll add it just
            // before we send.
            //
            $this->auth_signature = Net_DNS2_RR::fromString(
                strtolower(trim($keyname)) .
                ' TSIG '. $signature
            );

            //
            // set the algorithm to use
            //
            $this->auth_signature->algorithm = $algorithm;
        }

        return true;
    }

    /**
     * adds a SIG RR object for authentication
     *
     * @param string $filename the name of a file to load the signature from.
     *
     * @return boolean
     * @throws Net_DNS2_Exception
     * @access public
     * @since  function available since release 1.1.0
     *
     */
    public function signSIG0($filename)
    {
        //
        // check for OpenSSL
        //
        if (extension_loaded('openssl') === false) {

            throw new Net_DNS2_Exception(
                'the OpenSSL extension is required to use SIG(0).',
                Net_DNS2_Lookups::E_OPENSSL_UNAVAIL
            );
        }

        //
        // if the SIG was pre-created, then use it as-is
        //
        if ($filename instanceof Net_DNS2_RR_SIG) {

            $this->auth_signature = $filename;

        } else {

            //
            // otherwise, it's filename which needs to be parsed and processed.
            //
            $private = new Net_DNS2_PrivateKey($filename);

            //
            // create a new Net_DNS2_RR_SIG object
            //
            $this->auth_signature = new Net_DNS2_RR_SIG();

            //
            // reset some values
            //
            $this->auth_signature->name         = $private->signname;
            $this->auth_signature->ttl          = 0;
            $this->auth_signature->class        = 'ANY';

            //
            // these values are pulled from the private key
            //
            $this->auth_signature->algorithm    = $private->algorithm;
            $this->auth_signature->keytag       = $private->keytag;
            $this->auth_signature->signname     = $private->signname;

            //
            // these values are hard-coded for SIG0
            //
            $this->auth_signature->typecovered  = 'SIG0';
            $this->auth_signature->labels       = 0;
            $this->auth_signature->origttl      = 0;

            //
            // generate the dates
            //
            $t = time();

            $this->auth_signature->sigincep     = gmdate('YmdHis', $t);
            $this->auth_signature->sigexp       = gmdate('YmdHis', $t + 500);

            //
            // store the private key in the SIG object for later.
            //
            $this->auth_signature->private_key  = $private;
        }

        //
        // only RSAMD5 and RSASHA1 are supported for SIG(0)
        //
        switch($this->auth_signature->algorithm) {
        case Net_DNS2_Lookups::DNSSEC_ALGORITHM_RSAMD5:
        case Net_DNS2_Lookups::DNSSEC_ALGORITHM_RSASHA1:
            break;
        default:
            throw new Net_DNS2_Exception(
                'only asymmetric algorithms work with SIG(0)!',
                Net_DNS2_Lookups::E_OPENSSL_INV_ALGO
            );
        }

        return true;
    }

    /**
     * a simple function to determine if the RR type is cacheable
     *
     * @param stream $_type the RR type string
     *
     * @return bool returns true/false if the RR type if cachable
     * @access public
     *
     */
    public function cacheable($_type)
    {
        switch($_type) {
        case 'AXFR':
        case 'OPT':
            return false;
        }

        return true;
    }

    /**
     * PHP doesn't support unsigned integers, but many of the RR's return
     * unsigned values (like SOA), so there is the possibility that the
     * value will overrun on 32bit systems, and you'll end up with a
     * negative value.
     *
     * 64bit systems are not affected, as their PHP_IN_MAX value should
     * be 64bit (ie 9223372036854775807)
     *
     * This function returns a negative integer value, as a string, with
     * the correct unsigned value.
     *
     * @param string $_int the unsigned integer value to check
     *
     * @return string returns the unsigned value as a string.
     * @access public
     *
     */
    public static function expandUint32($_int)
    {
        if ( ($_int < 0) && (PHP_INT_MAX == 2147483647) ) {
            return sprintf('%u', $_int);
        } else {
            return $_int;
        }
    }

    /**
     * returns true/false if the given address is a valid IPv4 address
     *
     * @param string $_address the IPv4 address to check
     *
     * @return boolean returns true/false if the address is IPv4 address
     * @access public
     *
     */
    public static function isIPv4($_address)
    {
        //
        // use filter_var() if it's available; it's faster than preg
        //
        if (extension_loaded('filter') == true) {

            if (filter_var(
                $_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4
            ) == false) {
                return false;
            }
        } else {

            //
            // do the main check here;
            //
            if (inet_pton($_address) === false) {
                return false;
            }

            //
            // then make sure we're not a IPv6 address
            //
            if (preg_match(
                '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',
                $_address
            ) == 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * returns true/false if the given address is a valid IPv6 address
     *
     * @param string $_address the IPv6 address to check
     *
     * @return boolean returns true/false if the address is IPv6 address
     * @access public
     *
     */
    public static function isIPv6($_address)
    {
        //
        // use filter_var() if it's available; it's faster than preg
        //
        if (extension_loaded('filter') == true) {
            if (filter_var(
                $_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6
            ) == false) {
                return false;
            }
        } else {

            //
            // do the main check here
            //
            if (inet_pton($_address) === false) {
                return false;
            }

            //
            // then make sure it doesn't match a IPv4 address
            //
            if (preg_match(
                '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $_address
            ) == 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * formats the given IPv6 address as a fully expanded IPv6 address
     *
     * @param string $_address the IPv6 address to expand
     *
     * @return string the fully expanded IPv6 address
     * @access public
     *
     */
    public static function expandIPv6($_address)
    {
        if (strpos($_address, '::') !== false) {

            $part       = explode('::', $_address);
            $part[0]    = explode(':', $part[0]);
            $part[1]    = explode(':', $part[1]);

            $missing = array();

            $x = (8 - (count($part[0]) + count($part[1])));
            for ($i = 0; $i < $x; $i++) {

                array_push($missing, '0000');
            }

            $missing    = array_merge($part[0], $missing);
            $part       = array_merge($missing, $part[1]);

        } else {

            $part = explode(':', $_address);
        }

        foreach ($part as &$p) {
            while (strlen($p) < 4) {
                $p = '0' . $p;
            }
        }

        unset($p);

        $result = implode(':', $part);

        if (strlen($result) == 39) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * sends a standard Net_DNS2_Packet_Request packet
     *
     * @param Net_DNS2_Packet $request a Net_DNS2_Packet_Request object
     * @param boolean         $use_tcp true/false if the function should
     *                                 use TCP for the request
     *
     * @return mixed returns a Net_DNS2_Packet_Response object, or false on error
     * @throws Net_DNS2_Exception
     * @access protected
     *
     */
    protected function sendPacket(Net_DNS2_Packet $request, $use_tcp)
    {
        //
        // get the data from the packet
        //
        $data = $request->get();
        if (strlen($data) < Net_DNS2_Lookups::DNS_HEADER_SIZE) {

            throw new Net_DNS2_Exception(
                'invalid or empty packet for sending!',
                Net_DNS2_Lookups::E_PACKET_INVALID,
                null,
                $request
            );
        }

        reset($this->nameservers);

        //
        // randomize the name server list if it's asked for
        //
        if ($this->ns_random == true) {

            shuffle($this->nameservers);
        }

        //
        // loop so we can handle server errors
        //
        $response = null;
        $ns = '';
        $socket_type = null;
        $tcp_fallback = false;

        while (1) {

            //
            // grab the next DNS server
            //
            if ($tcp_fallback == false) {

                $ns = (empty($this->nameservers) || !is_array($this->nameservers))
                        ? false : [key($this->nameservers), current($this->nameservers)];
                if ($ns === false) {

                    throw new Net_DNS2_Exception(
                        'every name server provided has failed: ' .
                        $this->_last_socket_error,
                        Net_DNS2_Lookups::E_NS_FAILED
                    );
                }

                $ns = $ns[1];
            }

            //
            // if the use TCP flag (force TCP) is set, or the packet is bigger
            // than 512 bytes, use TCP for sending the packet
            //
            if ( ($use_tcp == true)
                || (strlen($data) > Net_DNS2_Lookups::DNS_MAX_UDP_SIZE)
                || ($tcp_fallback == true)
            ) {
                $tcp_fallback = false;
                $socket_type = Net_DNS2_Socket::SOCK_STREAM;

                //
                // create the socket object
                //
                if ( (!isset($this->sock['tcp'][$ns]))
                    || (!($this->sock['tcp'][$ns] instanceof Net_DNS2_Socket))
                ) {
                    if ($this->sockets_enabled === true) {

                        $this->sock['tcp'][$ns] = new Net_DNS2_Socket_Sockets(
                            Net_DNS2_Socket::SOCK_STREAM,
                            $ns,
                            $this->dns_port,
                            $this->timeout
                        );
                    } else {

                        $this->sock['tcp'][$ns] = new Net_DNS2_Socket_Streams(
                            Net_DNS2_Socket::SOCK_STREAM,
                            $ns,
                            $this->dns_port,
                            $this->timeout
                        );
                    }
                }

                //
                // if a local IP address / port is set, then add it
                //
                if (strlen($this->local_host) > 0) {

                    $this->sock['tcp'][$ns]->bindAddress(
                        $this->local_host, $this->local_port
                    );
                }

                //
                // open it; if it fails, continue in the while loop
                //
                if ($this->sock['tcp'][$ns]->open() === false) {

                    $this->_last_socket_error = $this->sock['tcp'][$ns]->last_error;
                    continue;
                }

                //
                // write the data to the socket; if it fails, continue on
                // the while loop
                //
                if ($this->sock['tcp'][$ns]->write($data) === false) {

                    $this->_last_socket_error = $this->sock['tcp'][$ns]->last_error;
                    continue;
                }

                //
                // read the content, using select to wait for a response
                //
                $size = 0;
                $result = null;

                //
                // handle zone transfer requests differently than other requests.
                //
                if ($request->question[0]->qtype == 'AXFR') {

                    $soa_count = 0;

                    while (1) {

                        //
                        // read the data off the socket
                        //
                        $result = $this->sock['tcp'][$ns]->read($size);
                        if ( ($result === false)
                            ||  ($size < Net_DNS2_Lookups::DNS_HEADER_SIZE)
                        ) {
                            $this->_last_socket_error = $this->sock['tcp'][$ns]->last_error;
                            break;
                        }

                        //
                        // parse the first chunk as a packet
                        //
                        $chunk = new Net_DNS2_Packet_Response($result, $size);

                        //
                        // if this is the first packet, then clone it directly, then
                        // go through it to see if there are two SOA records
                        // (indicating that it's the only packet)
                        //
                        if (is_null($response) == true) {

                            $response = clone $chunk;

                            //
                            // look for a failed response; if the zone transfer
                            // failed, then we don't need to do anything else at this
                            // point, and we should just break out.
                            //
                            if ($response->header->rcode != Net_DNS2_Lookups::RCODE_NOERROR) {
                                break;
                            }

                            //
                            // go through each answer
                            //
                            foreach ($response->answer as $index => $rr) {

                                //
                                // count the SOA records
                                //
                                if ($rr->type == 'SOA') {
                                    $soa_count++;
                                }
                            }

                            //
                            // if we have 2 or more SOA records, then we're done;
                            // otherwise continue out so we read the rest of the
                            // packets off the socket
                            //
                            if ($soa_count >= 2) {
                                break;
                            } else {
                                continue;
                            }

                        } else {

                            //
                            // go through all these answers, and look for SOA records
                            //
                            foreach ($chunk->answer as $index => $rr) {

                                //
                                // count the number of SOA records we find
                                //
                                if ($rr->type == 'SOA') {
                                    $soa_count++;
                                }

                                //
                                // add the records to a single response object
                                //
                                $response->answer[] = $rr;
                            }

                            //
                            // if we've found the second SOA record, we're done
                            //
                            if ($soa_count >= 2) {
                                break;
                            }
                        }
                    }

                } else {

                    $result = $this->sock['tcp'][$ns]->read($size);
                    if ( ($result === false)
                        ||  ($size < Net_DNS2_Lookups::DNS_HEADER_SIZE)
                    ) {
                        $this->_last_socket_error = $this->sock['tcp'][$ns]->last_error;
                        continue;
                    }

                    //
                    // create the packet object
                    //
                    $response = new Net_DNS2_Packet_Response($result, $size);
                }

                break;

            } else {

                $socket_type = Net_DNS2_Socket::SOCK_DGRAM;

                //
                // create the socket object
                //
                if ( (!isset($this->sock['udp'][$ns]))
                    || (!($this->sock['udp'][$ns] instanceof Net_DNS2_Socket))
                ) {
                    if ($this->sockets_enabled === true) {

                        $this->sock['udp'][$ns] = new Net_DNS2_Socket_Sockets(
                            Net_DNS2_Socket::SOCK_DGRAM, $ns, $this->dns_port, $this->timeout
                        );
                    } else {

                        $this->sock['udp'][$ns] = new Net_DNS2_Socket_Streams(
                            Net_DNS2_Socket::SOCK_DGRAM, $ns, $this->dns_port, $this->timeout
                        );
                    }
                }

                //
                // if a local IP address / port is set, then add it
                //
                if (strlen($this->local_host) > 0) {

                    $this->sock['udp'][$ns]->bindAddress(
                        $this->local_host, $this->local_port
                    );
                }

                //
                // open it
                //
                if ($this->sock['udp'][$ns]->open() === false) {

                    $this->_last_socket_error = $this->sock['udp'][$ns]->last_error;
                    continue;
                }

                //
                // write the data to the socket
                //
                if ($this->sock['udp'][$ns]->write($data) === false) {

                    $this->_last_socket_error = $this->sock['udp'][$ns]->last_error;
                    continue;
                }

                //
                // read the content, using select to wait for a response
                //
                $size = 0;

                $result = $this->sock['udp'][$ns]->read($size);
                if (( $result === false)
                    || ($size < Net_DNS2_Lookups::DNS_HEADER_SIZE)
                ) {
                    $this->_last_socket_error = $this->sock['udp'][$ns]->last_error;
                    continue;
                }

                //
                // create the packet object
                //
                $response = new Net_DNS2_Packet_Response($result, $size);
                if (is_null($response)) {

                    throw new Net_DNS2_Exception(
                        'empty response object',
                        Net_DNS2_Lookups::E_NS_FAILED,
                        null,
                        $request
                    );
                }

                //
                // check the packet header for a trucated bit; if it was truncated,
                // then re-send the request as TCP.
                //
                if ($response->header->tc == 1) {

                    $tcp_fallback = true;
                    continue;
                }

                break;
            }
        }

        //
        // if $response is null, then we didn't even try once; which shouldn't
        // actually ever happen
        //
        if (is_null($response)) {

            throw new Net_DNS2_Exception(
                'empty response object',
                Net_DNS2_Lookups::E_NS_FAILED,
                null,
                $request
            );
        }

        //
        // add the name server that the response came from to the response object,
        // and the socket type that was used.
        //
        $response->answer_from = $ns;
        $response->answer_socket_type = $socket_type;

        //
        // make sure header id's match between the request and response
        //
        if ($request->header->id != $response->header->id) {

            throw new Net_DNS2_Exception(
                'invalid header: the request and response id do not match.',
                Net_DNS2_Lookups::E_HEADER_INVALID,
                null,
                $request,
                $response
            );
        }

        //
        // make sure the response is actually a response
        //
        // 0 = query, 1 = response
        //
        if ($response->header->qr != Net_DNS2_Lookups::QR_RESPONSE) {

            throw new Net_DNS2_Exception(
                'invalid header: the response provided is not a response packet.',
                Net_DNS2_Lookups::E_HEADER_INVALID,
                null,
                $request,
                $response
            );
        }

        //
        // make sure the response code in the header is ok
        //
        if ($response->header->rcode != Net_DNS2_Lookups::RCODE_NOERROR) {

            throw new Net_DNS2_Exception(
                'DNS request failed: ' .
                Net_DNS2_Lookups::$result_code_messages[$response->header->rcode],
                $response->header->rcode,
                null,
                $request,
                $response
            );
        }

        return $response;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
