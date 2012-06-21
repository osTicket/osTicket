<?php
/*********************************************************************
    class.csrf.php

    Provides mechanisms to protect against cross-site request forgery
    attacks. This is accomplished by using a token that is not stored in a
    cookie, but required to make changes to the system.

    This can be accomplished by emitting a hidden field in a form, or
    sending a separate header (X-CSRFToken) when forms are submitted.

    This technique is based on the protection mechanism in the Django
    project, detailed at and thanks to
    https://docs.djangoproject.com/en/dev/ref/contrib/csrf/.

    Jared Hancock 
    Copyright (c)  2006-2012 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

function csrf_token() {
    ?>
    <input type="hidden" name="__CSRFToken__" value="<?php
        echo csrf_get_token(); ?>" />
    <?php
}

function csrf_get_token($length=32) {
    if (!isset($_SESSION['CSRFToken'])) {
        for ($i = 0; $i <= $length; $i++)
            $r .= chr(mt_rand(0, 255));
        $_SESSION['CSRFToken'] = base64_encode($r);
    }
    return $_SESSION['CSRFToken'];
}

function csrf_ensure_cookie() {
    global $csrf_unprotected;
    if ($csrf_unprotected)
        return true;

    $token = csrf_get_token();
    if (isset($_POST['__CSRFToken__'])) {
        if ($token == $_POST['__CSRFToken__'])
            return true;
    }
    elseif (isset($_SERVER['HTTP_X_CSRFTOKEN'])) {
        if ($token == $_SERVER['HTTP_X_CSRFTOKEN'])
            return true;
    }
    Http::response(400, 'CSRF Token Required');
}

function csrf_unprotect() {
    global $csrf_unprotected;
    $csrf_unprotected = true;
}

# Many thanks to https://docs.djangoproject.com/en/dev/ref/contrib/csrf/
function csrf_enable_ajax() { ?>
<script type="text/javascript">
jQuery(document).ajaxSend(function(event, xhr, settings) {
    function sameOrigin(url) {
        // url could be relative or scheme relative or absolute
        var host = document.location.host; // host + port
        var protocol = document.location.protocol;
        var sr_origin = '//' + host;
        var origin = protocol + sr_origin;
        // Allow absolute or scheme relative URLs to same origin
        return (url == origin || url.slice(0, origin.length + 1) == origin + '/') ||
            (url == sr_origin || url.slice(0, sr_origin.length + 1) == sr_origin + '/') ||
            // or any other URL that isn't scheme relative or absolute i.e
            // relative.
            !(/^(\/\/|http:|https:).*/.test(url));
    }
    function safeMethod(method) {
        return (/^(GET|HEAD|OPTIONS|TRACE)$/.test(method));
    }
    if (!safeMethod(settings.type) && sameOrigin(settings.url)) {
        xhr.setRequestHeader("X-CSRFToken", "<?php echo csrf_get_token(); ?>");
    }
});
</script>
<?php }

?>
