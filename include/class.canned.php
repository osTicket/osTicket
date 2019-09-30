<?php
/*********************************************************************
    class.canned.php

    Canned Responses AKA Premade replies

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
include_once(INCLUDE_DIR.'class.file.php');

class Canned
extends VerySimpleModel {
    static $meta = array(
        'table' => CANNED_TABLE,
        'pk' => array('canned_id'),
        'joins' => array(
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
                'null' => true,
            ),
            'attachments' => array(
                'constraint' => array(
                    "'C'" => 'Attachment.type',
                    'canned_id' => 'Attachment.object_id',
                ),
                'list' => true,
                'null' => true,
                'broker' => 'GenericAttachments',
            ),
        ),
    );

    const PERM_MANAGE = 'canned.manage';

    static protected $perms = array(
            self::PERM_MANAGE => array(
                'title' =>
                /* @trans */ 'Premade',
                'desc'  =>
                /* @trans */ 'Ability to add/update/disable/delete canned responses')
    );

    static function getPermissions() {
        return self::$perms;
    }

    function getId(){
        return $this->canned_id;
    }

    function isEnabled() {
         return $this->isenabled;
    }

    function isActive(){
        return $this->isEnabled();
    }

    function getFilters() {

        if (!isset($this->_filters)) {
            $this->_filters = array();
            $cid = sprintf('"canned_id":%d', $this->getId());
            $sql='SELECT filter.id, filter.name '
                .' FROM '.FILTER_TABLE.' filter'
                .' INNER JOIN '.FILTER_ACTION_TABLE.' action'
                .'  ON (filter.id=action.filter_id)'
                .' WHERE action.type="canned"'
                ."  AND action.configuration LIKE '%$cid%'";

            if (($res=db_query($sql)) && db_num_rows($res))
                while (list($id, $name) = db_fetch_row($res))
                    $this->_filters[$id] = $name;
        }

        return $this->_filters;
    }

    function getAttachedFiles($inlines=false) {
        return AttachmentFile::objects()
            ->filter(array(
                'attachments__type'=>'C',
                'attachments__object_id'=>$this->getId(),
                'attachments__inline' => $inlines,
            ));
    }

    function getNumFilters() {
        return count($this->getFilters());
    }

    function getTitle() {
        return $this->title;
    }

    function getResponse() {
        return $this->response;
    }
    function getResponseWithImages() {
        return Format::viewableImages($this->getResponse());
    }

    function getReply() {
        return $this->getResponse();
    }

    function getHtml() {
        return $this->getFormattedResponse('html');
    }

    function getPlainText() {
        return $this->getFormattedResponse('text.plain');
    }

    function getFormattedResponse($format='text', $cb=null) {

        $resp = array();
        $html = true;
        switch($format) {
            case 'json.plain':
                $html = false;
                // fall-through
            case 'json':
                $resp['id'] = $this->getId();
                $resp['title'] = $this->getTitle();
                $resp['response'] = $this->getResponseWithImages();

                // Callback to strip or replace variables!
                if ($cb && is_callable($cb))
                    $resp = $cb($resp);

                $resp['files'] = array();
                foreach ($this->getAttachedFiles(!$html) as $file) {
                    $_SESSION[':cannedFiles'][$file->id] = $file->name;
                    $resp['files'][] = array(
                        'id' => $file->id,
                        'name' => $file->name,
                        'size' => $file->size,
                        'download_url' => $file->getDownloadUrl(),
                    );
                }
                // strip html
                if (!$html) {
                    $resp['response'] = Format::html2text($resp['response'], 90);
                }

                return Format::json_encode($resp);
                break;
            case 'html':
            case 'text.html':
                $response = $this->getResponseWithImages();
                break;
            case 'text.plain':
                $html = false;
            case 'text':
            default:
                $response = $this->getResponse();
                if (!$html)
                    $response = Format::html2text($response, 90);
                break;
        }

        // Callback to strip or replace variables!
        if ($response && $cb && is_callable($cb))
            $response = $cb($response);

        return $response;
    }

    function getNotes() {
        return $this->ht['notes'];
    }

    function getDeptId(){
        return $this->ht['dept_id'];
    }

    function getHashtable() {
        $base = $this->ht;
        unset($base['attachments']);
        return $base;
    }

    function getInfo() {
        return $this->getHashtable();
    }

    function getNumAttachments() {
        return $this->attachments->count();
    }

    function delete(){
        if ($this->getNumFilters() > 0)
            return false;

        if (!parent::delete())
            return false;

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        $this->attachments->deleteAll();

        return true;
    }

    /*** Static functions ***/

    static function create($vars=false) {
        $faq = new static($vars);
        $faq->created = SqlFunction::NOW();
        return $faq;
    }

    static function getIdByTitle($title) {
        $row = static::objects()
            ->filter(array('title' => $title))
            ->values_flat('canned_id')
            ->first();

        return $row ? $row[0] : null;
    }

    static function getCannedResponses($deptId=0, $explicit=false) {
        $canned = static::objects()
            ->filter(array('isenabled' => true))
            ->order_by('title')
            ->values_flat('canned_id', 'title');

        if ($deptId) {
            $depts = array($deptId);
            if (!$explicit)
                $depts[] = 0;
            $canned->filter(array('dept_id__in' => $depts));
        }

        $responses = array();
        foreach ($canned as $row) {
            list($id, $title) = $row;
            $responses[$id] = $title;
        }

        return $responses;
    }

    function responsesByDeptId($deptId, $explicit=false) {
        return self::getCannedResponses($deptId, $explicit);
    }

    function save($refetch=false) {
        if ($this->dirty || $refetch)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars,&$errors) {
        global $cfg;

        $vars['title'] = Format::striptags(trim($vars['title']));

        $id = isset($this->canned_id) ? $this->canned_id : null;
        if ($id && $id != $vars['id'])
            $errors['err']=sprintf('%s - %s', __('Internal error occurred'), __('Please try again!'));

        if (!$vars['title'])
            $errors['title'] = __('Title required');
        elseif (strlen($vars['title']) < 3)
            $errors['title'] = __('Title is too short. 3 chars minimum');
        elseif (($cid=self::getIdByTitle($vars['title'])) && $cid!=$id)
            $errors['title'] = __('Title already exists');

        if (!$vars['response'])
            $errors['response'] = __('Response text is required');

        if ($errors)
            return false;

        $this->dept_id = $vars['dept_id'] ?: 0;
        $this->isenabled = $vars['isenabled'];
        $this->title = $vars['title'];
        $this->response = Format::sanitize($vars['response']);
        $this->notes = Format::sanitize($vars['notes']);

        $isnew = !isset($id);
        if ($this->save())
            return true;

        if ($isnew)
            $errors['err'] = sprintf(__('Unable to update %s.'), __('this canned response'));
        else
            $errors['err']=sprintf(__('Unable to create %s.'), __('this canned response'))
               .' '.__('Internal error occurred');

        return true;
    }
}
RolePermission::register( /* @trans */ 'Knowledgebase', Canned::getPermissions());

?>
