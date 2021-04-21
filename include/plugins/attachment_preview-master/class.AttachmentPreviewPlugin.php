<?php
require_once (INCLUDE_DIR . 'class.plugin.php');
require_once (INCLUDE_DIR . 'class.file.php');
require_once (INCLUDE_DIR . 'class.format.php');
require_once ('config.php');

/**
 * Provides in-line attachments, and an interface to the DOM wrapper. Read the
 * wiki for more. Requires PHP 5.6+
 *
 * @return string
 */
class AttachmentPreviewPlugin extends Plugin {

  var $config_class = 'AttachmentPreviewPluginConfig';

  /**
   * What signal are we going to allow others to connect to?
   *
   * @var string
   */
  const signal_id = 'attachments.wrapper';

  /**
   * An array of received structures containing DOMElement's.
   *
   * @var array
   */
  static $foreign_elements = array();

  /**
   * You will want this off! It will post an error log entry for every single
   * request.. which get's heavy.
   *
   * @var bool
   */
  const DEBUG = FALSE;

  /**
   * An array of messages to be logged. This plugin is called before $ost is
   * fully loaded, so it's likely/possible that actually sending $ost->logDebug
   * $ost->logError etc isn't possible.
   *
   * @var array
   */
  private $messages = array();

  /**
   * Singleton reference to itself, used for static functions to find the one
   * object.
   *
   * @var AttachmentPreviewPlugin
   */
  static $instance = null;

  /**
   * Explicitly define a constructor, to wrap a $this reference as $instance
   *
   * @param int $id of the plugin
   */
  public function __construct($id) {
    parent::__construct($id);
    self::$instance = $this;
  }

  /**
   * Replacing the register_shutdown_function's anonymous use of $this with the
   * object's destructor for PHP5.3 compatibility
   */
  public function __destruct() {
    if (count($this->messages)) {
      $this->log_after();
    }
  }

  /**
   * Singleton pattern Relies on the normal bootstrap phase from osTicket to
   * construct the only instance we want.
   *
   * @return AttachmentPreviewPlugin
   * @throws Exception
   */
  public static function getInstance() {
    if (! self::$instance) {
      throw new Exception(
        'Invalid use, please wait for the plugins to Bootstrap properly.');
    }
    return self::$instance;
  }

  /**
   * Try and do as little as possible in the bootstrap function, as it is called
   * on every page load, before the system is even ready to start deciding what
   * to do. I'm serious. $ost is what starts this, but it does it during it's
   * own bootstrap phase, so we don't actually have access to all functions that
   * $ost has yet.
   *
   * {@inheritdoc}
   *
   * @see Plugin::bootstrap()
   */
  function bootstrap() {
    // Ensure plugin does not run during cli cron calls. There is no DOM to manipulate in CLI mode.
    if (php_sapi_name() == 'cli') {
      return;
    }

    // Assuming that other plugins want to inject an element or two..
    // Provide a connection point to the attachments.wrapper
    Signal::connect(self::signal_id,
      function ($object, $data) {
        self::$instance->debug_log("Received connection from %s",
          get_class($object));
        // 
        // Assumes you want to edit the DOM with your structures, and that you've read the docs.
        // Just save them here until the page is done rendering, then we'll make all these changes at once:
        self::$foreign_elements[get_class($object)] = $data;
      });

    // Check what our URI is, if acceptable, add to the output.. :-)
    // Looks like there is no central router in osTicket yet, so I'll just parse REQUEST_URI
    // Can't go injecting this into every page.. we only want it for the actual ticket pages & Knowledgebase Pages
    if (self::isTicketsView() && $this->getConfig()->get('attachment-enabled')) {
      $this->debug_log(
        "Agent requested a tickets-view: Starting the attachments plugin.");
      // We could hack at core, or we can simply capture the whole page output and modify the HTML then..
      // Not "easier", but less likely to break core.. right?
      // There appears to be a few uses of ob_start in the codebase, but they stack, so it works!
      ob_start();

      // This will run after everything else, empties the buffer and runs our code over the HTML
      // Then we send it to the browser as though nothing changed..
      register_shutdown_function(
        function () {
          AttachmentPreviewPlugin::shutdownHandler();
        });
    }
  }

  /**
   * Wrapper around register_shutdown_function to avoid making $this calls from
   * anonymous functions.. Hopefully the Singleton factory pattern works
   * properly..
   */
  public static function shutdownHandler() {
    $plugin = AttachmentPreviewPlugin::getInstance();
    $plugin->debug_log("Shutdown handler for inline attachments running");
    // Output the buffer
    // Check for Attachable's and print
    // Note: This also checks foreign_elements
    $page = $plugin->inlineAttachments(ob_get_clean());

    // We should only embed the scripts once, however, if you aren't seeing it work, you'll have to refresh.. 
    $page = $plugin->insertScript($page);

    print $page;
  }

  /**
   * Injects the plugin's scripts and stylehseet into the page
   */
  public static function insertScript($page) {
    if (preg_match('/AP: Plugin loaded/', $page)) {
      // script is already loaded, don't inject again.
      return $page;
    }
    $plugin = AttachmentPreviewPlugin::getInstance();
    $config = $plugin->getConfig();
    $css = file_get_contents(__DIR__ . '/stylesheet.css');

    // Build a config object of options for javascript to use
    // This makes it translateable/dynamic/configurable etc.
    $plugin_options = (object) array(
      'text_show' => __('Show Attachment'),
      'text_hide' => __('Hide Attachment'),
      'limit' => $config->get('show-initially'),
      'hide_seen' => $config->get('hide-seen') ? 1 : 0,
      'hide_age' => $config->get('hide-age') ?: 14,
      'open_attachments' => $config->get('newtab-links') ? 'new-tab' : 'normal'
    );

    $plugin_js_variable = 'AttachmentPreview = ' . json_encode($plugin_options) . ';'; //NOTE: JSON_PRETTY_PRINT only available from 5.4+
    // If we were able to simply link to the script, we could insert the settings and have the browser cache the script.. but noo
    // Fetch the file and replace the comment with the config to override the defaults:
    $script = str_replace('/* REPLACED_BY_PLUGIN */', $plugin_js_variable,
      file_get_contents(__DIR__ . '/script.js'));

    // find the </head> tag, and insert our stylesheet just before it.
    $head = '</head>';
    $replacement = "<style>$css</style>\n</head>";
    $page = str_replace($head, $replacement, $page);
    // find the </body> tag, insert our javascript just before it.
    $foot = '</body>';
    $replacement = "<script>$script</script>\n</body>";
    return str_replace($foot, $replacement, $page); // only do it once.. 
  }

  /**
   * Converts an osTicket filesize2bytes($size) string back into the number of
   * bytes: eg: "10 mb" == 10485760 This allows comparison with admin settings,
   * to filter out oversized attachments.
   *
   * @param string $formatted_size
   * @return int
   */
  private function unFormatSize($formatted_size) {
    $from = array(
      'bytes' => function ($value) {
        return $value;
      },
      'kb' => function ($value) {
        return $value * 1024;
      },
      'mb' => function ($value) {
        return $value * 1024 * 1024;
      }
    );
    foreach ($from as $match => $formula) {
      if (stripos($formatted_size, $match) !== FALSE) {
        return call_user_func($formula,
          trim(str_replace($match, '', $formatted_size)));
      }
    }
  }

  /**
   * Builds a DOMDocument structure representing the HTML, checks the links
   * within for Attachments, then builds inserts inline attachment objects, and
   * returns the new HTML as a string for printing.
   *
   * @param string $html
   * @return string $html
   */
  private function inlineAttachments($html) {
    if (! $html) {
      $this->debug_log("Received no HTML, returned none..");
      // Something broke.. we can't even really recover from this, hopefully it wasn't our fault.
      // If this was called incorrectly, actually sending HTML could break AJAX or a binary file or something..
      // Error message therefore disabled:
      return '<html><body><h3>:-(</h3><p>Not sure what happened.. something broke though.</p></body></html>';
    }

    // We'll need this..
    $config = $this->getConfig();
    $allowed_extensions = $this->get_allowed_extensions($config);

    if (! count($allowed_extensions)) {
      $this->debug_log("Not allowed to do anything, not doing anything.");
      // We've not been granted permission to change anything, so don't... just return original HTML.
      return $html;
    }

    $this->debug_log(
      "Looking for linked files with ext: " .
      implode(',', array_values($allowed_extensions)));

    // Let's not get regex happy.. we all have the tendency.. :-)
    $dom = self::getDom($html);
    $xpath = new DOMXPath($dom);

    $links_seen = 0;
    $attachments_inlined = 0;

    // cache setting 
    $rewrite_youtube = (bool) $config->get('attach-youtube');
    $size_limit = (int) $config->get('attachment-size');
    // Find all <a> elements: http://stackoverflow.com/a/29272222 as DOMElement's
    foreach ($dom->getElementsByTagName('a') as $link) {
      // All links.. could be messy
      $this->debug_log("Saw link: %s", $link->textContent);
      $links_seen++;

      // Check the link points to osTicket's "attachments" provider:
      // osTicket uses /file.php for all attachments
      // we could have used XPATH: //a[@class="file"] however, that would break the youtube one.
      if (strpos($link->getAttribute('href'), '/file.php') !== FALSE) {
        // Luckily, the attachment link contains the filename.. which we can use!
        // Grab the extension of the file from the filename:
        $ext = $this->getExtension($link->textContent);
        if ($size_limit) {
          $this->debug_log("size filter found: %b kb", $size_limit);
          $size_element = $xpath->query("following-sibling::*[1]", $link)->item(0);

          if ($size_element instanceof DomElement) {
            $size_b = $this->unFormatSize($size_element->nodeValue);
            $this->debug_log("%s is roughly: %d bytes in size.", $ext, $size_b);
            $size_kb = $size_b / 1024;
            if ($size_kb > $size_limit) {
              // Skip this one, got a bit of an ass on it!
              $this->debug_log(
                "Skipping attachment, size filter restricted it.");
              continue;
            }
          }
        }
        $this->debug_log("Attempting to add %s file.", $ext);

        // See if admin allowed us to inject files with this extension:
        if (! $ext || ! isset($allowed_extensions[$ext])) {
          continue;
        }

        // Find the associated method to add the attachment: (defined above, eg: csv => addTEXT)
        $func = $allowed_extensions[$ext];
        if (method_exists($this, $func)) {
          $this->debug_log("Calling %s for %s", $func,
            $link->getAttribute('href'));
          // Call the method to insert the linked attachment into the DOM:
          try {
            $this->{$func}($dom, $link);
          } catch (Exception $e) {
            $this->log("Exception encountered running $func");
          }
          $attachments_inlined++;
        }
      }
      elseif ($rewrite_youtube) {
        // This link isn't to /file.php & admin have asked us to check if it is a youtube link.
        // The overhead of checking strpos on every URL is less than the overhead of checking for a youtube ID!
        if (strpos($link->getAttribute('href'), 'youtub') !== FALSE) {
          try {
            $this->add_youtube($dom, $link);
          } catch (Exception $e) {
            $this->log("Exception encountered injecting Youtube player");
          }
          $attachments_inlined++;
        }
      }
    }

    $this->debug_log("Saw %d link elements in the osTicket page.", $links_seen);
    $this->debug_log("Inlined: %d attachment[s].", $attachments_inlined);

    // Before we return this, let's see if any foreign_elements have been provided by other plugins, we'll insert them.
    // This allows those plugins to edit this plugin.. seat-of-the-pants stuff!
    if (count(self::$foreign_elements)) {
      $this->processRemoteElements($dom); // Handles the HTML generation at the end.
    }

    // just return the original if error
    $modified_html = self::printDom($dom);
    if (! $modified_html) {
      $this->log("Error manipulating the DOM, original returned.");
      return $html;
    }
    // The edited HTML can be sent to the browser (end of shutdown_handler calls print)
    return $modified_html;
  }

  /**
   * Figures out which extensions we are allowed to insert based on config
   * Handily compiles the associated method.
   *
   * @param PluginConfig $config
   */
  private function get_allowed_extensions(PluginConfig $config) {
    // Determine what method to run for each extension type:
    $allowed_extensions = array();

    // If you know browsers can handle more, please, submit a pull request!
    $types = array(
      'pdf' => array(
        'pdf'
      ),
      'text' => array(
        'csv',
        'txt'
      ),
      'html' => array(
        'html'
      ),
      'image' => array(
        'bmp',
        'svg',
        'gif',
        'png',
        'jpg',
        'jpeg'
      ),
      'audio' => array(
        'wav',
        'mp3'
      ),
      'video' => array(
        'mp4',
        'ogv',
        'ogg',
        'ogm',
        'webm',
        '3gp',
        'flv'
      )
    );
    foreach ($types as $type => $extensions) {
      if (! $config->get("attach-{$type}")) {
        continue;
      }
      foreach ($extensions as $ext) {
        // Example: [pdf] = add_pdf
        $allowed_extensions[$ext] = 'add_' . $type;
      }
    }
    return $allowed_extensions;
  }

  /**
   * Converts a linked audio file into an embedded HTML5 player.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/audio
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_audio(DOMDocument $doc, DOMElement $link) {
    $audio = $doc->createElement('audio');
    // $audio->setAttribute('autoplay','false'); //TODO: See if anyone wants these as admin options
    // $audio->setAttribute('loop','false');
    $audio->setAttribute('preload', 'auto');
    $audio->setAttribute('controls', 1);
    $audio->setAttribute('src', $link->getAttribute('href'));
    $this->debug_log('Wrapped %s as audio player.', $link->textContent);
    $this->wrap($doc, $link, $audio);
  }

  /**
   * Fetches an HTML attachment as the user via javascript in the browser, then
   * injects it into the DOM. Attempts have been made to sanitize it.
   *
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_html(DOMDocument $doc, DOMElement $link) {
    $d = $doc->createElement('div');
    $d->setAttribute('data-url', $link->getAttribute('href'));
    $d->setAttribute('data-type', 'html');
    $this->debug_log('Wrapped %s as html.', $link->textContent);
    $this->wrap($doc, $link, $d);
  }

  /**
   * Embeds the image into the DOM as <img> Only supports what Mozilla says
   * browsers can support.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_image(DOMDocument $doc, DOMElement $link) {

    // Rebuild the download link as a normal clickable link, for full-size viewing:
    $a = $doc->createElement('a');
    $a->setAttribute('href', $link->getAttribute('href'));

    // Build an image of the referenced file, so we can simply preview it
    $img = $doc->createElement('img');
    $img->setAttribute('data-url', $link->getAttribute('href'));
    $img->setAttribute('data-type', 'image');
    // Put the image inside the link, so the image is clickable (opens in new tab):
    $a->appendChild($img);

    // Add a title attribute to the download link:
    $link->setAttribute('title', __('Download this image.'));
    $this->debug_log('Wrapped %s as image.', $link->textContent);
    $this->wrap($doc, $link, $a);
  }

  /**
   * Attach PDF into the DOM
   *
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_pdf(DOMDocument $doc, DOMElement $link) {
    // Build a Chrome/Firefox compatible <object> to hold the PDF
    $pdf = $doc->createElement('object');
    $pdf->setAttribute('width', '100%');
    $pdf->setAttribute('height', '1000px'); // Arbitrary height
    $pdf->setAttribute('data', ''); //$url . '&disposition=inline'); // Can't use inline disposition with XSS security rules.. :-(
    $pdf->setAttribute('type', 'application/pdf');
    $pdf->setAttribute('data-type', 'pdf');
    $pdf->setAttribute('data-url', $link->getAttribute('href'));

    // Add a <b>Nope</b> type message for obsolete or text-based browsers.
    $message = $doc->createElement('b');
    $message->nodeValue = __('Your "browser" is unable to display this PDF. ');
    if ($this->getConfig()->get('show-ms-upgrade-help')) {
      $call_to_action = $doc->createElement('a');
      $call_to_action->setAttribute('href', 'http://abetterbrowser.org/');
      $call_to_action->setAttribute('title',
        __('Get a better browser to use this content inline.'));
      $call_to_action->nodeValue = __('Help');
      $message->appendChild($call_to_action);
    }
    $pdf->appendChild($message);
    $this->debug_log('Wrapped %s as pdf.', $link->textContent);
    $this->wrap($doc, $link, $pdf);
  }

  /**
   * Fetches a TEXT attachment entirely, and injects it into the DOM via ajax
   *
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_text(DOMDocument $doc, DOMElement $link) {
    $pre = $doc->createElement('pre');
    $pre->setAttribute('data-url', $link->getAttribute('href'));
    $pre->setAttribute('data-type', 'text');
    $this->debug_log('Wrapped %s as preformatted text.', $link->textContent);
    $this->wrap($doc, $link, $pre);
  }

  /**
   * Converts a link to Youtube player Fully loaded only, ie: <a
   * src="youtube.com/v/12345">Link to youtube</a> only, not just a bare youtube
   * URL.
   *
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_youtube(DOMDocument $doc, DOMElement $link) {
    $youtube_id = $this->getYoutubeIdFromUrl($link->getAttribute('href'));
    if ($youtube_id !== FALSE) {
      // Now we can add an iframe so the video is instantly playable.
      // eg: <iframe width="560" height="349" src="http://www.youtube.com/embed/something?rel=0&hd=1" frameborder="0" allowfullscreen></iframe>
      // TODO: Make responsive.. if required.
      $player = $doc->createElement('iframe');
      $player->setAttribute('width', '560');
      $player->setAttribute('height', '349');
      $player->setAttribute('src',
        'https://www.youtube.com/embed/' . $youtube_id . '?rel=0&hd=1');
      $player->setAttribute('frameborder', 0);
      $player->setAttribute('allowfullscreen', 1);
      $this->debug_log('Wrapped %s as youtube player.', $link->textContent);
      $this->wrap($doc, $link, $player);
    }
  }

  /**
   * Converts a linked video file into an embedded HTML5 player.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/video
   * @param DOMDocument $doc
   * @param DOMElement $link
   */
  private function add_video(DOMDocument $doc, DOMElement $link) {
    $video = $doc->createElement('video');
    $video->setAttribute('controls', 1);
    $video->setAttribute('preload', 'metadata');
    $video->nodeValue = 'Sorry, your browser doesn\'t support embedded videos, but don\'t worry, you can still download it and watch it with your favorite video player!';
    $source = $doc->createElement('source');
    $source->setAttribute('src', $link->getAttribute('href'));
    $source->setAttribute('type',
      'video/' . $this->getExtension($link->textContent));
    $video->appendChild($source);
    $this->debug_log('Wrapped %s as video player.', $link->textContent);
    $this->wrap($doc, $link, $video);
  }

  /**
   * Constructs a <div> element to contain each inlined attachment.
   *
   * @param DOMDocument $doc
   * @param DOMElement $source
   * @param DOMElement $new_child
   */
  private function wrap(DOMDocument $doc, DOMElement $source,
    DOMElement $new_child) {
    // Implement a limit for attachments. Only show the admin configured amount at first
    // if there are any more, we will inject them, however they will be shown as buttons
    static $number = 0;
    static $limit = 0;

    $number++;

    // Build a wrapper element to contain the attachment
    $wrapper = $doc->createElement('div');

    // Build an ID for the wrapper
    $id = 'ap-file-' . $number;
    $wrapper->setAttribute('id', $id);

    // Set the child's ID.. for ease of scripting
    $new_child->setAttribute('id', "$id-c");

    // Add the element to the wrapper
    $wrapper->appendChild($new_child);

    // Fetch the attachment limit from the config for later
    $limit = $this->getConfig()->get('show-initially');

    // See if we are over the admin-defined maximum number of inline-attachments:
    if ($limit == "ALL" || $number <= $limit) {
      // Not limited, just add a class to received our styles
      $wrapper->setAttribute('class', 'ap_embedded');
    }
    else {
      // Instead of injecting the element, let's show a button to click
      $button = $doc->createElement('a');
      $button->setAttribute('class', 'button');

      // Link button to the toggle function
      $button->setAttribute('onClick', "ap_toggle(this,'$id');");

      // Initially set the text, toggle will change it to "Hide" if toggled
      $button->nodeValue = __('Show Attachment');

      // Hide the whole wrapper via the class "hidden"
      $wrapper->setAttribute('class', 'ap_embedded hidden');

      // Insert the button before the wrapper, so it stays where it is when the wrapper expands.
      $source->parentNode->appendChild($button);
    }
    // Add the wrapper to the thread/source element
    $source->parentNode->appendChild($wrapper);
  }

  /**
   * Get Youtube video ID Based on http://stackoverflow.com/a/9785191
   *
   * @param string $url
   * @return mixed Youtube video ID or FALSE if not found
   */
  public function getYoutubeIdFromUrl($url) {
    // Series of possible url patterns, please pull-request any others you find!
    // Ideally they are in "most-common" first order.
    // Note the match group's around the ID of the video
    $regex = array(
      '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
      '/youtube\.com\/embed\/([^\&\?\/]+)/',
      '/youtube\.com\/v\/([^\&\?\/]+)/',
      '/youtu\.be\/([^\&\?\/]+)/',
      '/youtube\.com\/verify_age\?next_url=\/watch%3Fv%3D([^\&\?\/]+)/'
    );
    $match = array();
    foreach ($regex as $pattern) {
      if (preg_match($pattern, $url, $match)) {
        $this->debug_log('Matched youtube id: %s for url: %s', $match[1], $url);
        // Return the matched video ID
        return $match[1];
      }
    }
    // not a youtube video
    return FALSE;
  }

  /**
   * Converts an HTML string into a DOMDocument.
   *
   * @param string $html
   * @return \DOMDocument
   */
  public static function getDom($html = '') {
    $p = self::getInstance();
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->validateOnParse = true;
    $dom->resolveExternals = true;
    $dom->preserveWhiteSpace = false;
    // Turn off XML errors.. if only it was that easy right?
    $dom->strictErrorChecking = FALSE;
    $xml_error_setting = libxml_use_internal_errors(true);

    // Because PJax isn't a full document, it kinda breaks DOMDocument
    // Which expects a full document! (You know with a DOCTYPE, <HTML> <BODY> etc.. )
    if (self::isPjax() &&
      (strpos($html, '<!DOCTYPE') !== 0 || strpos($html, '<html') !== 0)) {
      // Prefix the non-doctyped html snippet with an xml prefix
      // This tricks DOMDocument into loading the HTML snippet
      $xml_prefix = '<?xml encoding="UTF-8" />';
      $html = $xml_prefix . $html;
    }

    // Convert the HTML into a DOMDocument, however, don't imply it's HTML, and don't insert a default Document Type Template
    // Note, we can't use the Options parameter until PHP 5.4 http://php.net/manual/en/domdocument.loadhtml.php
    if (! ($loaded = $dom->loadHTML($html))) {
      $p->debug_log("There was a problem loading the DOM.");
    }
    else {
      $p->debug_log("%d chars of HTML was inserted into a DOM", strlen($html));
    }
    libxml_use_internal_errors($xml_error_setting); // restore xml parser error handlers
    $p->debug_log('DOM Loaded.');
    return $dom;
  }

  /**
   * Gets the DOM back as HTML
   *
   * @param DOMDocument $dom
   * @return bool|string
   */
  public static function printDom(DOMDocument $dom) {
    $p = self::getInstance();
    $p->debug_log("Converting the DOM back to HTML");
    // Check for failure to generate HTML
    // DOMDocument::saveHTML() returns null on error
    $new_html = $dom->saveHTML();

    // Remove the DOMDocument make-happy encoding prefix:
    if (self::isPjax()) {
      $remove_prefix_pattern = '@<\?xml encoding="UTF-8" />@';
      $new_html = preg_replace($remove_prefix_pattern, '', $new_html);
    }
    return $new_html;
  }

  /**
   * Wrapper around log method that checks for DEBUG first.
   */
  private function debug_log($text, $_ = null) {
    if (self::DEBUG) {
      $args = func_get_args();
      $text = array_shift($args);
      $this->log($text, $args); // send variable amount of args as array
    }
  }

  /**
   * Supports variable replacement of $text using sprintf macros
   *
   * @param string $text
   *          with sprintf macros
   * @param $_ any
   *          number of params for the macros
   */
  private function log($text, $_ = null) {
    // Log to system, if available
    global $ost;

    $args = func_get_args();
    $format = array_shift($args);
    if (! $format) {
      return;
    }
    if (is_array($args[0])) {
      // handle debug_log's version or array of variables passed
      $text = vsprintf($format, $args[0]);
    }
    elseif (count($args)) {
      // handle normal variables as arguments
      $text = vsprintf($format, $args);
    }
    else {
      // no variables passed
      $text = $format;
    }

    if (! $ost instanceof osTicket) {
      // doh, can't log to the admin log without this object
      // setup a callback to do the logging afterwards:
      // save the log message in memory for now
      // the callback registered above will retrieve it and log it
      $this->messages[] = $text;
      return;
    }

    error_log("AttachmentPreviewPlugin: $text");
    $ost->logInfo(wordwrap($text, 30), $text, FALSE);
  }

  /**
   * Calls Log function again before shutting down, allows logs to be logged in
   * admin logs, when they otherwise aren't able to be logged. :-)
   */
  private function log_after() {
    global $ost;
    if (! $ost instanceof osTicket) {
      $this->debug_log("Unable to log to normal admin log..");
      foreach ($this->messages as $text) {
        $this->debug_log("Emergency PluginLog: $text");
      }
    }
    else {
      foreach ($this->messages as $text) {
        $this->log($text);
      }
    }
  }

  /**
   * Processes other plugin's structures when we don't run ours.
   *
   * @param string $html
   */
  private function doRemoteWork($html) {
    $dom = self::getDom($html);
    $dom = $this->processRemoteElements($dom);
    $new_html = self::printDom($dom);
    return $new_html ?: $html;
  }

  /**
   * Provides an interface to safely inject HTML into any page. Hopefully
   * useful. Used like:
   * AttachmentsPreviewPlugin::addRawHtml('<h2>Yo!</h2>','tag','body'); Now,
   * your <h2> will appear at the end of the <body> tag. When you don't want to
   * build a sendable structure and send a Signal, just addRaw!
   *
   *
   * @param string $html
   * @param string $locator
   *          one of: id,tag,xpath
   * @param string $expression
   *          an expression used by the locator to place the HTML Nodes within
   *          the Dom.
   * @deprecated
   */
  public static function add_arbitrary_html($html = '', $locator = 'id',
    $expression = 'pjax-container') {

    // Define a static index, we increment it for every node we add
    static $foreigners = 1;

    if (self::DEBUG) {
      // note: static function can't call $this->debug_log()
      if (self::DEBUG) {
        $p = self::getInstance();
        $p->debug_log("Received %d chars of arbitrary HTML", strlen($html));
      }
    }

    // Wrap in a <div> then check for children of it immediately after:
    // DOMElement needs something like a div to wrap it.. otherwise it loads scripts as a DOMCdataElement or something
    $dom = self::getDom("<html><div>$html</div></html>");
    // Now everything that they added can be injected as a "Foreign Element"
    // Note the selector selects the first <div> which is what we injected two lines up:
    $structures = array();
    foreach ($dom->getElementsByTagName('div')->item(0)->childNodes as $node) {
      $structures[] = (object) array(
        'element' => $node,
        'locator' => $locator,
        'expression' => $expression
      );
    }
    if (count($structures)) {
      self::$foreign_elements['raw-' . $foreigners++] = $structures;
    }
  }

  /**
   * Adds a script by rebuilding the script into an injectable format. Don't
   * wrap in <script> tags. Assumes all scripts want to be just before the
   * </body> tag.
   *
   * @param string $script
   * @deprecated
   */
  public static function add_arbitrary_script($script) {
    static $script_count = 1;
    if (self::DEBUG) {
      $p = self::getInstance();
      $p->debug_log("Received %d chars of arbitrary script injection",
        strlen($script));
    }
    $dom = new DOMDocument();
    $script_element = $dom->createElement('script');
    $script_element->setAttribute('type', 'text/javascript');
    $script_element->nodeValue = $script;

    // Connect to the attachment_previews API wrapper and save the structure:
    self::$foreign_elements['raw-script-' . $script_count++] = array(
      (object) array(
        'locator' => 'tag', // References an HTML tag, in this case <body>
        'expression' => 'body', // Append to the end of the body (persists through pjax loads of the container)
        'element' => $script_element
      )
    );
  }

  /**
   * Receives a DOMDocument, returns a DOMDocument that might contain foreign
   * element changes. Works on self::$foreign_elements as an API of changes to
   * the DOM.
   *
   * @param DOMDocument $dom
   * @throws \Exception
   * @return DOMDocument
   */
  private function processRemoteElements(DOMDocument &$dom) {
    //@formatter:off
        /*
         * $this->foreign_elements should be an array of structures like:
         * [
         *  'sourceClassName' =>
         *    [ (object)[
         *       'element' => $element, // The DOMElement to replace/inject etc.
         *       'locator' => 'tag', // EG: tag/id/xpath
         *       'replace_found' => FALSE, // default
         *       'setAttribute' =>
         *          [
         *            'attribute_name' => 'attribute_value'
         *          ],
         *        'expression' => 'body' // which tag/id/xpath etc.
         *   ],
         *   (object)[ // next structure properties ]
         *  ]
         * ]
         */
//@formatter:on
    if (! count(self::$foreign_elements)) {
      // We've already done them.
      return $dom;
    }
    foreach (self::$foreign_elements as $source => $structures) {
      $this->debug_log("Loading %d remote structures from %s",
        count($structures), $source);
      foreach ($structures as $structure) {
        // Validate the Structure
        if (! is_object($structure)) {
          $this->debug_log("Structure wasn't an object. Skipped.");
          continue; // just skip
        }
        try {
          if (! property_exists($structure, 'setAttribute') &&
            (! property_exists($structure, 'element') ||
            ! is_object($structure->element) ||
            ! $structure->element instanceof DOMElement)) {
            // What?
            throw new \Exception(
              "Invalid or missing parameter 'element' from source {$source}.");
          }

          // Verify that the sender used a tag/id/xpath
          if (! property_exists($structure, 'locator')) {
            throw new \Exception("Invalid or missing locator");
          }
          if (! property_exists($structure, 'replace_found')) {
            $structure->replace_found = FALSE;
          }

          if (! property_exists($structure, 'expression')) {
            throw new \Exception("Invalid or missing expression");
          }

          // Load the element(s) into our DOM, we can't insert them until then.
          if (! property_exists($structure, 'setAttribute')) {
            // we aren't just changing an attribute, we are inserting new or replacing.
            $imported_element = $dom->importNode($structure->element, true);
          }

          // Based on type of DOM Selector, lets insert this imported element.
          switch ($structure->locator) {
            case 'xpath':
              // TODO: Fix this.. doesn't seem to work
              $finder = new \DOMXPath($dom);
              $test = 0;
              foreach ($finder->query($structure->expression) as $node) {
                $test++;
                $this->updateStructure($node, $structure, $imported_element);
              }
              if (! $test) {
                throw new Exception("Nothing matched: {$structure->expression}");
              }
              break;
            case 'id':
              // Note, ID doesn't mean jQuery $('#id'); usefulness.. its xml:id="something", which none of our docs will have.
              $finder = new \DOMXPath($dom);
              // Add a fake namespace for the XPath class.. which needs one:
              //  $finder->registerNamespace('pluginprefix',
              //   $_SERVER['SERVER_NAME'] . '/pluginnamespace');
              // Find the first DOMElement with the id attribute matching the expression, there should only be one
              $nodeList = $finder->query("//*[@id='{$structure->expression}']");
              // Check length of the DOMNodeList
              if ($nodeList->length) {
                $node = $nodeList->item(0);
                $this->debug_log("Found a match for $expression!");
              }
              else {
                $this->log("Unable to find node with expression %s",
                  $structure->expression);
                continue 2;
              }
              $this->updateStructure($node, $structure, $imported_element);
              break;
            case 'tag':
              foreach ($dom->getElementsByTagName($structure->expression) as $node) {
                $this->updateStructure($node, $structure, $imported_element);
              }
              break;
            default:
              $this->log(
                "Your locator from %s is invalid, %s has not been implemented. Available options are: xpath,id,tag",
                $source, $structure->locator);
              continue 2;
          }
        } catch (\Exception $de) {
          $this->log("FAIL: %s triggered DOM error: %s", $source,
            $de->getMessage());
        }
        $this->debug_log('Successfull.');
      }
    }
    // Clear the array
    self::$foreign_elements = array();
  }

  /**
   * Connects a remote structure with a DOMElement. either setting attributes,
   * or appending or replacing nodes..
   *
   * @param \DOMElement $node
   * @param stdClass $structure
   * @param \DOMElement $imported_element
   */
  private function updateStructure(\DOMElement $node, $structure,
    \DOMElement $imported_element = null) {
    if ($structure->replace_found) {
      $node->parentNode->replaceChild($node, $imported_element);
    }
    elseif ($structure->setAttribute) {
      foreach ($structure->setAttribute as $key => $val) {
        $node->setAttribute($key, $val);
      }
    }
    else {
      $node->appendChild($imported_element);
    }
  }

  /**
   * Retrieve the file extension from a string in lowercase
   *
   * @param DOMElement $link
   * @return string
   */
  public static function getExtension($string) {
    // Why reinvent the wheel? 
    return trim(strtolower(pathinfo($string, PATHINFO_EXTENSION)));
  }

  /**
   * Determines based on the current URI if the page is a tickets page or not.
   * We only want to inject when viewing tickets, not when EDITING tickets.. or
   * any other view. Available statically via:
   * AttachmentPreviewPlugin::isTicketsView()
   *
   * @return bool whether or not current page is viewing a ticket.
   */
  public static function isTicketsView() {
    $tickets_view = false;
    $url = $_SERVER['REQUEST_URI'];

    // Run through the most likely candidates first:
    // Ignore POST data, unless we're seeing a new ticket, then don't ignore.
    if (isset($_POST['a']) && $_POST['a'] == 'open') {
      $tickets_view = TRUE;
    }
    elseif (strpos($url, '/scp/') === FALSE) {
      // URL doesn't include /scp/ so isn't an agent page
      $tickets_view = FALSE;
    }
    elseif (isset($_POST) && count($_POST)) {
      // If something has been POST'd to osTicket, assume we're not Viewing a ticket
      $tickets_view = FALSE;
    }
    elseif (strpos($url, 'a=edit') || strpos($url, 'a=print')) {
      // URL contains a=edit or a=print, so assume we aren't needed here!
      $tickets_view = FALSE;
    }
    elseif (strpos($url, 'index.php') !== FALSE ||
      strpos($url, 'tickets.php') !== FALSE) {
      // Might be a ticket page..
      $tickets_view = TRUE;
    }
    else {
      // Default
      $tickets_view = FALSE;
    }

    if (self::DEBUG) {
      error_log("Matched $url as " . ($tickets_view ? 'ticket' : 'not ticket'));
    }

    return $tickets_view;
  }

  /**
   * Determines if the page was/is being build from a PJAX request. Uses the
   * request header.
   *
   * @return bool
   */
  public static function isPjax() {
    return (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] == 'true');
  }

  /**
   * Required stub.
   *
   * {@inheritdoc}
   *
   * @see Plugin::uninstall()
   */
  function uninstall(&$errors) {
    parent::uninstall($errors);
  }

  /**
   * Plugin seems to want this.
   */
  public function getForm() {
    return array();
  }
}
