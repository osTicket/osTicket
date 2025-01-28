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
        'joins' => array(
            'attachments' => array(
                'constraint' => array(
                    "'D'" => 'Attachment.type',
                    'id' => 'Attachment.object_id',
                ),
                'list' => true,
                'null' => true,
                'broker' => 'GenericAttachments',
            ),
        ),
    );

    function getId() { return $this->id; }
    function getBody() { return $this->body; }
    function getStaffId() { return $this->staff_id; }
    function getNamespace() { return $this->namespace; }

    static protected function getCurrentUserId() {
        global $thisstaff, $thisclient;
        
        $user = $thisstaff ?: $thisclient;
        if ($user)
            return $user->getId();

        return 1 << 31;
    }

    static function getDraftAndDataAttrs($namespace, $id=0, $original='') {
        $draft_body = null;
        $attrs = array(sprintf('data-draft-namespace="%s"', Format::htmlchars($namespace)));
        $criteria = array(
            'namespace' => $namespace,
            'staff_id' => self::getCurrentUserId(),
        );
        if ($id) {
            $attrs[] = sprintf('data-draft-object-id="%s"', Format::htmlchars($id));
            $criteria['namespace'] .= '.' . $id;
        }
        if ($draft = static::objects()->filter($criteria)->first()) {
            $attrs[] = sprintf('data-draft-id="%s"', $draft->getId());
            $draft_body = $draft->getBody();
        }
        $attrs[] = sprintf('data-draft-original="%s"',
            Format::viewableImages($original, [], true));

        return array(Format::viewableImages($draft_body, [], true),
            implode(' ', $attrs));
    }

    static function getAttachmentIds($body=false) {
        $attachments = array();
        $body = Format::localizeInlineImages($body);
        $matches = array();
        if (preg_match_all('/"cid:([\\w.-]{32})"/', $body, $matches)) {
            $files = AttachmentFile::objects()
                ->filter(array('key__in' => $matches[1]));
            foreach ($files as $F) {
                $attachments[] = array(
                    'id' => $F->getId(),
                    'name' => $F->getName(),
                    'inline' => true
                );
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
        foreach (AttachmentFile::objects()
            ->filter(array('key__in' => $matches[1]))
            as $F
        ) {
            $this->attachments->upload($F->getId(), true);
        }
    }

    function setBody($body) {
        // Change file.php urls back to content-id's
        $body = Format::sanitize($body, false,
            // Preserve annotation information, if any
            'img=data-annotations,data-orig-annotated-image-src');

        $this->body = $body ?: ' ';
        $this->updated = SqlFunction::NOW();
        return $this->save();
    }

    function delete() {
        $this->attachments->deleteAll();
        return parent::delete();
    }

    function isValid() {
        // Required fields
        return $this->namespace && isset($this->staff_id);
    }

    function save($refetch=false) {
        if (!$this->isValid())
            return false;

        return parent::save($refetch);
    }

    static function create($vars=false) {
        $attachments = @$vars['attachments'];
        unset($vars['attachments']);

        $vars['created'] = SqlFunction::NOW();
        $vars['staff_id'] = self::getCurrentUserId();
        $draft = new static($vars);

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
    static function deleteForNamespace($namespace, $staff_id=false) {
        $attachments = Attachment::objects()
            ->filter(array('draft__namespace__startswith' => $namespace));
        if ($staff_id)
            $attachments->filter(array('draft__staff_id' => $staff_id));

        $attachments->delete();

        $criteria = array('namespace__like'=>$namespace);
        if ($staff_id)
            $criteria['staff_id'] = $staff_id;
        return static::objects()->filter($criteria)->delete();
    }

    static function cleanup() {
        // Keep drafts for two weeks (14 days)
        $sql = 'DELETE FROM '.DRAFT_TABLE
            ." WHERE (updated IS NULL AND datediff(now(), created) > 14)
                OR datediff(now(), updated) > 14";
        return db_query($sql);
    }
}

?>
