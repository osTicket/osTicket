<?php

if(!defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.draft.php');

class DraftAjaxAPI extends AjaxController {

    function _createDraft($vars) {
        if (false === ($vars['body'] = self::_findDraftBody($_POST)))
            return JsonDataEncoder::encode(array(
                'error' => __("Draft body not found in request"),
                'code' => 422,
                ));

        if (!($draft = Draft::create($vars)) || !$draft->save())
            Http::response(500, 'Unable to create draft');

        echo JsonDataEncoder::encode(array(
            'draft_id' => $draft->getId(),
        ));
    }

    function _getDraft($draft) {
        if (!$draft || !$draft instanceof Draft)
            Http::response(205, "Draft not found. Create one first");

        $body = Format::viewableImages($draft->getBody());

        echo JsonDataEncoder::encode(array(
            'body' => $body,
            'draft_id' => $draft->getId(),
        ));
    }

    function _updateDraft($draft) {
        if (false === ($body = self::_findDraftBody($_POST)))
            return JsonDataEncoder::encode(array(
                'error' => array(
                    'message' => "Draft body not found in request",
                    'code' => 422,
                )
            ));

        if (!$draft->setBody($body))
            return Http::response(500, "Unable to update draft body");

        echo "{}";
    }

    function _uploadInlineImage($draft) {
        global $cfg;

        if (!isset($_POST['data']) && !isset($_FILES['file']))
            Http::response(422, "File not included properly");

        # Fixup for expected multiple attachments
        if (isset($_FILES['file'])) {
            foreach ($_FILES['file'] as $k=>$v)
                $_FILES['image'][$k] = array($v);
            unset($_FILES['file']);

            $file = AttachmentFile::format($_FILES['image']);
            # Allow for data-uri uploaded files
            $fp = fopen($file[0]['tmp_name'], 'rb');
            if (fread($fp, 5) == 'data:') {
                $data = 'data:';
                while ($block = fread($fp, 8192))
                  $data .= $block;
                $file[0] = Format::parseRfc2397($data);
                list(,$ext) = explode('/', $file[0]['type'], 2);
                $file[0] += array(
                    'name' => Misc::randCode(8).'.'.$ext,
                    'size' => strlen($file[0]['data']),
                );
            }
            fclose($fp);

            # TODO: Detect unacceptable attachment extension
            # TODO: Verify content-type and check file-content to ensure image
            $type = $file[0]['type'];
            if (strpos($file[0]['type'], 'image/') !== 0)
                return Http::response(403,
                    JsonDataEncoder::encode(array(
                        'error' => 'File type is not allowed',
                    ))
                );

            # TODO: Verify file size is acceptable
            if ($file[0]['size'] > $cfg->getMaxFileSize())
                return Http::response(403,
                    JsonDataEncoder::encode(array(
                        'error' => 'File is too large',
                    ))
                );

            // Paste uploads in Chrome will have a name of 'blob'
            if ($file[0]['name'] == 'blob')
                $file[0]['name'] = 'screenshot-'.Misc::randCode(4);

            $ids = $draft->attachments->upload($file);

            if (!$ids) {
                if ($file[0]['error']) {
                    return Http::response(403,
                        JsonDataEncoder::encode(array(
                            'error' => $file[0]['error'],
                        ))
                    );
                }
                else
                    return Http::response(500, 'Unable to attach image');
            }

            $id = (is_array($ids)) ? $ids[0] : $ids;
        }
        else {
            $type = explode('/', $_POST['contentType']);
            $info = array(
                'data' => base64_decode($_POST['data']),
                'name' => Misc::randCode(10).'.'.$type[1],
                // TODO: Ensure _POST['contentType']
                'type' => $_POST['contentType'],
            );
            // TODO: Detect unacceptable filetype
            // TODO: Verify content-type and check file-content to ensure image
            $id = $draft->attachments->save($info);
        }
        if (!($f = AttachmentFile::lookup($id)))
            return Http::response(500, 'Unable to attach image');

        echo JsonDataEncoder::encode(array(
            'content_id' => 'cid:'.$f->getKey(),
            // Return draft_id to connect the auto draft creation
            'draft_id' => $draft->getId(),
            'filelink' => $f->getDownloadUrl(false, 'inline'),
        ));
    }

    // Client interface for drafts =======================================
    function createDraftClient($namespace) {
        global $thisclient;

        if (!$thisclient && substr($namespace, -12) != substr(session_id(), -12))
            Http::response(403, "Valid session required");

        $vars = array(
            'namespace' => $namespace,
        );

        return self::_createDraft($vars);
    }

    function getDraftClient($namespace) {
        global $thisclient;

        if ($thisclient) {
            try {
                $draft = Draft::lookupByNamespaceAndStaff($namespace,
                    $thisclient->getId());
            }
            catch (DoesNotExist $e) {
                Http::response(205, "Draft not found. Create one first");
            }
        }
        else {
            if (substr($namespace, -12) != substr(session_id(), -12))
                Http::response(404, "Draft not found");
            try {
                $draft = Draft::lookupByNamespaceAndStaff($namespace, 0);
            }
            catch (DoesNotExist $e) {
                Http::response(205, "Draft not found. Create one first");
            }
        }
        return self::_getDraft($draft);
    }

    function updateDraftClient($id) {
        global $thisclient;

        if (!($draft = Draft::lookup($id)))
            Http::response(205, "Draft not found. Create one first");
        // Check the owning client-id (for logged-in users), and the
        // session_id() for others
        elseif ($thisclient) {
            if ($draft->getStaffId() != $thisclient->getId())
                Http::response(404, "Draft not found");
        }
        else {
            if (substr(session_id(), -12) != substr($draft->getNamespace(), -12))
                Http::response(404, "Draft not found");
        }

        return self::_updateDraft($draft);
    }

    function deleteDraftClient($id) {
        global $thisclient;

        if (!($draft = Draft::lookup($id)))
            Http::response(205, "Draft not found. Create one first");
        elseif ($thisclient) {
            if ($draft->getStaffId() != $thisclient->getId())
                Http::response(404, "Draft not found");
        }
        else {
            if (substr(session_id(), -12) != substr($draft->getNamespace(), -12))
                Http::response(404, "Draft not found");
        }

        $draft->delete();
    }

    function uploadInlineImageClient($id) {
        global $thisclient;

        if (!($draft = Draft::lookup($id)))
            Http::response(205, "Draft not found. Create one first");
        elseif ($thisclient) {
            if ($draft->getStaffId() != $thisclient->getId())
                Http::response(404, "Draft not found");
        }
        else {
            if (substr(session_id(), -12) != substr($draft->getNamespace(), -12))
                Http::response(404, "Draft not found");
        }

        return self::_uploadInlineImage($draft);
    }

    function uploadInlineImageEarlyClient($namespace) {
        global $thisclient;

        if (!$thisclient && substr($namespace, -12) != substr(session_id(), -12))
            Http::response(403, "Valid session required");

        $draft = Draft::create(array(
            'namespace' => $namespace,
        ));
        if (!$draft->save())
            Http::response(500, 'Unable to create draft');

        return $this->uploadInlineImageClient($draft->getId());
    }

    // Staff interface for drafts ========================================
    function createDraft($namespace) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for draft creation");

        $vars = array(
            'namespace' => $namespace,
        );

        return self::_createDraft($vars);
    }

    function getDraft($namespace) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for draft creation");
        try {
            $draft = Draft::lookupByNamespaceAndStaff($namespace,
                $thisstaff->getId());
        }
        catch (DoesNotExist $e) {
            Http::response(205, "Draft not found. Create one first");
        }

        return self::_getDraft($draft);
    }

    function updateDraft($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for image upload");
        elseif (!($draft = Draft::lookup($id)))
            Http::response(205, "Draft not found. Create one first");
        elseif ($draft->getStaffId() != $thisstaff->getId())
            Http::response(404, "Draft not found");

        return self::_updateDraft($draft);
    }

    function uploadInlineImage($draft_id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for image upload");
        elseif (!($draft = Draft::lookup($draft_id)))
            Http::response(205, "Draft not found. Create one first");
        elseif ($draft->getStaffId() != $thisstaff->getId())
            Http::response(404, "Draft not found");

        return self::_uploadInlineImage($draft);
    }

    function uploadInlineImageEarly($namespace) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for image upload");

        $draft = Draft::create(array(
            'namespace' => $namespace
        ));
        if (!$draft->save())
            Http::response(500, 'Unable to create draft');

        return $this->uploadInlineImage($draft->getId());
    }

    function deleteDraft($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for draft edits");
        elseif (!($draft = Draft::lookup($id)))
            Http::response(205, "Draft not found. Create one first");
        elseif ($draft->getStaffId() != $thisstaff->getId())
            Http::response(404, "Draft not found");

        $draft->delete();
    }

    function getFileList() {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for file queries");

        if (isset($_GET['threadId']) && is_numeric($_GET['threadId'])
            && ($thread = Thread::lookup($_GET['threadId']))
            && ($object = $thread->getObject())
            && ($thisstaff->canAccess($object))
        ) {
            $union = ' UNION SELECT f.id, a.`type`, a.`name` FROM '.THREAD_TABLE.' t
                JOIN '.THREAD_ENTRY_TABLE.' th ON (th.thread_id = t.id)
                JOIN '.ATTACHMENT_TABLE.' a ON (a.object_id = th.id AND a.`type` = \'H\')
                JOIN '.FILE_TABLE.' f ON (a.file_id = f.id)
                WHERE a.`inline` = 1 AND t.id='.db_input($_GET['threadId']);
        }

        $sql = 'SELECT distinct f.id, COALESCE(a.type, f.ft), a.`name` FROM '.FILE_TABLE
            .' f LEFT JOIN '.ATTACHMENT_TABLE.' a ON (a.file_id = f.id)
            WHERE ((a.`type` IN (\'C\', \'F\', \'T\', \'P\') AND a.`inline` = 1) OR f.ft = \'L\')'
                .' AND f.`type` LIKE \'image/%\'';
        if (!($res = db_query($sql.$union)))
            Http::response(500, 'Unable to lookup files');

        $files = array();
        while (list($id, $type, $name) = db_fetch_row($res)) {
            $f = AttachmentFile::lookup((int) $id);
            $url = $f->getDownloadUrl();
            $files[] = array(
                // Don't send special sizing for thread items 'cause they
                // should be cached already by the client
                'thumb'=>$url.($type != 'H' ? '&s=128' : ''),
                'image'=>$url,
                'title'=>$name ?: $f->getName(),
            );
        }
        echo JsonDataEncoder::encode($files);
    }

    function _findDraftBody($vars) {
        if (isset($vars['name'])) {
            $parts = array();
            // Support nested `name`, like trans[lang]
            if (preg_match('`(\w+)(?:\[(\w+)\])?(?:\[(\w+)\])?`', $_POST['name'], $parts)) {
                array_shift($parts);
                $focus = $vars;
                foreach ($parts as $p)
                    $focus = $focus[$p];
                return $focus;
            }
        }
        $field_list = array('response', 'note', 'answer', 'body',
             'message', 'issue', 'description');
        foreach ($field_list as $field) {
            if (isset($vars[$field])) {
                return $vars[$field];
            }
        }

        return false;
    }

}
?>
