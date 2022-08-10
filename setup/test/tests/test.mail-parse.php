<?php

require_once 'mockdb.php';

require_once INCLUDE_DIR.'class.validator.php';
require_once INCLUDE_DIR.'class.auth.php';
require_once INCLUDE_DIR.'class.staff.php';
require_once INCLUDE_DIR.'class.email.php';
require_once INCLUDE_DIR.'class.format.php';
require_once INCLUDE_DIR.'class.thread.php';

class TestMailParsing extends Test {
    var $name = "Mail parsing library tests";

    function testRecipients() {
        db_connect(new MockDbSource());
        $email = <<<EOF
Delivered-To: jared@osticket.com
Received: by 10.60.55.168 with SMTP id t8csp161432oep;
        Fri, 7 Feb 2014 22:11:19 -0800 (PST)
X-Received: by 10.182.18.9 with SMTP id s9mr16356699obd.15.1391839879167;
        Fri, 07 Feb 2014 22:11:19 -0800 (PST)
Return-Path: <mailer@greezybacon.supportsystem.com>
To: jared@osticket.com
Subject: =?utf-8?Q?System_test_email_=C2=AE?=
Content-Type: multipart/alternative;
 boundary="=_28022448a1f58a3af7edf57ff2e3af44"
From: "Support" <help@supportsystem.com>
Date: Sat, 08 Feb 2014 01:11:18 -0500
Message-ID: <Syke6-g24hwuTu77-help@supportsystem.com>
MIME-Version: 1.0

--=_28022448a1f58a3af7edf57ff2e3af44
Content-Transfer-Encoding: base64
Content-Type: text/plain; charset=utf-8

Q2hlZXJzISE=
--=_28022448a1f58a3af7edf57ff2e3af44
Content-Transfer-Encoding: base64
Content-Type: text/html; charset=utf-8

Q2hlZXJzISE=
--=_28022448a1f58a3af7edf57ff2e3af44--
EOF;

        $parser = new EmailDataParser();
        $result = $parser->parse($email);
        $this->assert(count($result['recipients']) == 1, 'Expected 1 recipient');
        $this->assert($result['recipients'][0]['source'] == 'delivered-to',
            'Delivered-To header used as a collaborator');
    }
}
return 'TestMailParsing';
?>
