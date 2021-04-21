<?php
require_once "class.test.php";
require_once INCLUDE_DIR."class.mailparse.php";

abstract class Priorities {
    const HIGH_PRIORITY = 1;
    const NORMAL_PRIORITY = 2;
    const LOW_PRIORITY = 3;
    const NO_PRIORITY = 0;
}

class TestHeaderFunctions extends Test {
    var $name = "Email Header Function Algorithm Regression Tests.";

    function testMailParsePriority() {
        $func_class_method = array('Mail_Parse','parsePriority');
        $strlen_base = strlen($this->h());

        foreach ( array (
                // input => output
                'X-Priority: isNAN' => Priorities::NO_PRIORITY,
                'X-Priority: 1' => Priorities::HIGH_PRIORITY,
                'X-Priority: 2' => Priorities::HIGH_PRIORITY,
                'X-Priority: 3' => Priorities::NORMAL_PRIORITY,
                'X-Priority: 4' => Priorities::NORMAL_PRIORITY,
                'X-Priority: 5' => Priorities::LOW_PRIORITY,
                'X-Priority: 6' => Priorities::LOW_PRIORITY,
                'No priority set' => Priorities::NO_PRIORITY,
                'Priority: normal' => Priorities::NORMAL_PRIORITY,
                'xyz-priority: high' => Priorities::HIGH_PRIORITY,
                'Priority: high' => Priorities::HIGH_PRIORITY,
                'priority: low' => Priorities::LOW_PRIORITY,
                'x-priority: 1000' => Priorities::HIGH_PRIORITY, // only matches first 1, not the full 1000
                'priority: 3' => Priorities::NORMAL_PRIORITY,
                'IPM-Importance: low' => Priorities::LOW_PRIORITY,
                'My-Importance: URGENT' => Priorities::HIGH_PRIORITY,
                'Urgency: High' => Priorities::NO_PRIORITY, //urgency doesn't match.. maybe it should?
                'Importance: Low' => Priorities::LOW_PRIORITY,
                'X-MSMail-Priority: High' => Priorities::HIGH_PRIORITY,
                '' => Priorities::NO_PRIORITY
        ) as $priority => $response ) {
            $this->assert(is_int($response), "Setup fail, function should only return Integer values");
            //get header
            $header = $this->h($priority);

            if(strlen($priority)){
                $this->assert((strlen($header) > $strlen_base), "Setup fail, function h not returning correct string length");
            }
            if (! (call_user_func_array ($func_class_method , array($header) ) == $response)){
                //TODO: make line number dynamic
                $this->fail ( "class.mailparse.php", 351, "Algorithm mistake: $priority should return $response!" );
            }else{
                $this->pass();
            }
        }

    }

    /**
     * Generate some header text to test with. Allows insertion of a known header variable
     *
     * @param string $setPriority
     * @return string
     */
    function h($setPriority = "") {
        return <<<HEADER
Delivered-To: clonemeagain@gmail.com
Received: by 10.69.18.42 with SMTP id gj10csp88238pbd;
Fri, 20 Dec 2013 10:08:25 -0800 (PST)
X-Received: by 10.224.13.80 with SMTP id b16mr16256982qaa.73.1387562904239;
Fri, 20 Dec 2013 10:08:24 -0800 (PST)
Return-Path: <noreply@github.com>
Received: from github-smtp2a-ext-cp1-prd.iad.github.net (github-smtp2-ext5.iad.github.net. [192.30.252.196])
by mx.google.com with ESMTPS id k3si6568083qao.74.2013.12.20.10.08.23
for <clonemeagain@gmail.com>
(version=TLSv1.1 cipher=ECDHE-RSA-RC4-SHA bits=128/128);
Fri, 20 Dec 2013 10:08:23 -0800 (PST)
Received-SPF: pass (google.com: domain of noreply@github.com designates 192.30.252.196 as permitted sender) client-ip=192.30.252.196;
Authentication-Results: mx.google.com;
spf=pass (google.com: domain of noreply@github.com designates 192.30.252.196 as permitted sender) smtp.mail=noreply@github.com
Date: Fri, 20 Dec 2013 10:08:23 -0800
From: Jared Hancock <notifications@github.com>
Reply-To: "osTicket/osTicket-1.8" <reply+i-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH@reply.github.com>
To: "osTicket/osTicket-1.8" <osTicket-1.8@noreply.github.com>
Cc: clonemeagain <clonemeagain@gmail.com>
Message-ID: <osTicket/osTicket-1.8/pull/336/issue_event/82864993@github.com>
In-Reply-To: <osTicket/osTicket-1.8/pull/336@github.com>
References: <osTicket/osTicket-1.8/pull/336@github.com>
Subject: Re: [osTicket-1.8] Landing page inline image correction (#336)
Mime-Version: 1.0
Content-Type: multipart/alternative;
boundary="--==_mimepart_52b4879729712_d621217cfc567e3";
charset=UTF-8
Content-Transfer-Encoding: 7bit
Precedence: list
X-GitHub-Recipient: clonemeagain
X-GitHub-Reason: author
List-ID: osTicket/osTicket-1.8 <osTicket-1.8.osTicket.github.com>
List-Archive: https://github.com/osTicket/osTicket-1.8
List-Post: <mailto:reply+i-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH@reply.github.com>
List-Unsubscribe: <mailto:unsub+i-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH@reply.github.com>,
<https://github.com/notifications/unsubscribe/BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISH-BUNCHORANDOMGIBBERIBBERISHBUNCHORANDOMGIBBERIBBERISHBUNCHORANDOMGIBBERIBBERISH>
X-Auto-Response-Suppress: All
X-GitHub-Recipient-Address: clonemeagain@gmail.com
$setPriority
    	
HEADER;
    }

}
return 'TestHeaderFunctions';
?>
