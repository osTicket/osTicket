<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$pageTypes = array(
        'landing' => __('Landing page'),
        'offline' => __('Offline page'),
        'thank-you' => __('Thank you page'),
        'other' => __('Other'),
        );
$info = $qs = array();
if($page && $_REQUEST['a']!='add'){
    $title=__('Update Page');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$page->getHashtable();
    $info['body'] = Format::viewableImages($page->getBody());
    $info['notes'] = Format::viewableImages($info['notes']);
    $trans['name'] = $page->getTranslateTag('name');
    $slug = Format::slugify($info['name']);
    $qs += array('id' => $page->getId());
    $translations = CustomDataTranslation::allTranslations(
        $page->getTranslateTag('name:body'), 'article');
    foreach ($cfg->getSecondaryLanguages() as $tag) {
        foreach ($translations as $t) {
            if (strcasecmp($t->lang, $tag) === 0) {
                $C = $t->getComplex();
                $info['trans'][$tag] = Format::viewableImages($C['body']);
                break;
            }
        }
    }
}else {
    $title=__('Add New Page');
    $action='add';
    $submit_text=__('Add Page');
    $info['isactive']=isset($info['isactive'])?$info['isactive']:0;
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="pages.php?<?php echo Http::build_query($qs); ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo __('Site Pages'); ?>
    <i class="help-tip icon-question-sign" href="#site_pages"></i>
    </h2>
 <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr><td></td><td></td></tr> <!-- For fixed table layout -->
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><?php echo __('Page information'); ?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
              <?php echo __('Name'); ?>:
            </td>
            <td>
                <input type="text" size="40" name="name" value="<?php echo $info['name']; ?>"
                data-translate-tag="<?php echo $trans['name']; ?>"/>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Type'); ?>:
            </td>
            <td>
                <span>
                <select name="type">
                    <option value="" selected="selected">&mdash; <?php
                    echo __('Select Page Type'); ?> &mdash;</option>
                    <?php
                    foreach($pageTypes as $k => $v)
                        echo sprintf('<option value="%s" %s>%s</option>',
                                $k, (($info['type']==$k)?'selected="selected"':''), $v);
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['type']; ?></span>
                &nbsp;<i class="help-tip icon-question-sign" href="#type"></i>
                </span>
            </td>
        </tr>
        <?php if ($info['name'] && $info['type'] == 'other') { ?>
        <tr>
            <td width="180" class="required">
                <?php echo __('Public URL'); ?>:
            </td>
            <td><a href="<?php echo sprintf("%s/pages/%s",
                    $ost->getConfig()->getBaseUrl(), urlencode($slug));
                ?>">pages/<?php echo $slug; ?></a>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <td width="180" class="required">
                <?php echo __('Status'); ?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>>
                <strong><?php echo __('Active'); ?></strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>>
                <?php echo __('Disabled'); ?>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['isactive']; ?></span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <ul class="tabs">
                    <li class="active"><a href="#content"><?php echo __('Page Content'); ?></a></li>
                    <li><a href="#notes"><?php echo __('Internal Notes'); ?></a></li>
                </ul>
    <div class="tab_content active" id="content">
<?php
$langs = Internationalization::getConfiguredSystemLanguages();
if ($page && count($langs) > 1) { ?>
    <ul class="vertical left tabs">
        <li class="empty"><i class="icon-globe" title="This content is translatable"></i></li>
<?php foreach ($langs as $tag=>$nfo) { ?>
    <li class="<?php if ($tag == $cfg->getPrimaryLanguage()) echo "active";
        ?>"><a href="#translation-<?php echo $tag; ?>" title="<?php
        echo Internationalization::getLanguageDescription($tag);
    ?>"><span class="flag flag-<?php echo strtolower($nfo['flag']); ?>"></span>
    </a></li>
<?php } ?>
    </ul>
<?php
} ?>
    <div id="msg_info" style="margin:0 55px">
    <em><i class="icon-info-sign"></i> <?php
        echo __(
            'Ticket variables are only supported in thank-you pages.'
    ); ?></em></div>

        <div id="translation-<?php echo $cfg->getPrimaryLanguage(); ?>" class="tab_content" style="margin:0 45px"
            lang="<?php echo $cfg->getPrimaryLanguage(); ?>">
        <textarea name="body" cols="21" rows="12" style="width:98%;" class="richtext draft"
<?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('page', $info['id'], $info['body']);
    echo $attrs; ?>><?php echo $draft ?: $info['body']; ?></textarea>
        </div>

<?php if ($langs && $page) {
    foreach ($langs as $tag=>$nfo) {
        if ($tag == $cfg->getPrimaryLanguage())
            continue; ?>
        <div id="translation-<?php echo $tag; ?>" class="tab_content"
            style="display:none;margin:0 45px" lang="<?php echo $tag; ?>">
        <textarea name="trans[<?php echo $tag; ?>][body]" cols="21" rows="12"
            style="width:98%;" class="richtext draft"
<?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('page', $info['id'].'.'.$tag, $info['trans'][$tag]);
    echo $attrs; ?>><?php echo $draft ?: $info['trans'][$tag]; ?></textarea>
        </div>
<?php }
} ?>

        <div class="error" style="margin: 5px 55px"><?php echo $errors['body']; ?></div>
        <div class="clear"></div>
    </div>
    <div class="tab_content" style="display:none" id="notes">
        <em><strong><?php echo __('Internal Notes'); ?></strong>:
        <?php echo __("be liberal, they're internal"); ?></em>
        <textarea class="richtext no-bar" name="notes" cols="21"
            rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
    </div>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:225px;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick='window.location.href="pages.php"'>
</p>
</form>
