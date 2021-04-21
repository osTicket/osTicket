<?php
/*********************************************************************
    class.faq.php

    Backend support for article creates, edits, deletes, and attachments.

    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once('class.file.php');
require_once('class.category.php');
require_once('class.thread.php');

class FAQ extends VerySimpleModel {

    static $meta = array(
        'table' => FAQ_TABLE,
        'pk' => array('faq_id'),
        'ordering' => array('question'),
        'defer' => array('answer'),
        'select_related'=> array('category'),
        'joins' => array(
            'category' => array(
                'constraint' => array(
                    'category_id' => 'Category.category_id'
                ),
            ),
            'attachments' => array(
                'constraint' => array(
                    "'F'" => 'Attachment.type',
                    'faq_id' => 'Attachment.object_id',
                ),
                'list' => true,
                'null' => true,
                'broker' => 'GenericAttachments',
            ),
            'topics' => array(
                'reverse' => 'FaqTopic.faq',
            ),
        ),
    );

    const PERM_MANAGE  = 'faq.manage';
    static protected $perms = array(
            self::PERM_MANAGE => array(
                'title' =>
                /* @trans */ 'FAQ',
                'desc'  =>
                /* @trans */ 'Ability to add/update/disable/delete knowledgebase categories and FAQs',
                'primary' => true,
            ));

    var $_local;
    var $_attachments;

    const VISIBILITY_PRIVATE = 0;
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_FEATURED = 2;

    /* ------------------> Getter methods <--------------------- */
    function getId() { return $this->faq_id; }
    function getHashtable() {
        $base = $this->ht;
        unset($base['category']);
        unset($base['attachments']);
        return $base;
    }
    function getKeywords() { return $this->keywords; }
    function getQuestion() { return $this->question; }
    function getAnswer() { return $this->answer; }
    function getAnswerWithImages() {
        return Format::viewableImages($this->answer, ['type' => 'F']);
    }
    function getTeaser() {
        return Format::truncate(Format::striptags($this->answer), 150);
    }
    function getSearchableAnswer() {
        return ThreadEntryBody::fromFormattedText($this->answer, 'html')
            ->getSearchable();
    }
    function getNotes() { return $this->notes; }
    function getNumAttachments() { return $this->attachments->count(); }

    function isPublished() {
        return $this->ispublished != self::VISIBILITY_PRIVATE
            && $this->category->isPublic();
    }
    function getVisibilityDescription() {
        switch ($this->ispublished) {
        case self::VISIBILITY_PRIVATE:
            return __('Internal');
        case self::VISIBILITY_PUBLIC:
            return __('Public');
        case self::VISIBILITY_FEATURED:
            return __('Featured');
        }
    }

    function getCreateDate() { return $this->created; }
    function getUpdateDate() { return $this->updated; }

    function getCategoryId() { return $this->category_id; }
    function getCategory() { return $this->category; }

    function getHelpTopicsIds() {
        $ids = array();
        foreach ($this->getHelpTopics() as $T)
            $ids[] = $T->topic->getId();
        return $ids;
    }

    function getHelpTopicNames() {
        $names = array();
        foreach ($this->getHelpTopics() as $T)
            $names[] = $T->topic->getFullName();
        return $names;
    }

    function getHelpTopics() {
        return $this->topics;
    }

    /* ------------------> Setter methods <--------------------- */
    function setPublished($val) { $this->ispublished = !!$val; }
    function setQuestion($question) { $this->question = Format::striptags(trim($question)); }
    function setAnswer($text) { $this->answer = $text; }
    function setKeywords($words) { $this->keywords = $words; }
    function setNotes($text) { $this->notes = $text; }

    function publish() {
        $this->setPublished(1);
        return $this->save();
    }

    function unpublish() {
        $this->setPublished(0);
        return $this->save();
    }

    function printPdf() {
        global $thisstaff;
        require_once(INCLUDE_DIR.'class.pdf.php');

        $paper = 'Letter';
        if ($thisstaff)
            $paper = $thisstaff->getDefaultPaperSize();

        ob_start();
        $faq = $this;
        include STAFFINC_DIR . 'templates/faq-print.tmpl.php';
        $html = ob_get_clean();

        $pdf = new mPDFWithLocalImages(['mode' => 'utf-8', 'format' =>
               $paper, 'tempDir'=>sys_get_temp_dir()]);
        // Setup HTML writing and load default thread stylesheet
        $pdf->WriteHtml(
            '<style>
            .bleed { margin: 0; padding: 0; }
            .faded { color: #666; }
            .faq-title { font-size: 170%; font-weight: bold; }
            .thread-body { font-family: serif; }'
            .file_get_contents(ROOT_DIR.'css/thread.css')
            .'</style>'
            .'<div>'.$html.'</div>', 0, true, true);

        $pdf->Output(Format::slugify($faq->getQuestion()) . '.pdf', 'I');
    }

    // Internationalization of the knowledge base

    function getTranslateTag($subtag) {
        return _H(sprintf('faq.%s.%s', $subtag, $this->getId()));
    }
    function getLocal($subtag) {
        $tag = $this->getTranslateTag($subtag);
        $T = CustomDataTranslation::translate($tag);
        return $T != $tag ? $T : $this->ht[$subtag];
    }
    function getAllTranslations() {
        if (!isset($this->_local)) {
            $tag = $this->getTranslateTag('q:a');
            $this->_local = CustomDataTranslation::allTranslations($tag, 'article');
        }
        return $this->_local;
    }
    function getLocalQuestion($lang=false) {
        return $this->_getLocal('question', $lang);
    }
    function getLocalAnswer($lang=false) {
        return $this->_getLocal('answer', $lang);
    }
    function getLocalAnswerWithImages($lang=false) {
        return Format::viewableImages($this->getLocalAnswer($lang),
                ['type' => 'F']);
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
    function getDisplayLang() {
        if (isset($_REQUEST['kblang']))
            $lang = $_REQUEST['kblang'];
        else
            $lang = Internationalization::getCurrentLanguage();
        return $lang;
    }

    function getLocalAttachments($lang=false) {
        return $this->attachments->getSeparates()->filter(Q::any(array(
            'lang__isnull' => true,
            'lang' => $lang ?: $this->getDisplayLang(),
        )));
    }

    function updateTopics($ids){

        if($ids) {
            $topics = $this->getHelpTopicsIds();
            foreach($ids as $id) {
                if($topics && in_array($id,$topics)) continue;
                $sql='INSERT IGNORE INTO '.FAQ_TOPIC_TABLE
                    .' SET faq_id='.db_input($this->getId())
                    .', topic_id='.db_input($id);
                db_query($sql);
            }
        }

        if ($ids)
            $this->topics->filter(Q::not(array('topic_id__in' => $ids)))->delete();
        else
            $this->topics->delete();
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
            $content = array('question' => $trans['question'],
                'answer' => Format::sanitize($trans['answer']));

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
        $tag = $this->getTranslateTag('q:a');
        foreach ($vars['trans'] as $lang=>$parts) {
            $content = array('question' => @$parts['question'],
                'answer' => Format::sanitize(@$parts['answer']));
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

    function getAttachments($lang=null) {
        $att = $this->attachments;
        if ($lang)
            $att = $att->window(array('lang' => $lang));
        return $att;
    }

    function delete() {
        try {
            parent::delete();
            $type = array('type' => 'deleted');
            Signal::send('object.deleted', $this, $type);
            // Cleanup help topics.
            $this->topics->expunge();
            // Cleanup attachments.
            $this->attachments->deleteAll();
        }
        catch (OrmException $ex) {
            return false;
        }
        return true;
    }

    /* ------------------> Static methods <--------------------- */

    static function add($vars, &$errors) {
        if(!($faq = self::create($vars)))
            return false;

        return $faq;
    }

    static function create($vars=false) {
        $faq = new static($vars);
        $faq->created = SqlFunction::NOW();
        return $faq;
    }

    static function allPublic() {
        return static::objects()->exclude(Q::any(array(
            'ispublished'=>self::VISIBILITY_PRIVATE,
            'category__ispublic'=>Category::VISIBILITY_PRIVATE,
        )));
    }

    static function countPublishedFAQs() {
        static $count;
        if (!isset($count)) {
            $count = self::allPublic()->count();
        }
        return $count;
    }

    static function getFeatured() {
        return self::objects()
            ->filter(array('ispublished__in'=>array(1,2), 'category__ispublic'=>1))
            ->order_by('-ispublished');
    }

    static function findIdByQuestion($question) {
        $row = self::objects()->filter(array(
            'question'=>$question
        ))->values_flat('faq_id')->first();

        return ($row) ? $row[0] : null;
    }

    static function findByQuestion($question) {
        return self::objects()->filter(array(
            'question'=>$question
        ))->one();
    }

    function update($vars, &$errors) {
        global $cfg;

        // Cleanup.
        $vars['question'] = Format::striptags(trim($vars['question']));

        // Validate
        if ($vars['id'] && $this->getId() != $vars['id'])
            $errors['err'] = __('Internal error occurred');
        elseif (!$vars['question'])
            $errors['question'] = __('Question required');
        elseif (($qid=self::findIdByQuestion($vars['question'])) && $qid != $vars['id'])
            $errors['question'] = __('Question already exists');

        if (!$vars['category_id'] || !($category=Category::lookup($vars['category_id'])))
            $errors['category_id'] = __('Category is required');

        if (!$vars['answer'])
            $errors['answer'] = __('FAQ answer is required');

        if ($errors)
            return false;

        $this->question = $vars['question'];
        $this->answer = Format::sanitize($vars['answer']);
        $this->category = $category;
        $this->ispublished = $vars['ispublished'];
        $this->notes = Format::sanitize($vars['notes']);
        $this->keywords = ' ';

        if (!$this->save())
            return false;

        $this->updateTopics($vars['topics']);

        // General attachments (for all languages)
        // ---------------------
        // Delete removed attachments.
        if (isset($vars['files'])) {
            $this->getAttachments()->keepOnlyFileIds($vars['files'], false);
        }

        $images = Draft::getAttachmentIds($vars['answer']);
        $images = array_flip(array_map(function($i) { return $i['id']; }, $images));
        $this->getAttachments()->keepOnlyFileIds($images, true);

        // Handle language-specific attachments
        // ----------------------
        $langs = $cfg ? $cfg->getSecondaryLanguages() : false;
        if ($langs) {
            $langs[] = $cfg->getPrimaryLanguage();
            foreach ($langs as $lang) {
                if (!isset($vars['files_'.$lang]))
                    // Not updating the FAQ
                    continue;

                $keepers = $vars['files_'.$lang];

                // FIXME: Include inline images in translated content

                $this->getAttachments($lang)->keepOnlyFileIds($keepers, false, $lang);
            }
        }

        if (isset($vars['trans']) && !$this->saveTranslations($vars))
            return false;

        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    static function getPermissions() {
        return self::$perms;
    }
}

RolePermission::register( /* @trans */ 'Knowledgebase',
        FAQ::getPermissions());

class FaqTopic extends VerySimpleModel {

    static $meta = array(
        'table' => FAQ_TOPIC_TABLE,
        'pk' => array('faq_id', 'topic_id'),
        'select_related' => 'topic',
        'joins' => array(
            'faq' => array(
                'constraint' => array(
                    'faq_id' => 'FAQ.faq_id',
                ),
            ),
            'topic' => array(
                'constraint' => array(
                    'topic_id' => 'Topic.topic_id',
                ),
            ),
        ),
    );
}

class FaqAccessMgmtForm
extends AbstractForm {
    function buildFields() {
        return array(
            'ispublished' => new ChoiceField(array(
                'label' => __('Listing Type'),
                'choices' => array(
                    FAQ::VISIBILITY_PRIVATE => __('Internal'),
                    FAQ::VISIBILITY_PUBLIC => __('Public'),
                    FAQ::VISIBILITY_FEATURED => __('Featured'),
                ),
            )),
        );
    }
}
