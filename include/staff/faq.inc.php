<?php
if (!defined('OSTSCPINC') || !$thisstaff
        || !$thisstaff->hasPerm(FAQ::PERM_MANAGE))
    die('Access Denied');

$info = $qs = array();
if($faq && $faq->getId()){
    $title=__('Update FAQ').': '.$faq->getQuestion();
    $action='update';
    $submit_text=__('Save Changes');
    $info=$faq->getHashtable();
    $info['id']=$faq->getId();
    $info['topics']=$faq->getHelpTopicsIds();
    $info['answer']=Format::viewableImages($faq->getAnswer());
    $info['notes']=Format::viewableImages($faq->getNotes());
    $qs += array('id' => $faq->getId());
    $langs = $cfg->getSecondaryLanguages();
    $translations = $faq->getAllTranslations();
    foreach ($langs as $tag) {
        foreach ($translations as $t) {
            if (strcasecmp($t->lang, $tag) === 0) {
                $trans = $t->getComplex();
                $info['trans'][$tag] = array(
                    'question' => $trans['question'],
                    'answer' => Format::viewableImages($trans['answer']),
                );
                break;
            }
        }
    }
}else {
    $title=__('Add New FAQ');
    $action='create';
    $submit_text=__('Add FAQ');
    if($category) {
        $qs += array('cid' => $category->getId());
        $info['category_id']=$category->getId();
    }
}
//TODO: Add attachment support.
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
$qstr = Http::build_query($qs);
?>
<form action="faq.php?<?php echo $qstr; ?>" method="post" class="save" enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo __('Frequently Asked Questions');?></h2>
<?php if ($info['question']) { ?>
     <div class="faq-title" style="margin:5px 0 15px"><?php echo $info['question']; ?></div>
<?php } ?>
<div>
 <div style="display:inline-block;width:49%">
    <div>
        <b><?php echo __('Category Listing');?></b>:
        <span class="error">*</span>
        <div class="faded"><?php echo __('FAQ category the question belongs to.');?></div>
    </div>
    <select name="category_id" style="width:350px;">
        <option value="0"><?php echo __('Select FAQ Category');?> </option>
<?php foreach (Category::objects() as $C) { ?>
        <option value="<?php echo $C->getId(); ?>" <?php
            if ($C->getId() == $info['category_id']) echo 'selected="selected"';
            ?>><?php echo sprintf('%s (%s)',
                Category::getNameById($C->getId()),
                $C->isPublic() ? __('Public') : __('Private')
            ); ?></option>
<?php } ?>
    </select>
    <div class="error"><?php echo $errors['category_id']; ?></div>

<?php
if ($topics = Topic::getAllHelpTopics()) {
    if (!is_array(@$info['topics']))
        $info['topics'] = array();
?>
    <div style="padding-top:9px">
        <strong><?php echo __('Help Topics');?></strong>:
        <div class="faded"><?php echo sprintf(__('Check all help topics related to %s.'), __('this FAQ article'));?></div>
    </div>
    <select multiple="multiple" name="topics[]" class="multiselect"
        data-placeholder="<?php echo __('Help Topics'); ?>"
        id="help-topic-selection" style="width:350px;">
    <?php while (list($topicId,$topic) = each($topics)) { ?>
        <option value="<?php echo $topicId; ?>" <?php
            if (in_array($topicId, $info['topics'])) echo 'selected="selected"';
        ?>><?php echo $topic; ?></option>
    <?php } ?>
    </select>
    <script type="text/javascript">
        $(function() { $("#help-topic-selection").select2(); });
    </script>
<?php } ?>
    </div>
 <div style="display:inline-block;width:49%;margin-left:1%;vertical-align:top">
    <div style="padding-top:9px;">
        <b><?php echo __('Listing Type');?></b>:
        <span class="error">*</span>
        <i class="help-tip icon-question-sign" href="#listing_type"></i>
    </div>
    <select name="ispublished">
        <option value="2" <?php echo $info['ispublished'] == 2 ? 'selected="selected"' : ''; ?>>
            <?php echo __('Featured (promote to front page)'); ?>
        </option>
        <option value="1" <?php echo $info['ispublished'] == 1 ? 'selected="selected"' : ''; ?>>
            <?php echo __('Public').' '.__('(publish)'); ?>
        </option>
        <option value="0" <?php echo !$info['ispublished'] ? 'selected="selected"' : ''; ?>>
            <?php echo __('Internal').' '.('(private)'); ?>
        </option>
    </select>
    <div class="error"><?php echo $errors['ispublished']; ?></div>
  </div>
</div>

<div style="margin-top:20px"></div>

<ul class="tabs clean" style="margin-top:9px;">
    <li class="active"><a href="#article"><?php echo __('Article Content'); ?></a></li>
    <li><a href="#attachments"><?php echo __('Attachments') . sprintf(' (%d)',
        $faq ? count($faq->attachments->getSeparates('')) : 0); ?></a></li>
    <li><a href="#notes"><?php echo __('Internal Notes'); ?></a></li>
</ul>

<div class="tab_content" id="article">
<strong><?php echo __('Knowledgebase Article Content'); ?></strong><br/>
<?php echo __('Here you can manage the question and answer for the article. Multiple languages are available if enabled in the admin panel.'); ?>
<div class="clear"></div>

<?php
$langs = Internationalization::getConfiguredSystemLanguages();
if ($faq && count($langs) > 1) { ?>
    <ul class="tabs alt clean" id="trans" style="margin-top:10px;">
        <li class="empty"><i class="icon-globe" title="This content is translatable"></i></li>
<?php foreach ($langs as $tag=>$i) {
    list($lang, $locale) = explode('_', $tag);
 ?>
    <li class="<?php if ($tag == $cfg->getPrimaryLanguage()) echo "active";
        ?>"><a href="#lang-<?php echo $tag; ?>" title="<?php
        echo Internationalization::getLanguageDescription($tag);
    ?>"><span class="flag flag-<?php echo strtolower($i['flag'] ?: $locale ?: $lang); ?>"></span>
    </a></li>
<?php } ?>
    </ul>
<?php
} ?>

<div id="trans_container">
<?php foreach ($langs as $tag=>$i) {
    $code = $i['code'];
    if ($tag == $cfg->getPrimaryLanguage()) {
        $namespace = $faq ? $faq->getId() : false;
        $answer = $info['answer'];
        $question = $info['question'];
        $qname = 'question';
        $aname = 'answer';
    }
    else {
        $namespace = $faq ? $faq->getId() . $code : $code;
        $answer = $info['trans'][$code]['answer'];
        $question = $info['trans'][$code]['question'];
        $qname = 'trans['.$code.'][question]';
        $aname = 'trans['.$code.'][answer]';
    }
?>
    <div class="tab_content <?php
        if ($code != $cfg->getPrimaryLanguage()) echo "hidden";
     ?>" id="lang-<?php echo $tag; ?>"
<?php if ($i['direction'] == 'rtl') echo 'dir="rtl" class="rtl"'; ?>
    >
    <div style="margin-bottom:0.5em;margin-top:9px">
        <b><?php echo __('Question');?>
            <span class="error">*</span>
        </b>
        <div class="error"><?php echo $errors['question']; ?></div>
    </div>
    <input type="text" size="70" name="<?php echo $qname; ?>"
        style="font-size:110%;width:100%;box-sizing:border-box;"
        value="<?php echo $question; ?>">
    <div style="margin-bottom:0.5em;margin-top:9px">
        <b><?php echo __('Answer');?></b>
        <span class="error">*</span>
        <div class="error"><?php echo $errors['answer']; ?></div>
    </div>
    <div>
    <textarea name="<?php echo $aname; ?>" cols="21" rows="12"
        data-width="670px"
        class="richtext draft" <?php
list($draft, $attrs) = Draft::getDraftAndDataAttrs('faq', $namespace, $answer);
echo $attrs; ?>><?php echo $draft ?: $answer;
        ?></textarea>

    </div>
    </div>
<?php } ?>
    </div>
</div>

<div class="tab_content" id="attachments" style="display:none">
    <div>
        <strong><?php echo __('Common Attachments'); ?></strong>
        <div><?php echo __(
            'These attachments are always available, regardless of the language in which the article is rendered'
        ); ?></div>
        <div class="error"><?php echo $errors['files']; ?></div>
        <div style="margin-top:15px"></div>
    </div>
    <?php
    print $faq_form->getField('attachments')->render(); ?>

<?php if (count($langs) > 1) { ?>
    <div style="margin-top:15px"></div>
    <strong><?php echo __('Language-Specific Attachments'); ?></strong>
    <div><?php echo __(
        'These attachments are only available when article is rendered in one of the following languages.'
    ); ?></div>
    <div class="error"><?php echo $errors['files']; ?></div>
    <div style="margin-top:15px"></div>

    <ul class="tabs alt clean">
        <li class="empty"><i class="icon-globe" title="This content is translatable"></i></li>
<?php foreach ($langs as $lang=>$i) { ?>
        <li class="<?php if ($i['code'] == $cfg->getPrimaryLanguage()) echo 'active';
?>"><a href="#attachments-<?php echo $i['code']; ?>">
    <span class="flag flag-<?php echo $i['flag']; ?>"></span>
    </a></li>
<?php } ?>
    </ul>
<?php foreach ($langs as $lang=>$i) {
    $code = $i['code']; ?>
    <div class="tab_content" id="attachments-<?php echo $i['code']; ?>" <?php if ($i['code'] != $cfg->getPrimaryLanguage()) echo 'style="display:none;"'; ?>>
    <div style="padding:0 0 9px">
        <strong><?php echo sprintf(__(
            /* %s is the name of a language */ 'Attachments for %s'),
            Internationalization::getLanguageDescription($lang));
        ?></strong>
    </div>
    <?php
    print $faq_form->getField('attachments.'.$code)->render();
    ?></div><?php
    }
} ?>
<div class="clear"></div>
</div>

<div class="tab_content" style="display:none;" id="notes">
    <div>
        <b><?php echo __('Internal Notes');?></b>:<span class="faded"><?php echo __("Be liberal, they're internal");?></span>
    </div>
    <div style="margin-top:10px"></div>
    <textarea class="richtext no-bar" name="notes" cols="21"
        rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
</div>

<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>" onclick="javascript:
        $(this.form).find('textarea.richtext')
            .redactor('deleteDraft');
        location.reload();" />
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick='window.location.href="faq.php?<?php echo $qstr; ?>"'>
</p>
</form>
