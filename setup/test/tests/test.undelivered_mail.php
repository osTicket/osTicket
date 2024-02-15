<?php

require_once 'mockdb.php';

require_once INCLUDE_DIR.'class.validator.php';
require_once INCLUDE_DIR.'class.auth.php';
require_once INCLUDE_DIR.'class.staff.php';
require_once INCLUDE_DIR.'class.email.php';
require_once INCLUDE_DIR.'class.format.php';
require_once INCLUDE_DIR.'class.thread.php';

class TestUndeliveredMailParsing extends Test {
    var $name = "Mail parsing Undelivered Mail";

    function testRecipients() {
        db_connect(new MockDbSource());
        $email = <<<EOF
Return-Path: <>
Delivered-To: test@example.com
Received: by mail.example.com (Postcow)
Date: Fri,  2 Feb 2024 01:10:46 +0000 (UTC)
From: MAILER-DAEMON@mail.example.com (Mail Delivery System)
Subject: Undelivered Mail Returned to Sender
To: test@example.com
Auto-Submitted: auto-replied
MIME-Version: 1.0
Content-Type: multipart/report; report-type=delivery-status;
    boundary="CA4EC21BFE80.1706836246/mail.example.com"
Message-Id: <20240202011046.E8F6021BFF86@mail.example.com>

This is a MIME-encapsulated message.

--CA4EC21BFE80.1706836246/mail.example.com
Content-Description: Notification
Content-Type: text/plain; charset=us-ascii

This is the mail system at host mail.example.com.

I'm sorry to have to inform you that your message could not
be delivered to one or more recipients. It's attached below.

--CA4EC21BFE80.1706836246/mail.example.com
Content-Description: Delivery report
Content-Type: message/delivery-status

Reporting-MTA: dns; mail.example.com
X-Postcow-Queue-ID: CA4EC21BFE80
X-Postcow-Sender: rfc822; test@example.com
Arrival-Date: Fri,  2 Feb 2024 01:10:13 +0000 (UTC)

Final-Recipient: rfc822; recipient@example.net
Original-Recipient: rfc822;recipient@example.net
Action: failed
Status: 5.0.0
Remote-MTA: dns; example.net
Diagnostic-Code: smtp; 550 invalid mailbox (call fwd)

--CA4EC21BFE80.1706836246/mail.example.com
Content-Description: Undelivered Message
Content-Type: message/rfc822

Return-Path: <test@example.com>
Date: Fri, 02 Feb 2024 01:10:13 +0000
Message-ID: <BKAfB/m-40f2Z-AAAAAGINAACIBAAATe4OlSy5-test@example.com>
From: =?utf-8?Q?Source=2Email?= <test@example.com>
Subject: =?UTF-8?Q?Re:=20TEST=
To: =?utf-8?Q?test?= <recipient@example.net>
Cc: =?utf-8?Q?test?= <recipient@example.net>
MIME-Version: 1.0
Content-Type: multipart/alternative;
    boundary="=_9875f05c0d2ad638ed6e39eb808b5ce5"
X-Last-TLS-Session-Version: TLSv1.3

This is a message in Mime Format.  If you see this, your mail reader does not support this format.

--=_9875f05c0d2ad638ed6e39eb808b5ce5
Content-Type: text/plain; charset=utf-8
Content-Transfer-Encoding: base64

Q2hlZXNl=
--=_9875f05c0d2ad638ed6e39eb808b5ce5
Content-Type: text/html; charset=utf-8
Content-Transfer-Encoding: base64

PHN0cm9uZz5jaGVlc2U8L3N0cm9uZz4==
--=_9875f05c0d2ad638ed6e39eb808b5ce5--

--CA4EC21BFE80.1706836246/mail.example.com--        
EOF;

        $parser = new EmailDataParser();
        $result = $parser->parse($email);

        $this->assert($result['mailflags']['bounce'], "Bounce should be true");
        $this->assert($result['in-reply-to'] == '<BKAfB/m-40f2Z-AAAAAGINAACIBAAATe4OlSy5-test@example.com>', "in-reply to should be set");
        $this->assert($result['thread-type'] == 'N', "Thread type should be N");
    }
}
return 'TestUndeliveredMailParsing';
?>
