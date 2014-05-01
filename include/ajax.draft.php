<?php

if(!defined('INCLUDE_DIR')) die('!');

require_once(INCLUDE_DIR.'class.draft.php');

class DraftAjaxAPI extends AjaxController {

    function _createDraft($vars) {
        $field_list = array('response', 'note', 'answer', 'body',
             'message', 'issue');
        foreach ($field_list as $field) {
            if (isset($_POST[$field])) {
                $vars['body'] = urldecode($_POST[$field]);
                break;
            }
        }
        if (!isset($vars['body']))
            return Http::response(422, "Draft body not found in request");

        $errors = array();
        if (!($draft = Draft::create($vars, $errors)))
            Http::response(500, print_r($errors, true));

        // If the draft is created from an existing document, ensure inline
        // attachments from the cloned document are attachned to the draft
        // XXX: Actually, I think this is just wasting time, because the
        //     other object already has the items attached, so the database
        //     won't clean up the files. They don't have to be attached to
        //     the draft for Draft::getAttachmentIds to return the id of the
        //     attached file
        //$draft->syncExistingAttachments();

        echo JsonDataEncoder::encode(array(
            'draft_id' => $draft->getId(),
        ));
    }

    function _getDraft($id) {
        if (!($draft = Draft::lookup($id)))
            Http::response(205, "Draft not found. Create one first");

        $body = Format::viewableImages($draft->getBody());

        echo JsonDataEncoder::encode(array(
            'body' => $body,
            'draft_id' => (int)$id,
        ));
    }

    function _updateDraft($draft) {
        $field_list = array('response', 'note', 'answer', 'body',
             'message', 'issue');
        foreach ($field_list as $field) {
            if (isset($_POST[$field])) {
                $body = urldecode($_POST[$field]);
                break;
            }
        }
        if (!isset($body))
            return Http::response(422, "Draft body not found in request");

        if (!$draft->setBody($body))
            return Http::response(500, "Unable to update draft body");

        echo "{}";
    }

    function _uploadInlineImage($draft) {
        if (!isset($_POST['data']) && !isset($_FILES['file']))
            Http::response(422, "File not included properly");

        # Fixup for expected multiple attachments
        if (isset($_FILES['file'])) {
            foreach ($_FILES['file'] as $k=>$v)
                $_FILES['image'][$k] = array($v);
            unset($_FILES['file']);

            $file = AttachmentFile::format($_FILES['image'], true);
            # TODO: Detect unacceptable attachment extension
            # TODO: Verify content-type and check file-content to ensure image
            if (!($ids = $draft->attachments->upload($file))) {
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

            $id = $ids[0];
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
            'filelink' => sprintf('image.php?h=%s', $f->getDownloadHash())
        ));
    }

    // Client interface for drafts =======================================
    function createDraftClient($namespace) {
        global $thisclient;

        if (!$thisclient && substr($namespace, -12) != substr(session_id(), -12))
            Http::response(403, "Valid session required");

        $vars = array(
            'staff_id' => ($thisclient) ? $thisclient->getId() : 0,
            'namespace' => $namespace,
        );

        $info = self::_createDraft($vars);
        $info['draft_id'] = $namespace;
    }

    function getDraftClient($namespace) {
        global $thisclient;

        if ($thisclient) {
            if (!($id = Draft::findByNamespaceAndStaff($namespace,
                    $thisclient->getId())))
                Http::response(205, "Draft not found. Create one first");
        }
        else {
            if (substr($namespace, -12) != substr(session_id(), -12))
                Http::response(404, "Draft not found");
            elseif (!($id = Draft::findByNamespaceAndStaff($namespace, 0)))
                Http::response(205, "Draft not found. Create one first");
        }

        return self::_getDraft($id);
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

    // Staff interface for drafts ========================================
    function createDraft($namespace) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for draft creation");

        $vars = array(
            'staff_id' => $thisstaff->getId(),
            'namespace' => $namespace,
        );

        return self::_createDraft($vars);
    }

    function getDraft($namespace) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, "Login required for draft creation");
        elseif (!($id = Draft::findByNamespaceAndStaff($namespace,
                $thisstaff->getId())))
            Http::response(205, "Draft not found. Create one first");

        return self::_getDraft($id);
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

        $sql = 'SELECT distinct f.id, COALESCE(a.type, f.ft) FROM '.FILE_TABLE
            .' f LEFT JOIN '.ATTACHMENT_TABLE.' a ON (a.file_id = f.id)
            WHERE (a.`type` IN (\'C\', \'F\', \'T\', \'P\') OR f.ft = \'L\')
                AND f.`type` LIKE \'image/%\'';
        if (!($res = db_query($sql)))
            Http::response(500, 'Unable to lookup files');

        $files = array();
        $folders = array(
            'C' => 'Canned Responses',
            'F' => 'FAQ Articles',
            'T' => 'Email Templates',
            'L' => 'Logos',
            'P' => 'Pages',
        );
        while (list($id, $type) = db_fetch_row($res)) {
            $f = AttachmentFile::lookup($id);
            $url = 'image.php?h='.$f->getDownloadHash();
            $files[] = array(
                'thumb'=>$url.'&s=128',
                'image'=>$url,
                'title'=>$f->getName(),
                'folder'=>$folders[$type]
            );
        }
        echo JsonDataEncoder::encode($files);
    }

}
?>
