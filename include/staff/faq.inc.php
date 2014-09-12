<?php
if(!defined('OSTSCPINC') || !$thisstaff || !$thisstaff->canManageFAQ()) die('Access Denied');
$info=array();
$qstr='';
if($faq){
    $title=__('Update FAQ').': '.$faq->getQuestion();
    $action='update';
    $submit_text=__('Save Changes');
    $info=$faq->getHashtable();
    $info['id']=$faq->getId();
    $info['topics']=$faq->getHelpTopicsIds();
    $info['answer']=Format::viewableImages($faq->getAnswer());
    $info['notes']=Format::viewableImages($faq->getNotes());
    $qstr='id='.$faq->getId();
    $langs = $cfg->getSecondaryLanguages();
    $translations = $faq->getAllTranslations();
    foreach ($cfg->getSecondaryLanguages() as $tag) {
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
        $qstr='cid='.$category->getId();
        $info['category_id']=$category->getId();
    }
}
//TODO: Add attachment support.
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="faq.php?<?php echo $qstr; ?>" method="post" id="save" enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo __('FAQ');?></h2>
<?php if ($info['question']) { ?>
     <h4><?php echo $info['question']; ?></h4>
<?php } ?>
 <div>
    <div>
        <b><?php echo __('Category Listing');?></b>:
        <span class="error">*</span>
        <div class="faded"><?php echo __('FAQ category the question belongs to.');?></div>
    </div>
    <select name="category_id" style="width:350px;">
        <option value="0"><?php echo __('Select FAQ Category');?> </option>
        <?php
        $sql='SELECT category_id, name, ispublic FROM '.FAQ_CATEGORY_TABLE;
        if(($res=db_query($sql)) && db_num_rows($res)) {
            while($row=db_fetch_array($res)) {
                echo sprintf('<option value="%d" %s>%s (%s)</option>',
                        $row['category_id'],
                        (($info['category_id']==$row['category_id'])?'selected="selected"':''),
                        $row['name'],
                        ($info['ispublic']?__('Public'):__('Internal')));
            }
        }
       ?>
    </select>
    <div class="error"><?php echo $errors['category_id']; ?></div>

<?php
if ($topics = Topic::getAllHelpTopics()) {
    if (!is_array(@$info['topics']))
        $info['topics'] = array();
?>
    <div style="padding-top:9px">
        <strong><?php echo __('Help Topics');?></strong>:
        <div class="faded"><?php echo __('Check all help topics related to this FAQ.');?></div>
    </div>
    <select multiple="multiple" name="topics[]" class="multiselect"
        id="help-topic-selection" style="width:350px;">
    <?php while (list($topicId,$topic) = each($topics)) { ?>
        <option value="<?php echo $topicId; ?>" <?php
            if (in_array($topicId, $info['topics'])) echo 'selected="selected"';
        ?>><?php echo $topic; ?></option>
    <?php } ?>
    </select>
    <script type="text/javascript">
        $(function() { $("#help-topic-selection").multiselect({
            noneSelectedText: '<?php echo __('Help Topics'); ?>'});
         });
    </script>
<?php } ?>

    <div style="padding-top:9px;">
        <b><?php echo __('Listing Type');?></b>:
        <span class="error">*</span>
        <i class="help-tip icon-question-sign" href="#listing_type"></i>
    </div>
    <select name="ispublished">
        <option value="1" <?php echo $info['ispublished'] ? 'selected="selected"' : ''; ?>>
            <?php echo __('Public (publish)'); ?>
        </option>
        <option value="0" <?php echo !$info['ispublished'] ? 'selected="selected"' : ''; ?>>
            <?php echo __('Internal (private)'); ?>
        </option>
    </select>
    <div class="error"><?php echo $errors['ispublished']; ?></div>
</div>

<ul class="tabs" style="margin-top:9px;">
    <li class="active"><a href="#article"><?php echo __('Article Content'); ?></a></li>
    <li><a href="#attachments"><?php echo __('Attachments') . sprintf(' (%d)',
        $faq ? count($faq->attachments->getSeparates('')) : 0); ?></a></li>
    <li><a href="#notes"><?php echo __('Internal Notes'); ?></a></li>
</ul>

<div class="tab_content" id="article">
<strong>Knowledgebase Article Content</strong><br/>
Here you can manage the question and answer for the article. Multiple
languages are available if enabled in the admin panel.
<div class="clear"></div>
<?php
$langs = Internationalization::getConfiguredSystemLanguages();
if ($faq) { ?>
    <ul class="vertical tabs left" style="margin-top:10px;">
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
    <div class="tab_content" style="margin-left:45px;<?php
        if ($code != $cfg->getPrimaryLanguage()) echo "display:none;";
     ?>" id="lang-<?php echo $tag; ?>">
    <div style="padding-top:9px;">
        <b><?php echo __('Question');?>
            <span class="error">*</span>
        </b>
        <div class="error"><?php echo $errors['question']; ?></div>
    </div>
    <input type="text" size="70" name="<?php echo $qname; ?>"
        style="font-size:105%;display:block;width:98%"
        value="<?php echo $question; ?>">
    <div style="margin-bottom:0.5em;margin-top:9px">
        <b><?php echo __('Answer');?></b>
        <span class="error">*</span>
        <div class="error"><?php echo $errors['answer']; ?></div>
    </div>
    <div>
    <textarea name="<?php echo $aname; ?>" cols="21" rows="12"
        class="richtext draft" <?php
list($draft, $attrs) = Draft::getDraftAndDataAttrs('faq', $namespace, $answer);
echo $attrs; ?>><?php echo $draft ?: $answer;
        ?></textarea>

    </div>
    </div>
<?php } ?>
</div>

<div class="tab_content" id="attachments" style="display:none">
    <div>
        <p><em><?php echo __(
            'These attachments are always available, regardless of the language in which the article is rendered'
        ); ?></em></p>
        <div class="error"><?php echo $errors['files']; ?></div>
    </div>
    <?php
    print $faq_form->getField('attachments')->render(); ?>

<?php if (count($langs) > 1) {
    foreach ($langs as $lang=>$i) {
    $code = $i['code']; ?>
    <div style="padding-top:9px">
        <strong><?php echo sprintf(__(
            /* %s is the name of a language */ 'Attachments for %s'),
            Internationalization::getLanguageDescription($lang));
        ?></strong>
    <div style="margin:0 0 3px"><em class="faded"><?php echo __(
        'These attachments are only available when article is rendered in this language.'
    ); ?></em></div>
    </div>
    <?php
    print $faq_form->getField('attachments.'.$code)->render();
    }
} ?>
</div>

<div class="tab_content" style="display:none;" id="notes">
    <div>
        <b><?php echo __('Internal Notes');?></b>:
        <div class="faded"><?php echo __("Be liberal, they're internal");?></div>
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

<link rel="stylesheet" type="text/css" href="<?php echo ROOT_PATH; ?>css/jquery.multiselect.css" />
