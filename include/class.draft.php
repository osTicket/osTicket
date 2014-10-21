<?php

class Draft {

    var $id;
    var $ht;

    var $_attachments;

    function Draft($id) {
        $this->id = $id;
        $this->load();
    }

    function load() {
        $this->attachments = new GenericAttachments($this->id, 'D');
        $sql = 'SELECT * FROM '.DRAFT_TABLE.' WHERE id='.db_input($this->id);
        return (($res = db_query($sql))
            && ($this->ht = db_fetch_array($res)));
    }

    function getId() { return $this->id; }
    function getBody() { return $this->ht['body']; }
    function getStaffId() { return $this->ht['staff_id']; }
    function getNamespace() { return $this->ht['namespace']; }

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
        $this->ht['body'] = $body;

        $sql='UPDATE '.DRAFT_TABLE.' SET updated=NOW()'
            .',body='.db_input($body)
            .' WHERE id='.db_input($this->getId());
        return db_query($sql) && db_affected_rows() == 1;
    }

    function delete() {
        $this->attachments->deleteAll();
        $sql = 'DELETE FROM '.DRAFT_TABLE
            .' WHERE id='.db_input($this->getId());
        return (db_query($sql) && db_affected_rows() == 1);
    }

    function save($id, $vars, &$errors) {
        // Required fields
        if (!$vars['namespace'] || !isset($vars['body']) || !isset($vars['staff_id']))
            return false;

        $sql = ' SET `namespace`='.db_input($vars['namespace'])
            .' ,body='.db_input(Format::sanitize($vars['body'], false))
            .' ,staff_id='.db_input($vars['staff_id']);

        if (!$id) {
            $sql = 'INSERT INTO '.DRAFT_TABLE.$sql
                .' ,created=NOW()';
            if(!db_query($sql) || !($draft=self::lookup(db_insert_id())))
                return false;

            // Cloned attachments...
            if($vars['attachments'] && is_array($vars['attachments']))
                $draft->attachments->upload($vars['attachments'], true);

            return $draft;
        }
        else {
            $sql = 'UPDATE '.DRAFT_TABLE.$sql
                .' WHERE id='.db_input($id);
            if (db_query($sql) && db_affected_rows() == 1)
                return $this;
        }
    }

    function create($vars, &$errors) {
        return self::save(0, $vars, $errors);
    }

    function lookup($id) {
        return ($id && is_numeric($id)
                && ($d = new Draft($id))
                && $d->getId()==$id
                ) ? $d : null;
    }

    function findByNamespaceAndStaff($namespace, $staff_id) {
        $sql = 'SELECT id FROM '.DRAFT_TABLE
            .' WHERE `namespace`='.db_input($namespace)
            .' AND staff_id='.db_input($staff_id);
        if (($res = db_query($sql)) && (list($id) = db_fetch_row($res)))
            return $id;
        else
            return false;
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
