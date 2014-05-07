<?php
/*********************************************************************
    class.tnefparse.php

    Parser library and data objects for Microsoft TNEF (Transport Neutral
    Encapsulation Format) encoded email attachments.

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2014 osTicket
    http://www.osticket.com

    This algorithm based on a similar project; however the original code did
    not process the HTML body of the message, nor did it properly handle the
    Microsoft Unicode encoding found in the attributes.

     * The Horde's class allows MS-TNEF data to be displayed.
     *
     * The TNEF rendering is based on code by:
     *   Graham Norbury <gnorbury@bondcar.com>
     * Original design by:
     *   Thomas Boll <tb@boll.ch>, Mark Simpson <damned@world.std.com>
     *
     * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
     *
     * See the enclosed file COPYING for license information (LGPL). If you
     * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
     *
     * @author  Jan Schneider <jan@horde.org>
     * @author  Michael Slusarz <slusarz@horde.org>
     * @package Horde_Compress

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class TnefException extends Exception {}

/**
 * References:
 * http://download.microsoft.com/download/1/D/0/1D0C13E1-2961-4170-874E-FADD796200D9/%5BMS-OXTNEF%5D.pdf
 * http://msdn.microsoft.com/en-us/library/ee160597(v=exchg.80).aspx
 * http://sourceforge.net/apps/trac/mingw-w64/browser/trunk/mingw-w64-headers/include/tnef.h?rev=3952
 */
class TnefStreamReader implements Iterator {
    const SIGNATURE = 0x223e9f78;

    var $pos = 0;
    var $length = 0;
    var $streams = array();
    var $current = true;

    var $options = array(
        'checksum' => true,
    );

    function __construct($stream, $options=array()) {
        if (is_array($options))
            $this->options += $options;

        $this->push($stream);

        // Read header
        if (self::SIGNATURE != $this->_geti(32))
            throw new TnefException("Invalid signature");

        $this->_geti(16); // Attach key

        $this->next(); // Process first block
    }

    protected function push(&$stream) {
        $this->streams[] = array($this->stream, $this->pos, $this->length);
        $this->stream = &$stream;
        $this->pos = 0;
        $this->length = strlen($stream);
    }

    protected function pop() {
        list($this->stream, $this->pos, $this->length) = array_pop($this->streams);
    }

    protected function _geti($bits) {
        $bytes = $bits / 8;

        switch($bytes) {
        case 1:
            $value = ord($this->stream[$this->pos]);
            break;
        case 2:
            $value = unpack('vval', substr($this->stream, $this->pos, 2));
            $value = $value['val'];
            break;
        case 4:
            $value = unpack('Vval', substr($this->stream, $this->pos, 4));
            $value = $value['val'];
            break;
        }
        $this->pos += $bytes;
        return $value;
    }

    protected function _getx($bytes) {
        $value = substr($this->stream, $this->pos, $bytes);
        $this->pos += $bytes;

        return $value;
    }

    function check($block) {
        $sum = 0; $bytes = strlen($block['data']); $bs = 1024;
        for ($i=0; $i < $bytes; $i+=$bs) {
            $b = unpack('C*', substr($block['data'], $i, min($bs, $bytes-$i)));
            $sum += array_sum($b);
            $sum = $sum % 65536;
        }
        if ($block['checksum'] != $sum)
            throw new TnefException('Corrupted block. Invalid checksum');
    }

    function next() {
        if ($this->length - $this->pos < 11) {
            $this->current = false;
            return;
        }

        $this->current = array(
            'level' => $this->_geti(8),
            'type' => $this->_geti(32),
            'length' => $length = $this->_geti(32),
            'data' => $this->_getx($length),
            'checksum' => $this->_geti(16)
        );

        if ($this->options['checksum'])
            $this->check($this->current);
    }

    function current() {
        return $this->current;
    }

    function key() {
        return $this->current['type'];
    }

    function valid() {
        return (bool) $this->current;
    }

    function rewind() {
        // Skip signature and attach-key
        $this->pos = 6;
    }
}

/**
 * References:
 * http://msdn.microsoft.com/en-us/library/ee179447(v=exchg.80).aspx
 */
class TnefAttribute {
    // Encapsulated Attributes
    const AlternateRecipientAllowed = 0x0002;
    const AutoForwarded = 0x0005;
    const Importance = 0x0017;
    const MessageClass = 0x001a;
    const OriginatorDeliveryReportRequested = 0x0023;
    const Priority =  0x0026;
    const ReadReceiptRequested = 0x0029;
    const Sensitivity = 0x0036;
    const ClientSubmitTime = 0x0039;
    const ReceivedByEntryId = 0x003f;
    const ReceivedByName = 0x0040;
    const ReceivedRepresentingEntryId = 0x0043;
    const RecevedRepresentingName = 0x0044;
    const MessageSubmissionId = 0x0047;
    const ReceivedBySearchKey = 0x0051;
    const ReceivedRepresentingSearchKey = 0x0052;
    const MessageToMe = 0x0057;
    const MessgeCcMe = 0x0058;
    const ConversionTopic = 0x0070;
    const ConversationIndex = 0x0071;
    const ReceivedByAddressType = 0x0075;
    const ReceivedByEmailAddress = 0x0076;
    const TnefCorrelationKey = 0x007f;
    const SenderName = 0x0c1a;
    const HasAttachments = 0x0e1b;
    const NormalizedSubject = 0x0e1d;
    const AttachSize = 0x0e20;
    const AttachNumber = 0x0e21;
    const Body = 0x1000;
    const RtfSyncBodyCrc = 0x1006;
    const RtfSyncBodyCount = 0x1007;
    const RtfSyncBodyTag = 0x1008;
    const RtfCompressed = 0x1009;
    const RtfSyncPrefixCount = 0x1010;
    const RtfSyncTrailingCount = 0x1011;
    const BodyHtml = 0x1013;
    const BodyContentId = 0x1014;
    const NativeBody = 0x1016;
    const InternetMessageId = 0x1035;
    const IconIndex = 0x1080;
    const ImapCachedMsgsize = 0x10f0;
    const UrlCompName = 0x10f3;
    const AttributeHidden = 0x10f4;
    const AttributeSystem = 0x10f5;
    const AttributeReadOnly = 0x10f6;
    const CreationTime = 0x3007;
    const LastModificationTime = 0x3008;
    const AttachDataBinary = 0x3701;
    const AttachEncoding = 0x3702;
    const AttachExtension = 0x3703;
    const AttachFilename = 0x3704;
    const AttachLongFilename = 0x3707;
    const AttachPathname = 0x3708;
    const AttachTransportName = 0x370c;
    const AttachMimeTag = 0x370e;    # Mime content-type
    const AttachContentId = 0x3712;
    const AttachmentCharset = 0x371b;
    const InternetCodepage = 0x3fde;
    const MessageLocaleId = 0x3ff1;
    const CreatorName = 0x3ff8;
    const CreatorEntryId = 0x3ff9;
    const LastModifierName = 0x3ffa;
    const LastModifierEntryId = 0x3ffb;
    const MessageCodepage = 0x3ffd;
    const SenderFlags = 0x4019;
    const SentRepresentingFlags = 0x401a;
    const ReceivedByFlags = 0x401b;
    const ReceivedRepresentingFlags = 0x401c;
    const SenderSimpleDisplayName = 0x4030;
    const SentRepresentingSimpleDisplayName = 0x4031;
    # NOTE: The M$ specification gives ambiguous values for this property
    const ReceivedRepresentingSimpleDisplayName = 0x4034;
    const CreatorSimpleDisplayName = 0x4038;
    const LastModifierSimpleDisplayName = 0x4039;
    const ContentFilterSpamConfidenceLevel = 0x4076;
    const MessageEditorFormat = 0x5909;

    static function getName($code) {
        static $prop_codes = false;
        if (!$prop_codes) {
            $R = new ReflectionClass(get_class());
            $prop_codes = array_flip($R->getConstants());
        }
        return $prop_codes[$code];
    }
}

class TnefAttributeStreamReader extends TnefStreamReader {
    var $count = 0;

    const TypeUnspecified = 0x0;
    const TypeNull = 0x0001;
    const TypeInt16 = 0x0002;
    const TypeInt32 = 0x0003;
    const TypeFlt32 = 0x0004;
    const TypeFlt64 = 0x0005;
    const TypeCurency = 0x0006;
    const TypeAppTime = 0x0007;
    const TypeError = 0x000a;
    const TypeBoolean = 0x000b;
    const TypeObject = 0x000d;
    const TypeInt64 = 0x0014;
    const TypeString8 = 0x001e;
    const TypeUnicode = 0x001f;
    const TypeSystime = 0x0040;
    const TypeCLSID = 0x0048;
    const TypeBinary = 0x0102;

    const MAPI_NAMED_TYPE_ID = 0x0000;
    const MAPI_NAMED_TYPE_STRING = 0x0001;
    const MAPI_MV_FLAG = 0x1000;

    function __construct($stream) {
        $this->push($stream);
        /* Number of attributes. */
        $this->count = $this->_geti(32);
    }

    function valid() {
        return $this->count && $this->current;
    }

    function rewind() {
        $this->pos = 4;
    }

    protected function readPhpValue($type) {
        switch ($type) {
        case self::TypeUnspecified:
        case self::TypeNull:
        case self::TypeError:
            return null;

        case self::TypeInt16:
            return $this->_geti(16);

        case self::TypeInt32:
            return $this->_geti(32);

        case self::TypeBoolean:
            return (bool) $this->_geti(32);

        case self::TypeFlt32:
            list($f) = unpack('f', $this->_getx(8));
            return $f;

        case self::TypeFlt64:
            list($d) = unpack('d', $this->_getx(8));
            return $d;

        case self::TypeAppTime:
        case self::TypeCurency:
        case self::TypeInt64:
            return $this->_getx(8);

        case self::TypeSystime:
            $a = unpack('Vl/Vh', $this->_getx(8));
            // return FileTimeToU64(f) / 10000000 - 11644473600
            $ft = ($a['l'] / 10000000.0) + ($a['h'] * 429.4967296);
            return $ft - 11644473600;

        case self::TypeString8:
        case self::TypeUnicode:
        case self::TypeBinary:
        case self::TypeObject:
            $length = $this->_geti(32);

            /* Pad to next 4 byte boundary. */
            $datalen = $length + ((4 - ($length % 4)) % 4);

            // Chomp null terminator
            if ($type == self::TypeString8)
                --$length;
            elseif ($type == self::TypeUnicode)
                $length -= 2;

            /* Read and truncate to length. */
            $text = substr($this->_getx($datalen), 0, $length);
            if ($type == self::TypeUnicode) {
                $text = Format::encode($text, 'ucs2');
            }

            return $text;
        }
    }

    function next() {
        if ($this->count <= 0) {
            return $this->current = false;
        }

        $this->count--;

        $have_mval = false;
        $named_id = $value = null;
        $attr_type = $this->_geti(16);
        $attr_name = $this->_geti(16);
        $data_type = $attr_type & ~self::MAPI_MV_FLAG;

        if (($attr_type & self::MAPI_MV_FLAG) != 0
            // These are a "special case of multi-value attributes with
            // num_values=1
            || in_array($attr_type, array(
                self::TypeUnicode, self::TypeString8, self::TypeBinary,
                self::TypeObject))
        ) {
            $have_mval = true;
        }

        if (($attr_name >= 0x8000) && ($attr_name < 0xFFFE)) {
            $this->_getx(16);
            $named_type = $this->_geti(32);

            switch ($named_type) {
            case self::MAPI_NAMED_TYPE_ID:
                $named_id = $this->_geti(32);
                break;

            case self::MAPI_NAMED_TYPE_STRING:
                $attr_name = 0x9999;
                $named_id = $this->readPhpValue(self::TypeUnicode);
                break;
            }
        }

        if (!$have_mval) {
            $value = $this->readPhpValue($data_type);
        } else {
            $value = array();
            $k = $this->_geti(32);
            for ($i=0; $i < $k; $i++)
                $value[] = $this->readPhpValue($data_type);
        }

        if (is_array($value) && ($attr_type & self::MAPI_MV_FLAG) == 0)
            $value = $value[0];

        $this->current = array(
            'type' => $attr_type,
            'name' => $attr_name,
            'named_id' => $named_id,
            'value' => $value,
        );
    }

    function key() {
        return $this->current['name'];
    }
}

class TnefStreamParser {
    const LVL_MESSAGE = 0x01;
    const LVL_ATTACHMENT = 0x02;

    const attTnefVersion = 0x89006;
    const attAttachData = 0x6800f;
    const attAttachTransportFilename = 0x18010;
    const attAttachRendData = 0x69002;
    const attAttachment = 0x69005;
    const attMsgProps = 0x69003;
    const attRecipTable = 0x69004;
    const attOemCodepage = 0x69007;

    // Message-level attributes
    const idMessageClass = 0x78008;
    const idSenderEntryId = 0x8000;         # From
    const idSubject = 0x18004;              # Subject
    const idClientSubmitTime = 0x38004;
    const idMessageDeliveryTime = 0x38005;
    const idMessageStatus = 0x68007;
    const idMessageID = 0x18009;            # Message-Id
    const idConversationID = 0x1800b;
    const idBody = 0x2800c;                 # Body
    const idImportance = 0x4800d;           # Priority
    const idLastModificationTime = 0x38020;
    const idOriginalMessageClass = 0x70600;
    const idReceivedRepresentingEmailAddress = 0x60000;
    const idSentRepresentingEmailAddress = 0x60001;
    const idStartDate = 0x030006;
    const idEndDate = 0x30007;
    const idOwnerAppointmentId = 0x50008;
    const idResponseRequested = 0x40009;

    function __construct($stream) {
        $this->stream = new TnefStreamReader($stream);
    }

    function getMessage() {
        $msg = new TnefMessage();
        foreach ($this->stream as $type=>$info) {
            switch($type) {
            case self::attTnefVersion:
                // Ignored (for now)
                break;

            case self::attOemCodepage:
                $cp = unpack("Vpri/Vsec", $info['data']);
                $msg->_set('OemCodepage', $cp['pri']);
                break;

            // Message level attributes
            case self::idMessageClass:
                $msg->_set('MessageClass', $info['data']);
                break;
            case self::idMessageID:
                $msg->_set('MessageId', $info['data']);
                break;

            case self::attMsgProps:
                // Message properties (includig body)
                $msg->_setProperties(
                    new TnefAttributeStreamReader($info['data']));
                break;

            // Attachments
            case self::attAttachRendData:
                // Marks the start of an attachment
                $attach = $msg->pushAttachment();
                //$attach->_setRenderingData();
                break;
            case self::attAttachment:
                $attach->_setProperties(
                    new TnefAttributeStreamReader($info['data']));
                break;
            case self::attAttachTransportFilename:
                $attach->_setFilename(rtrim($info['data'], "\x00"));
                break;
            case self::attAttachData:
                $attach->_setData($info['data']);
                $attach->_setDataSize($info['length']);
                break;
            }
        }
        return $msg;
    }
}

abstract class AbstractTnefObject {
    function _setProperties($propReader) {
        foreach ($propReader as $prop=>$info) {
            if ($tag = TnefAttribute::getName($prop))
                $this->{$tag} = $info['value'];
            elseif ($prop == 0x9999)
                // Extended, "named" attribute
                $this->{$info['named_id']} = $info['value'];
        }
    }

    function _set($prop, $value) {
        $this->{$prop} = $value;
    }
}

class TnefMessage extends AbstractTnefObject {
    var $attachments = array();

    function pushAttachment() {
        $new = new TnefAttachment();
        $this->attachments[] = $new;
        return $new;
    }

    function getBody($type='text/html', $encoding=false) {
        // First select the body
        switch ($type) {
        case 'text/html':
            $body = $this->BodyHtml;
            break;
        default:
            return false;
        }

        // Figure out the source encoding (§5.1.2)
        $charset = false;
        if (@$this->OemCodepage)
            $charset = 'cp'.$this->OemCodepage;
        elseif (@$this->InternetCodepage)
            $charset = 'cp'.$this->InternetCodepage;

        // Transcode it
        if ($encoding && $charset)
            $body = Format::encode($body, $charset, $encoding);

        return $body;
    }
}

class TnefAttachment extends AbstractTnefObject {
    function _setFilename($data) {
        $this->TransportFilename = $data;
    }

    function _setData($data) {
        $this->Data = $data;
    }

    function _setDataSize($size) {
        $this->DataSize = $size;
    }

    function getData() {
        if (isset($this->Data))
            return $this->Data;
        elseif (isset($this->AttachDataBinary))
            return $this->AttachDataBinary;
    }

    function _setRenderingData($data) {
        // Pass
    }

    function getType() {
        return $this->AttachMimeTag;
    }

    function getName() {
        if (isset($this->AttachLongFilename))
            return basename($this->AttachLongFilename);
        elseif (isset($this->AttachFilename))
            return $this->AttachFilename;
        elseif (isset($this->AttachTransportName))
            return $this->AttachTransportName;
        else
            return $this->TransportFilename;
    }
}
