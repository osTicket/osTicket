<?php
require_once INCLUDE_DIR . 'class.plugin.php';

class AttachmentPreviewPluginConfig extends PluginConfig {

  // Provide compatibility function for versions of osTicket prior to
  // translation support (v1.9.4)
  function translate() {
    if (! method_exists('Plugin', 'translate')) {
      return array(
        function ($x) {
          return $x;
        },
        function ($x, $y, $n) {
          return $n != 1 ? $y : $x;
        }
      );
    }
    return Plugin::translate('attachment_preview');
  }

  /**
   * Build an Admin settings page.
   *
   * {@inheritdoc}
   *
   * @see PluginConfig::getOptions()
   */
  function getOptions() {
    list ($__, $_N) = self::translate();

    return array(
      'attachment-enabled' => new BooleanField(
        array(
          'label' => $__('Permission'),
          'default' => TRUE,
          'hint' => 'Check to enable attachments inline, uncheck only allows the API to function.'
        )),
      'hide-seen' => new BooleanField(
        array(
          'label' => $__('Hide Seen'),
          'default' => FALSE,
          'hint' => 'When auto-showing an attachment, store the ID in the users browser, so they can choose to see it again if they want, but are not forced to.'
        )),
      'hide-age' => new TextboxField(
        array(
          'label' => $__('Number of days to remember attachments for'), 
          'default' => '14', 
          'hint' => $__('Browser storage is not infinite, so we only store seen attachment ids for a short time before they have to auto-see them again.')
       )),
      'attachment-size' => new TextboxField(
        array(
          'label' => $__('Max Size'),
          'default' => 1024,
          'hint' => 'Enter maximum Kilobytes of an attachment to inline. Larger attachments are ignored, use zero (0) to remove limit.'
        )),
      'attach-pdf' => new BooleanField(
        array(
          'label' => $__('Inline PDF files as <object>s'),
          'default' => TRUE
        )),
      'attach-image' => new BooleanField(
        array(
          'label' => $__('Inline image files as <img>s'),
          'default' => TRUE
        )),
      'attach-text' => new BooleanField(
        array(
          'label' => $__('Inline textfiles (txt,csv) as <pre>'),
          'default' => TRUE
        )),
      'attach-html' => new BooleanField(
        array(
          'label' => $__('Inline HTML files into a <div>'),
          'hint' => $__(
            'Dangerous: While we filter/sanitize the HTML, make sure it is something you really need before turning on.'),
          'default' => FALSE
        )),
      'attach-audio' => new BooleanField(
        array(
          'label' => $__('Inline audio attachments as Players'),
          'default' => FALSE
        )),
      'attach-video' => new BooleanField(
        array(
          'label' => $__('Inline video attachments as Players'),
          'hint' => $__("Embeds video attachments "),
          'default' => FALSE
        )),
      'attach-youtube' => new BooleanField(
        array(
          'label' => $__('Inline Youtube links to Players'),
          'default' => FALSE
        )),
      'show-ms-upgrade-help' => new BooleanField(
        array(
          'label' => $__('Show IE upgrade link'),
          'hint' => $__(
            'Enable help link to abetterbrowser.org for PDFs when on Internet Explorer'),
          'default' => TRUE
        )),
      'show-initially' => new ChoiceField(
        array(
          'label' => $__('Number of attachments to show initially'),
          'default' => "ALL",
          'hint' => $__(
            'If you find too many attachments displaying at once is slowing you down, change this to only show some of them at first.'),
          'choices' => array(
            "NONE" => '0',
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
            '7' => '7',
            '8' => '8',
            '9' => '9',
            '10' => '10',
            "ALL" => $__('All') // Woo.
          )
        )),
      'newtab-links' => new BooleanField(
        array(
          'label' => $__('Open attachment links in new tab'),
          'default' => FALSE,
          'hint' => $__(
            'Rewrites links to files instructing the browser to open them in a new window/tab based on browser settings, instead of opening in the same window.')
        ))
    );
  }
}
