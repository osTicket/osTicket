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

class FAQ extends VerySimpleModel {

    static $meta = array(
        'table' => FAQ_TABLE,
        'pk' => array('faq_id'),
        'ordering' => array('question'),
        'defer' => array('answer'),
        'joins' => array(
            'category' => array(
                'constraint' => array(
                    'category_id' => 'Category.category_id'
                ),
            ),
            'attachments' => array(
                'constraint' => array(
                    "'F'" => 'GenericAttachment.type',
                    'faq_id' => 'GenericAttachment.object_id',
                ),
                'list' => true,
                'null' => true,
            ),
        ),
    );

    var $attachments;
    var $topics;
    var $_local;

    function __onload() {
        if (isset($this->faq_id))
            $this->attachments = new GenericAttachments($this->getId(), 'F');
    }

    /* ------------------> Getter methods <--------------------- */
    function getId() { return $this->faq_id; }
    function getHashtable() { return $this->ht; }
    function getKeywords() { return $this->keywords; }
    function getQuestion() { return $this->question; }
    function getAnswer() { return $this->answer; }
    function getAnswerWithImages() {
        return Format::viewableImages($this->answer, ROOT_PATH.'image.php');
    }
    function getTeaser() {
        return Format::truncate(Format::striptags($this->answer), 150);
    }
    function getSearchableAnswer() {
        return ThreadBody::fromFormattedText($this->answer, 'html')
            ->getSearchable();
    }
    function getNotes() { return $this->notes; }
    function getNumAttachments() { return $this->attachments->count(); }

    function isPublished() { return (!!$this->ispublished && !!$this->category->ispublic); }

    function getCreateDate() { return $this->created; }
    function getUpdateDate() { return $this->updated; }

    function getCategoryId() { return $this->category_id; }
    function getCategory() { return $this->category; }

    function getHelpTopicsIds() {
        $ids = array();
        foreach ($this->getHelpTopics() as $topic)
            $ids[] = $topic->getId();
        return $ids;
    }

    function getHelpTopicNames() {
        $names = array();
        foreach ($this->getHelpTopics() as $topic)
            $names[] = $topic->getFullName();
        return $names;
    }

    function getHelpTopics() {
        //XXX: change it to obj (when needed)!

        if (!isset($this->topics)) {
            $this->topics = Topic::objects()->filter(array(
                'topic_id__in' => FaqTopic::objects()->filter(array(
                        'faq_id' => $this->getId(),
                    ))->values('topic_id'),
            ));
        }
        return $this->topics;
    }

    /* ------------------> Setter methods <--------------------- */
    function setPublished($val) { $this->ispublished = !!$val; }
    function setQuestion($question) { $this->question = Format::striptags(trim($question)); }
    function setAnswer($text) { $this->answer = $text; }
    function setKeywords($words) { $this->keywords = $words; }
    function setNotes($text) { $this->notes = $text; }

    /* For ->attach() and ->detach(), use $this->attachments() (nolint) */
    function attach($file) { return $this->_attachments->add($file); }
    function detach($file) { return $this->_attachments->remove($file); }

    function publish() {
        $this->setPublished(1);
        return $this->save();
    }

    function unpublish() {
        $this->setPublished(0);
        return $this->save();
    }

    function logView() {
        $this->views++;
        $this->save();
    }

    function printPdf() {
        global $thisstaff;
        require_once(INCLUDE_DIR.'mpdf/mpdf.php');

        $paper = 'Letter';
        if ($thisstaff)
            $paper = $thisstaff->getDefaultPaperSize();

        ob_start();
        $faq = $this;
        include STAFFINC_DIR . 'templates/faq-print.tmpl.php';
        $html = ob_get_clean();

        $pdf = new mPDF('', $paper);
        // Setup HTML writing and load default thread stylesheet
        $pdf->WriteHtml(
            '<style>
            .bleed { margin: 0; padding: 0; }
            .faded { color: #666; }
            .faq-title { font-size: 170%; font-weight: bold; }
            .thread-body { font-family: serif; }'
            .file_get_contents(ROOT_DIR.'css/thread.css')
            .'</style>'
            .'<div>'.$html.'</div>');

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
    function getLocalAnswerWithImages($lang=false) {
        return $this->_getLocal('answer', $lang);
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
        return $this->attachments->getSeparates(
            $lang ?: $this->getDisplayLang());
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

        $sql='DELETE FROM '.FAQ_TOPIC_TABLE.' WHERE faq_id='.db_input($this->getId());
        if($ids)
            $sql.=' AND topic_id NOT IN('.implode(',', db_input($ids)).')';

        if (!db_query($sql))
            return false;

        Signal::send('model.updated', $this);
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

    function getVisibleAttachments() {
        return array_merge(
            $this->attachments->getSeparates() ?: array(),
            $this->getLocalAttachments());
    }

    function getAttachmentsLinks($separator=' ',$target='') {

        $str='';
        if ($attachments = $this->getVisibleAttachments()) {
            foreach($attachments as $attachment ) {
            /* The h key must match validation in file.php */
            $hash=$attachment['key'].md5($attachment['id'].session_id().strtolower($attachment['key']));
            if($attachment['size'])
                $size=sprintf('&nbsp;<small>(<i>%s</i>)</small>',Format::file_size($attachment['size']));

            $str.=sprintf('<a class="Icon file no-pjax" href="file.php?h=%s" target="%s">%s</a>%s&nbsp;%s',
                    $hash, $target, Format::htmlchars($attachment['name']), $size, $separator);

            }
        }
        return $str;
    }

    function delete() {
        try {
            parent::delete();
            // Cleanup help topics.
            db_query('DELETE FROM '.FAQ_TOPIC_TABLE.' WHERE faq_id='.db_input($this->getId()));
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
        $faq = parent::create($vars);
        $faq->created = SqlFunction::NOW();
        return $faq;
    }

    static function countPublishedFAQs() {
        static $count;
        if (!isset($count)) {
            $count = self::objects()->filter(array(
                'category__ispublic__gt' => 0,
                'ispublished__gt'=> 0
            ))->count();
        }
        return $count;
    }

    static function getFeatured() {
        return self::objects()
            ->filter(array('ispublished__in'=>array(1,2), 'category__ispublic'=>1))
            ->order_by('-ispublished','-views');
    }

    static function findIdByQuestion($question) {
        $object = self::objects()->filter(array(
            'question'=>$question
        ))->values_flat('faq_id')->one();

        if ($object)
            return $object[0];
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
            $keepers = $vars['files'];
            if (($attachments = $this->attachments->getSeparates())) {
                foreach($attachments as $file) {
                    if($file['id'] && !in_array($file['id'], $keepers))
                        $this->attachments->delete($file['id']);
                }
            }
        }
        // Upload new attachments IF any.
        $this->attachments->upload($keepers);

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

                // Delete removed attachments.
                if (($attachments = $this->attachments->getSeparates($lang))) {
                    foreach ($attachments as $file) {
                        if ($file['id'] && !in_array($file['id'], $keepers))
                            $this->attachments->delete($file['id']);
                    }
                }
                // Upload new attachments IF any.
                $this->attachments->upload($keepers, false, $lang);
            }
        }

        // Inline images (attached to the draft)
        $this->attachments->deleteInlines();
        $this->attachments->upload(Draft::getAttachmentIds($vars['answer']));

        if (isset($vars['trans']) && !$this->saveTranslations($vars))
            return false;

        return true;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }
}
FAQ::_inspect();

class FaqTopic extends VerySimpleModel {

    static $meta = array(
        'table' => FAQ_TOPIC_TABLE,
        'pk' => array('faq_id', 'topic_id'),
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
?>
