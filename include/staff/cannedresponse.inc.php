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
<form action="canned.php?<?php echo Http::build_query($qs); ?>" method="post" class="save" enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo $title; ?>
         <?php if (isset($info['title'])) { ?><small>
    — <?php echo $info['title']; ?></small>
     <?php } ?><i class="help-tip icon-question-sign" href="#canned_response"></i>
</h2>
 <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr><td></td><td></td></tr> <!-- For fixed table layout -->
        <tr>
            <th colspan="2">
                <em><?php echo __('Canned response settings');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required"><?php echo __('Status');?>:</td>
            <td>
                <label><input type="radio" name="isenabled" value="1" <?php
                    echo $info['isenabled']?'checked="checked"':''; ?>>&nbsp;<?php echo __('Active'); ?>&nbsp;</label>
                <label><input type="radio" name="isenabled" value="0" <?php
                        echo !$info['isenabled']?'checked="checked"':''; ?>>&nbsp;<?php echo __('Disabled'); ?>&nbsp;</label>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['isenabled']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required"><?php echo __('Department');?>:</td>
            <td>
                <select name="dept_id">
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
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['dept_id']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Canned Response');?></strong>: <?php echo __('Make the title short and clear.');?>&nbsp;</em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <div><b><?php echo __('Title');?></b><span class="error">*&nbsp;<?php echo $errors['title']; ?></span></div>
                <input type="text" size="70" name="title" value="<?php echo $info['title']; ?>">
                <br><br>
                <div style="margin-bottom:0.5em"><b><?php echo __('Canned Response'); ?></b>
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
                <div><h3><?php echo __('Canned Attachments'); ?> <?php echo __('(optional)'); ?>
                &nbsp;<i class="help-tip icon-question-sign" href="#canned_attachments"></i></h3>
                <div class="error"><?php echo $errors['files']; ?></div>
                </div>
                <?php
                $attachments = $canned_form->getField('attachments');
                if ($canned && $attachments) {
                    $attachments->setAttachments($canned->attachments);
                }
                print $attachments->render(); ?>
                <br/>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Notes');?></strong>: <?php echo __('Notes about the canned response.');?>&nbsp;</em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
 <?php if ($canned && $canned->getFilters()) { ?>
    <br/>
    <div id="msg_warning"><?php echo __('Canned response is in use by email filter(s)');?>: <?php
    echo implode(', ', $canned->getFilters()); ?></div>
 <?php } ?>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>" onclick="javascript:
        $(this.form).find('textarea.richtext')
            .redactor('deleteDraft');
        location.reload();" />
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick='window.location.href="canned.php"'>
</p>
</form>
