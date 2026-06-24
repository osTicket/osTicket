<?php
require_once "class.test.php";
require_once INCLUDE_DIR.'class.auth.php';
require_once INCLUDE_DIR.'class.staff.php';
require_once INCLUDE_DIR.'class.format.php';
require_once INCLUDE_DIR.'class.thread.php';

class TestFormatting extends Test {
    var $name = "Formatting checks";

    function assertCleanTextEqual($body, $clean) {
        $this->assertEqual(ThreadEntryBody::clean($body, 'text'), $clean);
    }

    function assertCleanHtmlEqual($body, $clean) {
        $this->assertEqual(ThreadEntryBody::clean($body, 'html'), $clean);
    }

    function testText() {
        $this->assertCleanTextEqual('foo', 'foo');
        $this->assertCleanTextEqual('  ', '');
    }

    function testHtml() {
        $this->assertCleanHtmlEqual('Hello', 'Hello');
        $this->assertCleanHtmlEqual('Hello <b>world', 'Hello <b>world</b>');
        $this->assertCleanHtmlEqual('Hello</em> world', 'Hello world');
        $this->assertCleanHtmlEqual('Morning<script>alert("a");</script>', 'Morning');
        $this->assertCleanHtmlEqual('<a href="https://osticket.com/">osTicket</a>', '<a href="https://osticket.com/">osTicket</a>');
    }
}

return 'TestFormatting';
?>
