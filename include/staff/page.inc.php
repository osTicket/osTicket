<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$pageTypes = array(
        'landing' => __('Landing Page'),
        'offline' => __('Offline Page'),
        'thank-you' => __('Thank-You Page'),
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
<form action="pages.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo $title; ?>
    <?php if (isset($info['name'])) { ?><small>
    — <?php echo $info['name']; ?></small>
     <?php } ?>
    <i class="help-tip icon-question-sign" href="#site_pages"></i>
    </h2>
 <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr><td style="padding:0"></td><td style="padding:0;"></td></tr> <!-- For fixed table layout -->
        <tr>
            <th colspan="2">
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
                    autofocus data-translate-tag="<?php echo $trans['name']; ?>"/>
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
    </tbody>
</table>
<div style="margin-top: 10px">
  <ul class="tabs clean">
    <li class="active"><a href="#page-content"><?php echo __('Page Content'); ?></a></li>
    <li><a href="#notes"><?php echo __('Internal Notes'); ?></a></li>
  </ul>
  <div class="tab_content active" id="page-content">

<?php
$langs = Internationalization::getConfiguredSystemLanguages();
if ($page && count($langs) > 1) { ?>
    <ul class="tabs alt clean" id="translations">
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
}

// For landing page, constrain to the diplayed width of 565px;
if ($info['type'] == 'landing')
    $width = '565px';
else
    $width = '100%';
?>
    <div id="translations_container">
      <div id="translation-<?php echo $cfg->getPrimaryLanguage(); ?>" class="tab_content"
        lang="<?php echo $cfg->getPrimaryLanguage(); ?>">
        <textarea name="body" cols="21" rows="12" class="richtext draft"
          data-width="<?php echo $width; ?>"
<?php
    if (!$info['type'] || $info['type'] == 'thank-you') echo 'data-root-context="thank-you"';
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('page', $info['id'], $info['body']);
    echo $attrs; ?>><?php echo $info['body'] ?: $draft; ?></textarea>
      </div>

<?php if ($langs && $page) {
    foreach ($langs as $tag=>$nfo) {
        if ($tag == $cfg->getPrimaryLanguage())
          continue; ?>
      <div id="translation-<?php echo $tag; ?>" class="tab_content hidden"
        dir="<?php echo $nfo['direction']; ?>" lang="<?php echo $tag; ?>">
        <textarea name="trans[<?php echo $tag; ?>][body]" cols="21" rows="12"
<?php if ($info['type'] == 'thank-you') echo 'data-root-context="thank-you"'; ?>
          style="width:100%" class="richtext draft" data-width="<?php echo $width; ?>"
<?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('page', $info['id'].'.'.$tag, $info['trans'][$tag]);
    echo $attrs; ?>><?php echo $info['trans'][$tag] ?: $draft; ?></textarea>
      </div>
<?php }
} ?>

      <div id="msg_info">
        <em><i class="icon-info-sign"></i> <?php
          echo __(
            'Ticket variables are only supported in thank-you pages.'
          ); ?></em>
      </div>

      <div class="error" style="margin: 5px 0"><?php echo $errors['body']; ?></div>
      <div class="clear"></div>
    </div>
  </div>
  <div class="tab_content" style="display:none" id="notes">
    <em><strong><?php echo __('Internal Notes'); ?></strong>:
      <?php echo __("Be liberal, they're internal"); ?></em>
    <textarea class="richtext no-bar" name="notes" cols="21"
      rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
  </div>
</div>

<p style="text-align:center">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick='window.location.href="pages.php"'>
</p>
</form>
