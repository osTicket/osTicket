<?php
/*********************************************************************
    class.category.php

    Backend support for article categories.

    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR . 'class.faq.php';

class Category extends VerySimpleModel {

    static $meta = array(
        'table' => FAQ_CATEGORY_TABLE,
        'pk' => array('category_id'),
        'ordering' => array('name'),
        'joins' => array(
            'parent' => array(
                'constraint' => array('category_pid' => 'Category.category_id'),
                'null' => true,
                ),
            'children' => array(
                'reverse' => 'Category.parent',
                ),
            'faqs' => array(
                'reverse' => 'FAQ.category'
                ),
        ),
    );

    const VISIBILITY_FEATURED = 2;
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PRIVATE = 0;

    var $_local;

    /* ------------------> Getter methods <--------------------- */
    function getId() { return $this->category_id; }
    function getName() { return $this->name; }
    function getFullName() {
        return self::getNameById($this->category_id) ?: $this->getLocalName();
    }
    function getNumFAQs($primary=false) {
        $count = $this->faqs->count();
        if (!$primary && $this->children)
            foreach ($this->children as $c)
                $count += $c->faqs->count();

        return $count;
    }
    function getDescription() { return $this->description; }
    function getDescriptionWithImages() {
        return Format::viewableImages($this->description);
    }
    function getNotes() { return $this->notes; }
    function getCreateDate() { return $this->created; }
    function getUpdateDate() { return $this->updated; }

    function isPublic() {
        return $this->ispublic != self::VISIBILITY_PRIVATE;
    }
    function getVisibilityDescription() {
        switch ($this->ispublic) {
        case self::VISIBILITY_PRIVATE:
            return __('Private');
        case self::VISIBILITY_PUBLIC:
            return __('Public');
        case self::VISIBILITY_FEATURED:
            return __('Featured');
        }
    }
    function getHashtable() { return $this->ht; }

    // Translation interface ----------------------------------
    function getTranslateTag($subtag) {
        return _H(sprintf('category.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }
    function getLocalDescriptionWithImages($lang=false) {
        return Format::viewableImages($this->_getLocal('description', $lang));
    }
    function getLocalName($lang=false) {
        return $this->_getLocal('name', $lang);
    }
    function _getLocal($what, $lang=false) {
        if (!$lang) {
            $lang = $this->getDisplayLang();
        }
        $translations = $this->getAllTranslations();
        foreach ($translations as $t) {
            if (0 === strcasecmp($lang, $t->lang)) {
                $data = $t->getComplex();
                if (isset($data[$what]))
                    return $data[$what];
            }
        }
        return $this->ht[$what];
    }
    function getAllTranslations() {
        if (!isset($this->_local)) {
            $tag = $this->getTranslateTag('c:d');
            $this->_local = CustomDataTranslation::allTranslations($tag, 'article');
        }
        return $this->_local;
    }
    function getDisplayLang() {
        if (isset($_REQUEST['kblang']))
            $lang = $_REQUEST['kblang'];
        else
            $lang = Internationalization::getCurrentLanguage();
        return $lang;
    }

    function getTopArticles() {
        return $this->faqs
            ->filter(Q::not(array('ispublished'=>0)))
            ->order_by('-ispublished')
            ->limit(5);
    }

    function getPublicSubCategories() {
        return $this->getSubCategories(array('public' => true));
    }

    function getSubCategories($criteria=array()) {

        $categories = self::objects()
            ->filter(array('category_pid' => $this->getId()));
        if (isset($criteria['public']) && $categories) {
            $categories
                ->exclude(
                    Q::any(array(
                            'ispublic'=>Category::VISIBILITY_PRIVATE,
                            'faqs__ispublished'=>FAQ::VISIBILITY_PRIVATE,
                            )))
                ->annotate(array('faq_count' => SqlAggregate::COUNT(
                                SqlCase::N()
                                ->when(array(
                                        'faqs__ispublished__gt'=> FAQ::VISIBILITY_PRIVATE), 1)
                                ->otherwise(null)
                )))
                ->filter(array('faq_count__gt'=>0));
        } else {
            $categories
                ->annotate(array(
                            'faq_count'=>SqlAggregate::COUNT('faqs')));
        }

        return $categories;
    }

    /* ------------------> Setter methods <--------------------- */
    function setName($name) { $this->name=$name; }
    function setNotes($notes) { $this->notes=$notes; }
    function setDescription($desc) { $this->description=$desc; }

    /* --------------> Database access methods <---------------- */
    function update($vars, &$errors, $validation=false) {

        // Cleanup.
        $vars['name'] = Format::striptags(trim($vars['name']));

        // Validate
        if ($vars['id'] && $this->getId() != $vars['id'])
            $errors['err'] = __('Internal error occurred');

        if (!$vars['name'])
            $errors['name'] = __('Category name is required');
        elseif (strlen($vars['name']) < 3)
            $errors['name'] = __('Name is too short. 3 chars minimum');
        elseif (($cid=self::findIdByName($vars['name'], $vars['pid'])) && $cid != $vars['id'])
            $errors['name'] = __('Category already exists');

        if (!$vars['description'])
            $errors['description'] = __('Category description is required');

        if ($errors)
            return false;

        /* validation only */
        if ($validation)
            return true;

        $this->ispublic = $vars['ispublic'];
        $this->name = $vars['name'];
        $this->category_pid = $vars['pid'] ?: 0;
        $this->description = Format::sanitize($vars['description']);
        $this->notes = Format::sanitize($vars['notes']);

        if (!$this->save())
            return false;

        if (isset($vars['trans']) && !$this->saveTranslations($vars))
            return false;

        // TODO: Move FAQs if requested.

        return true;
    }

    function delete() {
        try {
            parent::delete();
        }
        catch (OrmException $e) {
            return false;
        }
        $type = array('type' => 'deleted');
        Signal::send('object.deleted', $this, $type);

        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function saveTranslations($vars) {
        global $thisstaff;

        foreach ($this->getAllTranslations() as $t) {
            $trans = @$vars['trans'][$t->lang];
            if (!$trans || !array_filter($trans))
                // Not updating translations
                continue;

            // Content is not new and shouldn't be added below
            unset($vars['trans'][$t->lang]);
            $content = array('name' => $trans['name'],
                'description' => Format::sanitize($trans['description']));

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
        $tag = $this->getTranslateTag('c:d');
        foreach ($vars['trans'] as $lang=>$parts) {
            $content = array('name' => @$parts['name'],
                'description' => Format::sanitize(@$parts['description']));
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


    /* ------------------> Static methods <--------------------- */

    static function findIdByName($name, $pid=null) {
        $row = self::objects()->filter(array(
            'name'=>$name,
            'category_pid'  => $pid ?: null
        ))->values_flat('category_id')->first();

        return ($row) ? $row[0] : null;
    }

    static function findByName($name, $pid=null) {
        return self::objects()->filter(array(
            'name'=>$name,
            'category_pid'  => $pid ?: null
        ))->one();
    }

    static function getNameById($id) {
        $names = static::getCategories();
        return $names[$id] ?: '';
    }

    static function getFeatured() {
        return self::objects()->filter(array(
            'ispublic'=>self::VISIBILITY_FEATURED
        ));
    }

    static function getCategories($criteria=null, $localize=true) {
        static $categories = null;

        if (!isset($categories) || $criteria) {
            $categories = array();
            $query = self::objects();
            $query->order_by('name')
                ->values('category_id', 'category_pid', 'name', 'parent');

            foreach ($query as $row)
                $categories[$row['category_id']] = $row;

            // Resolve parent names
            $names = array();
            foreach ($categories as $id=>$info) {
                $name = $info['name'];
                $loop = array($id=>true);
                $parent = false;
                while ($info['category_pid'] && ($info = $categories[$info['category_pid']])) {
                    $name = sprintf('%s / %s', $info['name'], $name);
                    if (isset($loop[$info['category_pid']]))
                        break;
                    $loop[$info['category_pid']] = true;
                    $parent = $info;
                }
                // TODO: localize category names
                $names[$id] = $name;
            }
            asort($names);

            if ($criteria)
                return $names;

            $categories = $names;
        }

        return $categories;
    }

    static function create($vars=false) {
        $category = new static($vars);
        $category->created = SqlFunction::NOW();
        return $category;
    }
}
?>
