<?php

/**
 * I've started testing.. yay. 
 */
use PHPUnit\Framework\TestCase;

// Get some mocks
include_once 'setup.inc';

//TODO: Migrate this to phpunit!
// Now, we can test things!
// 
final class AttachmentPreviewPluginTest extends TestCase {

    private $plugin;

    // This will probably break in earlier versions of php.. christ.
    protected function  setUp(): void {
        // reset the mock
        $this->plugin = new AttachmentPreviewPlugin(1);
    }

// Let's start with the bottom, and work our way up.. phew!
    //public function testBootstrap(){
    //how to test? 
    //}
    //
    //skip 10 other private methods..  for now.
    //public function wrap()// fuck.

    /**
     * Test Youtube fetcher
     * @param type $url
     * @dataProvider getYoutubeUrls
     */
    public function testGetYoutubeIdFromUrl($url, $expectation) {
        $this->assertEquals($this->plugin->getYoutubeIdFromUrl($url), $expectation);
    }

    public function getYoutubeUrls() {
        return array(array(
                'https://www.youtube.com/watch?v=53nCql7VEXA', '53nCql7VEXA'),
            array('m.youtube.com/watch?v=53nCql7VEXA&app=desktop', '53nCql7VEXA')
        );
    }

    //public function testGetDom()// who named this shit?
    //public function testPrintDom() // needs a dom..

    public function fakeApiUser() {
        // Need a remote structure to test with.. christ. 
        $dom                       = new DOMDocument();
        $script_element            = $dom->createElement('b');
        $script_element->nodeValue = 'FINDME';

        // Connect to the attachment_previews API wrapper and save the structure:
        Signal::send('attachments.wrapper', 'test', (object) array(
                    'locator'    => 'tag', // References an HTML tag, in this case <body>
                    'expression' => 'body', // Append to the end of the body (persists through pjax loads of the container)
                    'element'    => $script_element
                )
        );

        $html = '<!DOCTYPE html><html><head><title>Title!</title></head><body><p>Body!</p></body></html>';
    }

    // public function testAddArbitraryHtml(){} // really? 
    public function testAddArbitraryScript() {
        $script = 'var variable = 1;';
        AttachmentPreviewPlugin::add_arbitrary_script($script);

        $this->assertArrayHasKey('raw-script-1', AttachmentPreviewPlugin::$foreign_elements);
        $obj = reset(AttachmentPreviewPlugin::$foreign_elements['raw-script-1']);
        $this->assertInstanceOf('DOMElement', $obj->element);
    }

    // public function testAppendHtml(){} // pointless
    // public function testProcessRemoteElements(){} // christ
    //public function testUpdateStructure(){
    // hmm.. we'll need a DOMDocument structure, something to add to it
    // and a way of testing that it's updated.. fuck. 
    //}

    /** Test for getExtension($string)
     * @dataProvider getExtensionData
     */
    public function testGetExtension($filename, $expectation) {
        $this->assertEquals(AttachmentPreviewPlugin::getExtension($filename), $expectation);
    }

    public function getExtensionData() {
        return array(
            array('test.php', 'php'),
            array('something.jpg', 'jpg'),
            array('ANtyHingsdf.asdfadf.234m,345,gdfd.F', 'f'),
            array('swear.words', 'words')
        );
    }

    /**
     * Test for isTicketsView()
     * @dataProvider getUrls
     */
    public function testIsTicketsView($url, $expected) {
        $_SERVER['REQUEST_URI'] = $url;
        $this->assertSame(AttachmentPreviewPlugin::isTicketsView(), $expected);
    }

    /**
     * 
     * @return type
     * @dataProvider postUrls
     */
    public function testIsTicketsViewPost($url, $postdata, $expected) {
        global $_SERVER, $_POST;
        $_SERVER['REQUEST_URI'] = $url;
        $_POST                  = $postdata;
        $this->assertSame(AttachmentPreviewPlugin::isTicketsView(), $expected);
    }

    public function getUrls() {
        $b = 'https://tickets.dev/support/';

        return array(
            array($b . 'index.php', FALSE),
            array($b . 'tickets.php', FALSE),
            array($b . 'scp/index.php', TRUE),
            array($b . 'scp/tickets.php', TRUE),
            array($b . 'scp/tickets.php?a=edit', FALSE),
            array($b . 'scp/tickets.php?a=print', FALSE),
            array('http://crazylongdomainnamethatreallyprobablyhopefullyisntinusebutactuallyyouknowwhatitjustmightbe.longasstld/someidiotpainfullylong/series/of/folders/threatening/to/make/the/url/longer/than/the/maximum/well/lets/be/honest/its/already/longer/than/anyone/would/want/to/type/support/scp/tickets.php?id=158279',
                TRUE)
        );
    }

    public function postUrls() {
        $b = 'https://tickets.dev/support/';

        return array(
            array($b . 'scp/tickets.php', array('a' => 'open'), TRUE),
            array($b . 'scp/anything.php', array('a' => 'anything'), FALSE),
        );
    }

    /**
     *  Test for isPjax
     */
    public function testIsNotPjax() {
        $_SERVER['HTTP_X_PJAX'] = 'false';
        $this->assertSame(AttachmentPreviewPlugin::isPjax(), FALSE);
    }

    /**
     *  Test for isPjax
     */
    public function testIsPjax() {

        $_SERVER['HTTP_X_PJAX'] = 'true';
        $this->assertSame(AttachmentPreviewPlugin::isPjax(), TRUE);
    }

    /**
     * Does our function work?
     * @dataProvider getByteSizedStrings
     */
    public function testUnformatSize($input, $expected) {
        $out = $this->invokeMethod($this->plugin, 'unFormatSize', array($input));
        $this->assertEquals($out, $expected);
    }

    /**
     * Naively assumes the string is a MB rather than MiB type data-size
     */
    public function getByteSizedStrings() {

        return array(
            // osticket string representation of size, number of bytes
            array('1 bytes', 1),
            array('444 bytes', 444),
            array('1 kb', 1024),
            array('122 kb', 124928),
            array('1 mb', 1048576),
            array('12 mb', 12582912)
        );
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @see https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

}
