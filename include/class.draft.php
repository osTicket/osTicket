<?php

/**
 * Class: Draft
 *
 * Defines a simple draft-saving mechanism for osTicket which supports draft
 * fetch and update via an ajax mechanism (include/ajax.draft.php).
 *
 * Fields:
 * id - (int:auto:pk) Draft ID number
 * body - (text) Body of the draft
 * namespace - (string) Identifier of draft grouping — useful for multiple
 *      drafts on the same document by different users
 * staff_id - (int:null) Staff owner of the draft
 * extra - (text:json) Extra attributes of the draft
 * created - (date) Date draft was initially created
 * updated - (date:null) Date draft was last updated
 */
class Draft extends VerySimpleModel {

    static $meta = array(
        'table' => DRAFT_TABLE,
        'pk' => array('id'),
    );

    var $attachments;

    function __construct() {
        call_user_func_array(array('parent', '__construct'), func_get_args());
        if (isset($this->id))
            $this->attachments = new GenericAttachments($this->id, 'D');
    }

    function getId() { return $this->id; }
    function getBody() { return $this->body; }
    function getStaffId() { return $this->staff_id; }
    function getNamespace() { return $this->namespace; }

    static function getDraftAndDataAttrs($namespace, $id=0, $original='') {
        $draft_body = null;
        $attrs = array(sprintf('data-draft-namespace="%s"', $namespace));
        $criteria = array('namespace'=>$namespace);
        if ($id) {
            $attrs[] = sprintf('data-draft-object-id="%s"', $id);
            $criteria['namespace'] .= '.' . $id;
        }
        if ($draft = static::lookup($criteria)) {
            $attrs[] = sprintf('data-draft-id="%s"', $draft->getId());
            $draft_body = $draft->getBody();
        }
        $attrs[] = sprintf('data-draft-original="%s"', Format::htmlchars($original));

        return array($draft_body, implode(' ', $attrs));
    }

    function getAttachmentIds($body=false) {
        $attachments = array();
        if (!$body)
            $body = $this->getBody();
        $body = Format::localizeInlineImages($body);
        $matches = array();
        if (preg_match_all('/"cid:([\\w.-]{32})"/', $body, $matches)) {
            foreach ($matches[1] as $hash) {
                if ($file_id = AttachmentFile::getIdByHash($hash))
                    $attachments[] = array(
                            'id' => $file_id,
                            'inline' => true);
            }
        }
        return $attachments;
    }

    /*
     * Ensures that the inline attachments cited in the body of this draft
     * are also listed in the draft_attachment table. After calling this,
     * the ::getAttachments() function should correctly return all inline
     * attachments. This function should be called after creating a draft
     * with an existing body
     */
    function syncExistingAttachments() {
        $matches = array();
        if (!preg_match_all('/"cid:([\\w.-]{32})"/', $this->getBody(), $matches))
            return;

        // Purge current attachments
        $this->attachments->deleteInlines();
        foreach ($matches[1] as $hash)
            if ($file = AttachmentFile::getIdByHash($hash))
                $this->attachments->upload($file, true);
    }

    function setBody($body) {
        // Change image.php urls back to content-id's
        $body = Format::sanitize($body, false);

        $this->body = $body;
        $this->updated = SqlFunction::NOW();
        return $this->save();
    }

    function delete() {
        $this->attachments->deleteAll();
        return parent::delete();
    }

    function isValid() {
        // Required fields
        return $this->namespace && isset($this->body) && isset($this->staff_id);
    }

    function save($refetch=false) {
        if (!$this->isValid())
            return false;

        return parent::save($refetch);
    }

    static function create($vars) {
        $attachments = @$vars['attachments'];
        unset($vars['attachments']);

        $vars['created'] = SqlFunction::NOW();
        $draft = parent::create($vars);

        // Cloned attachments ...
        if (false && $attachments && is_array($attachments))
            // XXX: This won't work until the draft is saved
            $draft->attachments->upload($attachments, true);

        return $draft;
    }

    static function lookupByNamespaceAndStaff($namespace, $staff_id) {
        return static::lookup(array(
            'namespace'=>$namespace,
            'staff_id'=>$staff_id
        ));
    }

    /**
     * Delete drafts saved for a particular namespace. If the staff_id is
     * specified, only drafts owned by that staff are deleted. Usually, if
     * closing a ticket, the staff_id should be left null so that all drafts
     * are cleaned up.
     */
    /* static */
    function deleteForNamespace($namespace, $staff_id=false) {
        $sql = 'DELETE attach FROM '.ATTACHMENT_TABLE.' attach
                INNER JOIN '.DRAFT_TABLE.' draft
                ON (attach.object_id = draft.id AND attach.`type`=\'D\')
                WHERE draft.`namespace` LIKE '.db_input($namespace);
        if ($staff_id)
            $sql .= ' AND draft.staff_id='.db_input($staff_id);
        if (!db_query($sql))
            return false;

        $sql = 'DELETE FROM '.DRAFT_TABLE
             .' WHERE `namespace` LIKE '.db_input($namespace);
        if ($staff_id)
            $sql .= ' AND staff_id='.db_input($staff_id);
        return (!db_query($sql) || !db_affected_rows());
    }

    static function cleanup() {
        // Keep client drafts for two weeks (14 days)
        $sql = 'DELETE FROM '.DRAFT_TABLE
            ." WHERE `namespace` LIKE 'ticket.client.%'
            AND ((updated IS NULL AND datediff(now(), created) > 14)
                OR datediff(now(), updated) > 14)";
        return db_query($sql);
    }
}

?>
