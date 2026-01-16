<?php
/*********************************************************************
    class.page.php

    Page class

    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class Page extends VerySimpleModel {

    static $meta = array(
        'table' => PAGE_TABLE,
        'pk' => array('id'),
        'ordering' => array('name'),
        'defer' => array('body'),
        'joins' => array(
            'topics' => array(
                'reverse' => 'Topic.page',
            ),
            'attachments' => array(
                'constraint' => array(
                    "'P'" => 'Attachment.type',
                    'id' => 'Attachment.object_id',
                ),
                'list' => true,
                'null' => true,
                'broker' => 'GenericAttachments',
            ),
        ),
    );

    var $_local;

    function getId() {
        return $this->id;
    }

    function getHashtable() {
        $base = $this->ht;
        unset($base['topics']);
        unset($base['attachments']);
        return $base;
    }

    function getType() {
        return $this->type;
    }

    function getName() {
        return $this->name;
    }
    function getLocalName($lang=false) {
        return $this->_getLocal('name', $lang);
    }
    function getNameAsSlug() {
        return urlencode(Format::slugify($this->name));
    }

    function getBody() {
        return $this->body;
    }
    function getLocalBody($lang=false) {
        return $this->_getLocal('body', $lang);
    }
    function getBodyWithImages() {
        return Format::viewableImages($this->getLocalBody(), ['type' => 'P']);
    }

    function _getLocal($what, $lang=false) {
        if (!$lang) {
            $lang = Internationalization::getCurrentLanguage();
        }
        $translations = $this->getAllTranslations();
        foreach ($translations as $t) {
            if ($lang == $t->lang) {
                $data = $t->getComplex();
                if (isset($data[$what]))
                    return $data[$what];
            }
        }
        return $this->ht[$what];
    }

    function getAllTranslations() {
        if (!isset($this->_local)) {
            $tag = $this->getTranslateTag('name:body');
            $this->_local = CustomDataTranslation::allTranslations($tag, 'article');
        }
        return $this->_local;
    }

    function getNotes() {
        return $this->notes;
    }

    function isActive() {
        return ($this->isactive);
    }

    function isInUse() {
        global $cfg;

        return  ($this->getNumTopics()
                    || in_array($this->getId(), $cfg->getDefaultPages()));
    }


    function getCreateDate() {
        return $this->created;
    }

    function getUpdateDate() {
        return $this->updated;
    }

    function getNumTopics() {
        return $this->topics->count();
    }

    function getTranslateTag($subtag) {
        return _H(sprintf('page.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }

    function disable() {

        if(!$this->isActive())
            return true;

        if($this->isInUse())
            return false;


        $this->isactive = 0;
        return $this->save();
    }

    function delete() {

        if ($this->isInUse())
            return false;

        if (!parent::delete())
            return false;

        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);
        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    /* ------------------> Static methods <--------------------- */

    static function create($vars=false) {
        $page = new static($vars);
        $page->created = SqlFunction::NOW();
        return $page;
    }

    function add($vars, &$errors) {
        if(!($id=self::create($vars, $errors)))
            return false;

        return self::lookup($id);
    }

    static function getPages($criteria=array()) {
        $pages = self::objects();
        if(isset($criteria['active']))
            $pages = $pages->filter(array('isactive'=>$criteria['active']));
        if(isset($criteria['type']))
            $pages = $pages->filter(array('type'=>$criteria['type']));

        return $pages;
    }

    static function getActivePages($criteria=array()) {

        $criteria = array_merge($criteria, array('active'=>true));

        return self::getPages($criteria);
    }

    static function getActiveThankYouPages() {
        return self::getActivePages(array('type' => 'thank-you'));
    }

    static function lookup($id, $lang=false) {
        try {
            $qs = self::objects()->filter(is_array($id) ? $id : array('id'=>$id));
            if ($lang)
                $qs = $qs->filter(array('lang'=>$lang));
            return $qs->one();
        }
        catch (DoesNotExist $ex) {
            return null;
        }
    }

    static function getIdByName($name, $lang=false) {
        try {
            $qs = self::objects()->filter(array('name'=>$name))
                ->values_flat('id');
            list($id) = $qs->one();
            return $id;
        }
        catch (DoesNotExist $ex) {
            return null;
        }
        catch (InconsistentModelException $ex) {
            // This largely happens on upgrades, and may specifically cause
            // the staff login box to crash
            return null;
        }
    }

    static function lookupByType($type, $lang=false) {
        try {
            return self::objects()->filter(array('type'=>$type))->one();
        }
        catch (DoesNotExist | InconsistentModelException $ex) {
            return null;
        }
    }

    function update($vars, &$errors, $allowempty=false) {
        //Cleanup.
        $vars['name']=Format::striptags(trim($vars['name']));

        //validate
        if (isset($this->id) && !$vars['isactive'] && $this->isInUse()) {
            $errors['err'] = __('A page currently in-use CANNOT be disabled!');
            $errors['isactive'] = __('Page is in-use!');
        }

        if (isset($this->id) && $this->getId() != $vars['id'])
            $errors['err'] = sprintf('%s - %s', __('Internal error occurred'), __('Please try again!'));

        if(!$vars['type'])
            $errors['type'] = __('Type is required');

        if(!$vars['name'])
            $errors['name'] = __('Name is required');
        elseif(($pid=self::getIdByName($vars['name'])) && $pid!=$this->getId())
            $errors['name'] = __('Name already exists');

        if(!$vars['body'] && !$allowempty)
            $errors['body'] = __('Page body is required');

        if($errors) return false;

        $this->type = $vars['type'];
        $this->name = $vars['name'];
        $this->body = Format::sanitize($vars['body']);
        $this->isactive = (bool) $vars['isactive'];
        $this->notes = Format::sanitize($vars['notes']);

        $isnew = !isset($this->id);
        $rv = $this->save();
        if (!$isnew)
            $rv = $this->saveTranslations($vars, $errors);

        // Attach inline attachments from the editor
        $keepers = Draft::getAttachmentIds($vars['body']);
        $keepers = array_flip(array_map(function($i) { return $i['id']; }, $keepers));
        $this->attachments->keepOnlyFileIds($keepers, true);

        if ($rv)
            return $rv;

        $errors['err']=sprintf(__('Unable to update %s.'), __('this site page'));
        return false;
    }

    function saveTranslations($vars, &$errors) {
        global $thisstaff;

        $tag = $this->getTranslateTag('name:body');
        $translations = CustomDataTranslation::allTranslations($tag, 'article');
        foreach ($translations as $t) {
            $title = @$vars['trans'][$t->lang]['title'];
            $body = @$vars['trans'][$t->lang]['body'];
            if (!$title && !$body)
                continue;

            // Content is not new and shouldn't be added below
            unset($vars['trans'][$t->lang]['title']);
            unset($vars['trans'][$t->lang]['body']);
            $content = array('name' => $title, 'body' => Format::sanitize($body));

            // Don't update content which wasn't updated
            if ($content == $t->getComplex())
                continue;

            $t->text = $content;
            $t->agent_id = $thisstaff->getId();
            $t->updated = SqlFunction::NOW();
            if (!$t->save())
                return false;
        }
        // New translations (?)
        foreach ($vars['trans'] ?: array() as $lang=>$parts) {
            $content = array('name' => @$parts['title'], 'body' => Format::sanitize(@$parts['body']));
            if (!array_filter($content))
                continue;
            $t = CustomDataTranslation::create(array(
                'type'      => 'article',
                'object_hash' => $tag,
                'lang'      => $lang,
                'text'      => $content,
                'revision'  => 1,
                'agent_id'  => $thisstaff->getId(),
                'updated'   => SqlFunction::NOW(),
            ));
            if (!$t->save())
                return false;
        }
        return true;
    }

    static function getContext($type) {
        $context = array(
        'thank-you' => array('ticket'),
        'registration-staff' => array(
            // 'token' => __('Special authentication token'),
            'staff' => array('class' => 'Staff', 'desc' => __('Message recipient')),
            'recipient' => array('class' => 'Staff', 'desc' => __('Message recipient')),
            'link',
        ),
        'pwreset-staff' => array(
            'staff' => array('class' => 'Staff', 'desc' => __('Message recipient')),
            'recipient' => array('class' => 'Staff', 'desc' => __('Message recipient')),
            'link',
        ),
        'registration-client' => array(
            // 'token' => __('Special authentication token'),
            'recipient' => array('class' => 'User', 'desc' => __('Message recipient')),
            'link', 'user',
        ),
        'pwreset-client' => array(
            'recipient' => array('class' => 'User', 'desc' => __('Message recipient')),
            'link', 'user',
        ),
        'access-link' => array('ticket', 'recipient'),
        );

        return $context[$type];
    }
}
?>
