# Attachment Preview
An [osTicket](https://github.com/osTicket/osTicket) plugin allowing inlining of Attachments, works with PHP5.3+ and osTicket 1.9+ 

[![Build Status](https://travis-ci.org/clonemeagain/attachment_preview.svg?branch=master)](https://travis-ci.org/clonemeagain/attachment_preview)

#How it looks:
![agent_view](https://cloud.githubusercontent.com/assets/5077391/15166401/bedd01fc-1761-11e6-8814-178c7d4efc03.png)

## Current features:
- PDF Files attachments are embedded as full PDF `<object>` in the entry. The number to automatically show is configurable, and if you tick the box `Hide Seen` in the config, then if you go back, your browser remembers you've already seen that attachment and doesn't show it again, instead replacing the embedded PDF with a Toggle button. You can get toggle buttons for everything by setting the `Number of attachments to show initially` option to 0. 
- Images inserted as normal `<img>` tags. Supported by most browsers: `png,jpg,gif,svg,bmp`
- Text files attachments are inserted into using `<pre>` (If enabled). 
- HTML files are filtered and inserted (If enabled). 
- Youtube links in comments can be used to create embedded `<iframe>` players. (if enabled)
- HTML5 Compatible video formats attached are embedded as `<video>` players. (if enabled)
- All modifications to the DOM are now performed on the server, however the bulk of the work is in Javascript.
- Admin can choose the types of attachments to inline, and who can inline them.
- Plugin API: allows other plugins to manipulate the DOM without adding race conditions or multiple re-parses.

## To Install:
1. Simply `git clone https://github.com/clonemeagain/attachment_preview.git /include/plugins/attachment_preview` Or extract [latest-zip](https://github.com/clonemeagain/attachment_preview/archive/master.zip) into /include/plugins/attachment_preview
1. Navigate to: https://your.domain/support/scp/plugins.php?a=add to add a new plugin.
1. Click "Install" next to "Attachment Inline Plugin"
1. Now the plugin needs to be enabled & configured, so you should be seeing the list of currently installed plugins, pick the checkbox next to "Attachment Inline Plugin" and select the "Enable" button.
1. Now you can configure the plugin, click the link "Attachment Inline Plugin" and choose who the plugin should be activated for, or keep the default.

If you don't already have a PDF program on your computer, Chrome will still work (it does that for you), for Firefox you will need to enable it, eg: [enable pdf preview in firefoox](https://support.mozilla.org/en-US/kb/change-firefox-behavior-when-open-file) and set "Preview in Firefox"

## To Remove:
Navigate to admin plugins view, click the checkbox and push the "Delete" button.

The plugin will still be available, you have deleted the config only at this point, to remove after deleting, remove the /plugins/attachment_preview folder.


# How it works:
Latest in [Wiki](https://github.com/clonemeagain/attachment_preview/wiki)

* Essentially it's simple, when enabled, and a ticket page is viewed, an output buffer is created which waits for the page to be finished rendering by osTicket. (Using php's register_shutdown_function & ob_start)
* The plugin then uses a DOMDocument and adds a new DOMElement after each attachment, inlining them. PDF's become `<object>`'s, PNG's become `<img>` etc. It also injects the plugin's stylesheet and script into the correct places of the page, so they execute once and don't break anything else.

The plugin has several administratively configurable options, including, but not limited to:
* What to inline (PDF/Image/Youtube/Text/HTML etc)
* The maximum size of attachments to inline.
* How many to inline, attachments after that are still inlineable, but the Agent has to press a "Show Attachment" button (translateable). 
* We now support changing the original attachment link into a "New Tab", so there is an option for that.
* If the browser should remember the attachments it's seen, and if so, for how long.

The plugin is completely self-contained, so there are ZERO MODS to core required to get it working. 
You simply clone the repo or download the zip from github and extract into /include/plugins/ which should make a folder: "attachment_preview", but it could be called anything. 


## Note on CI
The travis-ci tests use phpunit, and are configured for PHP 7, therefore I no longer test on PHP 5.4/5.6 etc as the automated tests would break, and Travis gives me testing for free, so I don't want to waste their time running every possible combo of PHP/osTicket. The setup currently tests the plugin using the two most recent versions of osTicket (1.10.5 & 1.11) using PHP 7.1 & 7.3.

# TODO:
- Your suggestions/feedback? [Let me know via the Issue Queue above!](https://github.com/clonemeagain/attachment_preview/issues/new)
