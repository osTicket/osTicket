<?php
/*********************************************************************
    pages/index.php

    Custom pages servlet

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
@chdir(dirname(__file__).'/../');

require_once('client.inc.php');
require_once(INCLUDE_DIR.'class.format.php');
require_once(INCLUDE_DIR.'class.page.php');

// Determine the requested page
// - Strip extension
$slug = Format::slugify($ost->get_path_info());

// Get the part before the first dash
$first_word = explode('-', $slug);
$first_word = $first_word[0];

$pages = Page::objects()->filter(array(
    'name__like' => "$first_word%"
));

$selected_page = null;
foreach ($pages as $P) {
    if (Format::slugify($P->name) == $slug) {
        $selected_page = $P;
        break;
    }
}

if (!$selected_page)
    Http::response(404, __('Page Not Found'));

if (!$selected_page->isActive() || $selected_page->getType() != 'other')
    Http::response(404, __('Page Not Found'));

require(CLIENTINC_DIR.'header.inc.php');

$BUTTONS = false;
include CLIENTINC_DIR.'templates/sidebar.tmpl.php';
?>
<div class="main-content">
<?php
print $selected_page->getBodyWithImages();
?>
</div>

<?php
require(CLIENTINC_DIR.'footer.inc.php');
?>
