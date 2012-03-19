<?php
//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Chuck Hagenbuch <chuck@horde.org>                            |
// +----------------------------------------------------------------------+
//
// $Id: mock.php,v 1.1 2007/12/08 17:57:54 chagenbu Exp $
//

/**
 * Mock implementation of the PEAR Mail:: interface for testing.
 * @access public
 * @package Mail
 * @version $Revision: 1.1 $
 */
class Mail_mock extends Mail {

    /**
     * Array of messages that have been sent with the mock.
     *
     * @var array
     * @access public
     */
    var $sentMessages = array();

    /**
     * Callback before sending mail.
     *
     * @var callback
     */
    var $_preSendCallback;

    /**
     * Callback after sending mai.
     *
     * @var callback
     */
    var $_postSendCallback;

    /**
     * Constructor.
     *
     * Instantiates a new Mail_mock:: object based on the parameters
     * passed in. It looks for the following parameters, both optional:
     *     preSendCallback   Called before an email would be sent.
     *     postSendCallback  Called after an email would have been sent.
     *
     * @param array Hash containing any parameters.
     * @access public
     */
    function Mail_mock($params)
    {
        if (isset($params['preSendCallback']) &&
            is_callable($params['preSendCallback'])) {
            $this->_preSendCallback = $params['preSendCallback'];
        }

        if (isset($params['postSendCallback']) &&
            is_callable($params['postSendCallback'])) {
            $this->_postSendCallback = $params['postSendCallback'];
        }
    }

    /**
     * Implements Mail_mock::send() function. Silently discards all
     * mail.
     *
     * @param mixed $recipients Either a comma-seperated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     *
     * @param array $headers The array of headers to send with the mail, in an
     *              associative array, where the array key is the
     *              header name (ie, 'Subject'), and the array value
     *              is the header value (ie, 'test'). The header
     *              produced from those values would be 'Subject:
     *              test'.
     *
     * @param string $body The full text of the message body, including any
     *               Mime parts, etc.
     *
     * @return mixed Returns true on success, or a PEAR_Error
     *               containing a descriptive error message on
     *               failure.
     * @access public
     */
    function send($recipients, $headers, $body)
    {
        if ($this->_preSendCallback) {
            call_user_func_array($this->_preSendCallback,
                                 array(&$this, $recipients, $headers, $body));
        }

        $entry = array('recipients' => $recipients, 'headers' => $headers, 'body' => $body);
        $this->sentMessages[] = $entry;

        if ($this->_postSendCallback) {
            call_user_func_array($this->_postSendCallback,
                                 array(&$this, $recipients, $headers, $body));
        }

        return true;
    }

}
