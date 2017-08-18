<?php
if(!defined('OSTSCPINC') || !$thisstaff) die('Access Denied');
$info=$qs = array();
if($canned && $_REQUEST['a']!='add'){
    $title=__('Update Canned Response');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$canned->getInfo();
    $info['id']=$canned->getId();
    $qs += array('id' => $canned->getId());
    // Replace cid: scheme with downloadable URL for inline images
    $info['response'] = $canned->getResponseWithImages();
    $info['notes'] = Format::viewableImages($info['notes']);
}else {
    $title=__('Add New Canned Response');
    $action='create';
    $submit_text=__('Add Response');
    $info['isenabled']=isset($info['isenabled'])?$info['isenabled']:1;
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);

?>
<div class="subnav">

    <div class="float-left subnavtitle">
                          
   <?php echo __('Canned Responses').' / '.$info['title'];?>                        
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
   
        &nbsp;
       
    </div>
        
   <div class="clearfix"></div> 
</div> 

<div class="card-box">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

<form action="canned.php?<?php echo Http::build_query($qs); ?>" method="post" class="form-horizontal save" enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 
 <div class="form-group row">
  <em><?php echo __('Canned response settings');?> <i class="help-tip icon-question-sign" href="#canned_response"></i></em>
 </div>
 
 
<div class="form-group row">
<label class="col-2 col-form-label"><?php echo __('Status');?>:</label>

<div class="col-10">
 <label class="custom-control custom-radio">
    <input class="custom-control-input" type="radio" name="isenabled" value="1" <?php
                    echo $info['isenabled']?'checked="checked"':''; ?>>
                     <span class="custom-control-indicator"></span>
                     <span class="custom-control-description"><?php echo __('Active');?></span>
 </label>
 
 
 <label class="custom-control custom-radio">
    <input class="custom-control-input" type="radio" name="isenabled" value="0" <?php
                        echo !$info['isenabled']?'checked="checked"':''; ?> >
                         <span class="custom-control-indicator"></span>
                     <span class="custom-control-description"><?php echo __('Disabled'); ?></span>
 </label>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['isenabled'];?></span>
  </div>    
</div>  
  <div class="form-group row">
  <label class="col-2 col-form-label"><?php echo __('Department');?>: </label>
  <div class="col-10">
          <select class="form-control form-control-sm" name="dept_id">
                    <option value="0">&mdash; <?php echo __('All Departments');?> &mdash;</option>
                    <?php
                    if (($depts=Dept::getDepartments())) {
                        foreach($depts as $id => $name) {
                            $selected=($info['dept_id'] && $id==$info['dept_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
   </div>
   </div>    
<div class="form-group row">
  <label class="col-2 col-form-label"><?php echo __('Title');?>: </label>
        
    <div class="col-10">
        <input type="text" class="form-control form-control-sm" name="title" value="<?php echo $info['title']; ?>">
        <span class="error"><?php echo $errors['title']; ?></span>
    </div>
</div>
   
<div class="m-b-5"><b><?php echo __('Canned Response'); ?></b>
                    <font class="error">*&nbsp;<?php echo $errors['response']; ?></font>
                    &nbsp;&nbsp;&nbsp;(<a class="tip" href="#ticket_variables"><?php echo __('Supported Variables'); ?></a>)
                    </div>
                <textarea name="response" cols="21" rows="12"
                    data-root-context="cannedresponse"
                    style="width:98%;" class="richtext draft draft-delete" <?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('canned',
        is_object($canned) ? $canned->getId() : false, $info['response']);
    echo $attrs; ?>><?php echo $draft ?: $info['response'];
                ?></textarea>
                
                <div class="m-b-5 m-t-10"><strong><?php echo __('Canned Attachments'); ?> <?php echo __('(optional)'); ?></strong>
                &nbsp;<i class="help-tip icon-question-sign" href="#canned_attachments"></i>
                <div class="error"><?php echo $errors['files']; ?></div>
                </div>
                <?php
                $attachments = $canned_form->getField('attachments');
                if ($canned && $attachments) {
                    $attachments->setAttachments($canned->attachments);
                }
                print $attachments->render(); ?>
 
<div class="form-group row"> 
   <div class="col">
  <em><strong><?php echo __('Internal Notes');?></strong>: <?php echo __('Notes about the canned response.');?>&nbsp;</em> 
    <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
    </div>
</div>

 <?php if ($canned && $canned->getFilters()) { ?>
    <br/>
    <div id="msg_warning"><?php echo __('Canned response is in use by email filter(s)');?>: <?php
    echo implode(', ', $canned->getFilters()); ?></div>
 <?php } ?>
<p style="text-align:left;">
    <input type="submit" class="btn btn-sm btn-success" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset" class="btn btn-sm btn-warning" name="reset"  value="<?php echo __('Reset'); ?>" onclick="javascript:
        $(this.form).find('textarea.richtext')
            .redactor('deleteDraft');
        location.reload();" />
    <input type="button" class="btn btn-sm btn-danger" name="cancel" value="<?php echo __('Cancel'); ?>" onclick='window.location.href="canned.php"'>
</p>
</form>
</div>
</div>