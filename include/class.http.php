<?php
/*********************************************************************
    class.http.php

    Http helper.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
class Http {

    function header_code_verbose($code) {
        switch($code):
        case 200: return '200 OK';
        case 201: return '201 Created';
        case 204: return '204 No Content';
        case 205: return '205 Reset Content';
        case 400: return '400 Bad Request';
        case 401: return '401 Unauthorized';
        case 403: return '403 Forbidden';
        case 404: return '404 Not Found';
        case 405: return '405 Method Not Allowed';
        case 416: return '416 Requested Range Not Satisfiable';
        case 422: return '422 Unprocessable Entity';
        default:  return '500 Internal Server Error';
        endswitch;
    }

    function response($code,$content,$contentType='text/html',$charset='UTF-8') {

        header('HTTP/1.1 '.Http::header_code_verbose($code));
		header('Status: '.Http::header_code_verbose($code)."\r\n");
		header("Connection: Close\r\n");
		header("Content-Type: $contentType; charset=$charset\r\n");
        header('Content-Length: '.strlen($content)."\r\n\r\n");
       	print $content;
        exit;
    }

	function redirect($url,$delay=0,$msg='') {

        if(strstr($_SERVER['SERVER_SOFTWARE'], 'IIS')){
            header("Refresh: $delay; URL=$url");
        }else{
            header("Location: $url");
        }
        exit;
    }

    function cacheable($etag, $modified, $ttl=3600) {
        // Thanks, http://stackoverflow.com/a/1583753/1025836
        $last_modified = Misc::db2gmtime($modified);
        header("Last-Modified: ".date('D, d M y H:i:s', $last_modified)." GMT", false);
        header('ETag: "'.$etag.'"');
        header("Cache-Control: private, max-age=$ttl");
        header('Expires: ' . gmdate(DATE_RFC822, time() + $ttl)." GMT");
        header('Pragma: private');
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified ||
            @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
                header("HTTP/1.1 304 Not Modified");
                exit();
        }
    }

    function download($filename, $type, $data=null) {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Type: '.$type);
        $user_agent = strtolower ($_SERVER['HTTP_USER_AGENT']);
        if (strpos($user_agent,'msie') !== false
                && strpos($user_agent,'win') !== false) {
            header('Content-Disposition: filename="'.basename($filename).'";');
        } else {
            header('Content-Disposition: attachment; filename="'
                .basename($filename).'"');
        }
        header('Content-Transfer-Encoding: binary');
        if ($data !== null) {
            header('Content-Length: '.strlen($data));
            print $data;
            exit;
        }
    }
}
?>
