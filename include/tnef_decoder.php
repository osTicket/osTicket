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
    const AttachEncoding = 0x3702;
    const AttachExtension = 0x3703;
    const AttachFilename = 0x3704;
    const AttachLongFilename = 0x3707;
    const AttachPathname = 0x3708;
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
    const ContentFilterSpmnConfidenceLevel = 0x4076;
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
        $this->next();
    }

    function valid() {
        return (bool) $this->current;
    }

    function rewind() {
        $this->pos = 0;
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
        $this->count--;

        if ($this->length - $this->pos < 12)
            return $this->current = false;

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
                $attach->_setFilename($info['data']);
                break;
            case self::attAttachData:
                $attach->_setData($info['data']);
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
            if ($body = @$this->BodyHtml)
                break;
            if ($c = $this->RtfCompressed) {
                $codec = new RtfCodec();
                $body = $codec->decompress($c);
                // TODO: Convert to HTML or plain text
            }
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
        else
            return $this->TransportFilename;
    }
}

/**
 * RTF compression codec. Currently only decompression is implemented
 *
 * References:
 * http://msdn.microsoft.com/en-us/library/cc463890(v=exchg.80).aspx
 */
class RtfCodec {
    var $dictionary = '';

    var $crc_dictionary = array(
        0x0, 0x77073096, 0xEE0E612C, 0x990951BA, 0x76DC419, 0x706AF48F,
        0xE963A535, 0x9E6495A3, 0xEDB8832, 0x79DCB8A4, 0xE0D5E91E, 0x97D2D988,
        0x9B64C2B, 0x7EB17CBD, 0xE7B82D07, 0x90BF1D91, 0x1DB71064, 0x6AB020F2,
        0xF3B97148, 0x84BE41DE, 0x1ADAD47D, 0x6DDDE4EB, 0xF4D4B551, 0x83D385C7,
        0x136C9856, 0x646BA8C0, 0xFD62F97A, 0x8A65C9EC, 0x14015C4F, 0x63066CD9,
        0xFA0F3D63, 0x8D080DF5, 0x3B6E20C8, 0x4C69105E, 0xD56041E4, 0xA2677172,
        0x3C03E4D1, 0x4B04D447, 0xD20D85FD, 0xA50AB56B, 0x35B5A8FA, 0x42B2986C,
        0xDBBBC9D6, 0xACBCF940, 0x32D86CE3, 0x45DF5C75, 0xDCD60DCF, 0xABD13D59,
        0x26D930AC, 0x51DE003A, 0xC8D75180, 0xBFD06116, 0x21B4F4B5, 0x56B3C423,
        0xCFBA9599, 0xB8BDA50F, 0x2802B89E, 0x5F058808, 0xC60CD9B2, 0xB10BE924,
        0x2F6F7C87, 0x58684C11, 0xC1611DAB, 0xB6662D3D, 0x76DC4190, 0x1DB7106,
        0x98D220BC, 0xEFD5102A, 0x71B18589, 0x6B6B51F, 0x9FBFE4A5, 0xE8B8D433,
        0x7807C9A2, 0xF00F934, 0x9609A88E, 0xE10E9818, 0x7F6A0DBB, 0x86D3D2D,
        0x91646C97, 0xE6635C01, 0x6B6B51F4, 0x1C6C6162, 0x856530D8, 0xF262004E,
        0x6C0695ED, 0x1B01A57B, 0x8208F4C1, 0xF50FC457, 0x65B0D9C6, 0x12B7E950,
        0x8BBEB8EA, 0xFCB9887C, 0x62DD1DDF, 0x15DA2D49, 0x8CD37CF3, 0xFBD44C65,
        0x4DB26158, 0x3AB551CE, 0xA3BC0074, 0xD4BB30E2, 0x4ADFA541, 0x3DD895D7,
        0xA4D1C46D, 0xD3D6F4FB, 0x4369E96A, 0x346ED9FC, 0xAD678846, 0xDA60B8D0,
        0x44042D73, 0x33031DE5, 0xAA0A4C5F, 0xDD0D7CC9, 0x5005713C, 0x270241AA,
        0xBE0B1010, 0xC90C2086, 0x5768B525, 0x206F85B3, 0xB966D409, 0xCE61E49F,
        0x5EDEF90E, 0x29D9C998, 0xB0D09822, 0xC7D7A8B4, 0x59B33D17, 0x2EB40D81,
        0xB7BD5C3B, 0xC0BA6CAD, 0xEDB88320, 0x9ABFB3B6, 0x3B6E20C, 0x74B1D29A,
        0xEAD54739, 0x9DD277AF, 0x4DB2615, 0x73DC1683, 0xE3630B12, 0x94643B84,
        0xD6D6A3E, 0x7A6A5AA8, 0xE40ECF0B, 0x9309FF9D, 0xA00AE27, 0x7D079EB1,
        0xF00F9344, 0x8708A3D2, 0x1E01F268, 0x6906C2FE, 0xF762575D, 0x806567CB,
        0x196C3671, 0x6E6B06E7, 0xFED41B76, 0x89D32BE0, 0x10DA7A5A, 0x67DD4ACC,
        0xF9B9DF6F, 0x8EBEEFF9, 0x17B7BE43, 0x60B08ED5, 0xD6D6A3E8, 0xA1D1937E,
        0x38D8C2C4, 0x4FDFF252, 0xD1BB67F1, 0xA6BC5767, 0x3FB506DD, 0x48B2364B,
        0xD80D2BDA, 0xAF0A1B4C, 0x36034AF6, 0x41047A60, 0xDF60EFC3, 0xA867DF55,
        0x316E8EEF, 0x4669BE79, 0xCB61B38C, 0xBC66831A, 0x256FD2A0, 0x5268E236,
        0xCC0C7795, 0xBB0B4703, 0x220216B9, 0x5505262F, 0xC5BA3BBE, 0xB2BD0B28,
        0x2BB45A92, 0x5CB36A04, 0xC2D7FFA7, 0xB5D0CF31, 0x2CD99E8B, 0x5BDEAE1D,
        0x9B64C2B0, 0xEC63F226, 0x756AA39C, 0x26D930A, 0x9C0906A9, 0xEB0E363F,
        0x72076785, 0x5005713, 0x95BF4A82, 0xE2B87A14, 0x7BB12BAE, 0xCB61B38,
        0x92D28E9B, 0xE5D5BE0D, 0x7CDCEFB7, 0xBDBDF21, 0x86D3D2D4, 0xF1D4E242,
        0x68DDB3F8, 0x1FDA836E, 0x81BE16CD, 0xF6B9265B, 0x6FB077E1, 0x18B74777,
        0x88085AE6, 0xFF0F6A70, 0x66063BCA, 0x11010B5C, 0x8F659EFF, 0xF862AE69,
        0x616BFFD3, 0x166CCF45, 0xA00AE278, 0xD70DD2EE, 0x4E048354, 0x3903B3C2,
        0xA7672661, 0xD06016F7, 0x4969474D, 0x3E6E77DB, 0xAED16A4A, 0xD9D65ADC,
        0x40DF0B66, 0x37D83BF0, 0xA9BCAE53, 0xDEBB9EC5, 0x47B2CF7F, 0x30B5FFE9,
        0xBDBDF21C, 0xCABAC28A, 0x53B39330, 0x24B4A3A6, 0xBAD03605, 0xCDD70693,
        0x54DE5729, 0x23D967BF, 0xB3667A2E, 0xC4614AB8, 0x5D681B02, 0x2A6F2B94,
        0xB40BBE37, 0xC30C8EA1, 0x5A05DF1B, 0x2D02EF8D);

    const COMPRESSED = 0x75465a4c;
    const UNCOMPRESSED = 0x414c454d;

    function __construct() {
        $this->dictionary =
            '{\rtf1\ansi\mac\deff0\deftab720{\fonttbl;}'
           .'{\f0\fnil \froman \fswiss \fmodern \fscript '
           .'\fdecor MS Sans SerifSymbolArialTimes New RomanCourier{\colortbl\red0\green0\blue0'
           ."\r\n"
           .'\par \pard\plain\f0\fs20\b\i\u\tab\tx';
    }

    function getHeader($what) {
        $header = unpack("Vcompsize/Vrawsize/Vcomptype/Vcrc", $what);
        return $header;
    }

    /**
     * Uncompress a compressed RTF stream. Returns the RTF data
     * uncompressed.
     *
     * Throws:
     * RtfException - if the CRC indicates corrupted input
     * RtfException - if the compression method is unsupported
     */
    function decompress($what) {
        $header = self::getHeader($what);
        switch ($header['comptype']) {
        case self::UNCOMPRESSED:
            return $what;
        case self::COMPRESSED:
            // This is what the rest of the function does
            break;
        default:
            throw new RtfException('Unexpected RTF compression type');
        }

        // TODO: Compute header CRC
        $output = '';
        $dict = $this->dictionary;
        $woffset = $il = strlen($dict);
        $rawsize = $header['rawsize'];
        for ($i = 16, $k = strlen($what); $i < $k;) {
            # Convert CONTROL byte to list of bits (LSB first)
            $control = str_split(str_pad(decbin(ord($what[$i++])), 8, '0', STR_PAD_LEFT));
            foreach (array_reverse($control) as $flag) {
                if ($flag == '0') {
                    $char = $what[$i];
                    $output .= $char;
                    $dict[$woffset] = $char;
                    $woffset = ($woffset + 1) % 0x1000;
                    $i++;
                }
                else {
                    list(,$idx) = unpack('n', substr($what, $i, 2));
                    $upper = ($idx & 0xfff0) >> 4;
                    $bytes = ($idx & 0xf) + 1;
                    if ($upper == $woffset)
                        return $output;
                    foreach (range($upper, $upper+$bytes) as $m) {
                        $rpos = $m % 0x1000;
                        if (strlen($output) == $rawsize)
                            return $output;
                        $output .= $dict[$rpos];
                        $dict[$woffset] = $dict[$rpos];
                        $woffset = ($woffset + 1) % 0x1000;
                    }
                    $i+=2;
                }
            }
        }
    }
}

class RtfException extends Exception {}
