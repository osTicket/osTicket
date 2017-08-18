<?php
if (!defined('OSTSCPINC') || !$thisstaff
        || !$thisstaff->hasPerm(FAQ::PERM_MANAGE))
    die('Access Denied');

$info=array();
$qs = array();
if($category && $_REQUEST['a']!='add'){
    $title=__('Update Category');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$category->getHashtable();
    $info['id']=$category->getId();
    $info['notes'] = Format::viewableImages($category->getNotes());
    $qs += array('id' => $category->getId());
    $langs = $cfg->getSecondaryLanguages();
    $translations = $category->getAllTranslations();
    foreach ($langs as $tag) {
        foreach ($translations as $t) {
            if (strcasecmp($t->lang, $tag) === 0) {
                $trans = $t->getComplex();
                $info['trans'][$tag] = array(
                    'name' => $trans['name'],
                    'description' => Format::viewableImages($trans['description']),
                );
                break;
            }
        }
    }
}else {
    $title=__('Add New Category');
    $action='create';
    $submit_text=__('Add');
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>


<div class="subnav">

    <div class="float-left subnavtitle">
                          
    <?php echo __('Update Category').' / '.$info['name']; ?>               
    
    </div>
    
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
    
    &nbsp;
                                
                                
      </div>   
   <div class="clearfix"></div> 
</div> 

<div class="card-box">

<div class="row">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
<form action="categories.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">


    <div style="margin:8px 0"><strong><?php echo __('Category Type');?>:</strong>
        <span class="error">*</span></div>
    <div style="margin-left:5px">
    <input type="radio" name="ispublic" value="2" <?php echo $info['ispublic']==2?'checked="checked"':''; ?>><b><?php echo __('Featured');?></b> <?php echo __('(on front-page sidebar)');?>
    <br/>
    <input type="radio" name="ispublic" value="1" <?php echo $info['ispublic']==1?'checked="checked"':''; ?>><b><?php echo __('Public');?></b> <?php echo __('(publish)');?>
    <br/>
    <input type="radio" name="ispublic" value="0" <?php echo !$info['ispublic']?'checked="checked"':''; ?>><?php echo __('Private');?> <?php echo __('(internal)');?>
    <br/>
    <div class="error"><?php echo $errors['ispublic']; ?></div>
    </div>

<div style="margin-top:20px"></div>

<ul class="nav nav-tabs">
    <li class="nav-item"><a href="#info" data-toggle="tab" aria-expanded="true" class="nav-link active" ><?php echo __('Category Information'); ?></a></li>
    <li class="nav-item"><a href="#notes" data-toggle="tab" aria-expanded="false" class="nav-link"><?php echo __('Internal Notes'); ?></a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="info">

    <?php
$langs = Internationalization::getConfiguredSystemLanguages();
if (count($langs) > 1) { ?>
    <ul class="nav nav-tabs" id="trans">
        <!--<li class="nav-item empty"><i class="icon-globe" title="This content is translatable"></i></li>-->
<?php foreach ($langs as $tag=>$i) {
    list($lang, $locale) = explode('_', $tag);
 ?>
    <li class="nav-item "><a data-toggle="tab" class="nav-link <?php if ($tag == $cfg->getPrimaryLanguage()) echo "active";
        ?>" href="#lang-<?php echo $tag; ?>" title="<?php
        echo Internationalization::getLanguageDescription($tag);
    ?>"><span class="flag flag-<?php echo strtolower($i['flag'] ?: $locale ?: $lang); ?>"></span>
    </a></li>
<?php } ?>
    </ul>
    <div class="tab-content">

<?php
} ?>


<?php foreach ($langs as $tag=>$i) {
    $code = $i['code'];
    $cname = 'name';
    $dname = 'description';
    if ($tag == $cfg->getPrimaryLanguage()) {
        $category = $info[$cname];
        $desc = $info[$dname];
    }
    else {
        $category = $info['trans'][$code][$cname];
        $desc = $info['trans'][$code][$dname];
        $cname = "trans[$code][$cname]";
        $dname = "trans[$code][$dname]";
    } ?>
    <div class="tab-pane fade <?php
        if ($tag == $cfg->getPrimaryLanguage()) echo "show active";
      ?>" id="lang-<?php echo $tag; ?>"
      <?php if ($i['direction'] == 'rtl') echo 'dir="rtl" class="rtl"'; ?>
    >
    
    <div><strong>Language: </strong><span class="text-danger"><?php
        echo Internationalization::getLanguageDescription($tag);?></span>
    </div>
    <div style="padding-bottom:8px;">
        <b><?php echo __('Category Name');?></b>:
        <span class="error">*</span>
        <div class="faded"><?php echo __('Short descriptive name.');?></div>
    </div>
    <input type="text" class="form-control"
        name="<?php echo $cname; ?>" value="<?php echo $category; ?>">
    <div class="error"><?php echo $errors['name']; ?></div>

    <div style="padding:8px 0;">
        <b><?php echo __('Category Description');?></b>:
        <span class="error">*</span>
        <div class="faded"><?php echo __('Summary of the category.');?></div>
        <div class="error"><?php echo $errors['description']; ?></div>
    </div>
    <textarea class="richtext" name="<?php echo $dname; ?>" cols="21" rows="12"
        style="width:100%;"><?php
        echo $desc; ?></textarea>
    </div>
<?php } ?>
   
   </div>
    
    </div>
    
    <div class="tab-pane fade" id="notes" >
        <b><?php echo __('Internal Notes');?></b>:
        <span class="faded"><?php echo __("Be liberal, they're internal");?></span>
        <textarea class="richtext no-bar" name="notes" cols="21"
            rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
    </div>
</div>


</div>
</div>



<div class="row" style="margin-top: 10px;">
 <div class="col">
    <input type="submit" class="btn btn-sm btn-primary" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  class="btn btn-sm btn-warning" name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" class="btn btn-sm btn-danger" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="categories.php"'>
</div>
</div>
</div>
</form>
