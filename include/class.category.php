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

class Category extends VerySimpleModel {

    static $meta = array(
        'table' => FAQ_CATEGORY_TABLE,
        'pk' => array('category_id'),
        'ordering' => array('name'),
        'joins' => array(
            'faqs' => array(
                'reverse' => 'FAQ.category_id'
            ),
        ),
    );

    /* ------------------> Getter methods <--------------------- */
    function getId() { return $this->category_id; }
    function getName() { return $this->name; }
    function getNumFAQs() { return  $this->faqs->count(); }
    function getDescription() { return $this->description; }
    function getNotes() { return $this->notes; }
    function getCreateDate() { return $this->created; }
    function getUpdateDate() { return $this->updated; }

    function isPublic() { return $this->ispublic; }
    function getHashtable() { return $this->ht; }

    function getTopArticles() {
        return $this->faqs
            ->filter(Q::not(array('ispublished'=>0)))
            ->order_by('-ispublished', '-views')
            ->limit(5);
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
        elseif (($cid=self::findIdByName($vars['name'])) && $cid != $vars['id'])
            $errors['name'] = __('Category already exists');

        if (!$vars['description'])
            $errors['description'] = __('Category description is required');

        if ($errors)
            return false;

        /* validation only */
        if ($validation)
            return true;

        $this->ispublic = !!$vars['public'];
        $this->name = $vars['name'];
        $this->description = Format::sanitize($vars['description']);
        $this->notes = Format::sanitize($vars['notes']);

        if (!$this->save())
            return false;

        // TODO: Move FAQs if requested.

        return true;
    }

    function delete() {
        try {
            parent::delete();
            $this->faqs->expunge();
        }
        catch (OrmException $e) {
            return false;
        }
        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    /* ------------------> Static methods <--------------------- */

    static function findIdByName($name) {
        $object = self::objects()->filter(array(
            'name'=>$name
        ))->values_flat('category_id')->one();

        if ($object)
            return $object[0];
    }

    static function findByName($name) {
        return self::objects()->filter(array(
            'name'=>$name
        ))->one();
    }

    static function getFeatured() {
        return self::objects()->filter(array(
            'ispublic'=>2
        ));
    }

    static function create($vars=false) {
        $category = parent::create($vars);
        $category->created = SqlFunction::NOW();
        return $category;
    }
}
?>
