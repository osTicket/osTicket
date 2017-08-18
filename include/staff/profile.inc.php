<?php
if(!defined('OSTSTAFFINC') || !$staff || !$thisstaff) die('Access Denied');
?>

<div class="subnav">

    <div class="float-left subnavtitle" id="ticketviewtitle">
        <?php echo __('My Account Profile');?>

    </div>

    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
    &nbsp;
    </div>
    <div class="clearfix"></div>
</div>


<div class="card-box">
<div class="row">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

<form action="profile.php" method="post" class="save" autocomplete="off">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="update">
 <input type="hidden" name="id" value="<?php echo $staff->getId(); ?>">

  <ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link active" href="#account" data-toggle="tab" ><?php echo __('Account'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#preferences" data-toggle="tab" ><?php echo __('Preferences'); ?></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#signature" data-toggle="tab" ><?php echo __('Signature'); ?></a>
    </li>
  </ul>
<div class="tab-content">
  <div class="tab-pane active" id="account">
<div class="row">
  <div class="col-md-3">
        
        <div class="form-group">
          <label><?php echo __('First Name'); ?>:</label>
       
            <input class="form-control form-control-sm requiredfield" type="text" maxlength="64" name="firstname"
              autofocus value="<?php echo Format::htmlchars($staff->firstname); ?>"
              placeholder="<?php echo __("First Name"); ?>" />
              <?php if( $errors['firstname']) {?>
              <span class="error"><?php echo $errors['firstname']; ?></span>
              <?php } ?>
           </div> 
        <div class="form-group">
          <label><?php echo __('Last Name'); ?>:</label> 
            
            
            <input class="form-control form-control-sm requiredfield" type="text" maxlength="64" name="lastname"
              value="<?php echo Format::htmlchars($staff->lastname); ?>"
              placeholder="<?php echo __("Last Name"); ?>" />
            
            <?php if( $errors['lastname']) {?>
              <span class="error"><?php echo $errors['lastname']; ?></span>
              <?php } ?>
          
        </div>
        <div class="form-group">
          <label><?php echo __('Email Address'); ?>:</label>
          
            <input type="email" maxlength="64"  name="email"
              value="<?php echo Format::htmlchars($staff->email); ?>"
              placeholder="<?php echo __('e.g. me@mycompany.com'); ?>" class="form-control form-control-sm requiredfield"/>
            <div class="error"><?php echo $errors['email']; ?></div>
        </div>
</div>
 <div class="col-md-3">
        <div class="form-group">
          <label><?php echo __('Phone Number');?>:</label>
          
            <input type="tel" name="phone" class="form-control form-control-sm auto phone"
              value="<?php echo Format::htmlchars($staff->phone); ?>" />
           </div>
           <div class="form-group">
          <label> <?php echo __('Ext');?></label>
            <input type="text" name="phone_ext"
              value="<?php echo Format::htmlchars($staff->phone_ext); ?>" class="form-control form-control-sm">
            <div class="error"><?php echo $errors['phone']; ?></div>
            <div class="error"><?php echo $errors['phone_ext']; ?></div>
            </div>
       <div class="form-group">
          <label><?php echo __('Mobile Number');?>:</label>
         
            <input type="tel" name="mobile" class="form-control form-control-sm auto phone"
              value="<?php echo Format::htmlchars($staff->mobile); ?>" />
            <div class="error"><?php echo $errors['mobile']; ?></div>
        </div>
       
 </div>
 
<div class="col-md-3">
      <!-- ================================================ -->
     <div class="form-group">
     
       
          <label><?php echo __('Username'); ?>:</label>
    
          
            <input type="text" size="40" style="width:300px"
              class="form-control form-control-sm staff-username typeahead"
              name="username" disabled value="<?php echo Format::htmlchars($staff->username); ?>" />
              
<?php if (!$bk || $bk->supportsPasswordChange()) { ?>
            <button type="button" id="change-pw-button" class="btn btn-warning btn-sm" onclick="javascript:
            $.dialog('ajax.php/staff/'+<?php echo $staff->getId(); ?>+'/change-password', 201);">
              <i class="icon-refresh"></i> <?php echo __('Change Password'); ?>
            </button>
<?php } ?>
            <i class="offset help-tip icon-question-sign" href="#username"></i>
            <div class="error"><?php echo $errors['username']; ?></div>
            </div>
          
</div>
<div class="col-md-3">
      <!-- ================================================ -->
      
      <label class="custom-control custom-checkbox">
<input type="checkbox" class="custom-control-input" name="show_assigned_tickets"
              <?php echo $cfg->showAssignedTickets() ? 'disabled="disabled" ' : ''; ?>
              <?php echo $staff->show_assigned_tickets ? 'checked="checked"' : ''; ?>>
<span class="custom-control-indicator"></span>
<span class="custom-control-description"><?php echo __('Show assigned tickets on open queue.'); ?>
            <i class="help-tip icon-question-sign" href="#show_assigned_tickets"></i></span>
</label>
      
<label class="custom-control custom-checkbox">
<input type="checkbox" class="custom-control-input"  name="onvacation"
              <?php echo ($staff->onvacation) ? 'checked="checked"' : ''; ?>>
<span class="custom-control-indicator"></span>
<span class="custom-control-description"><?php echo __('Vacation Mode'); ?>
 </span>
</label>     
      

  </div>
  </div>
</div>
  <!-- =================== PREFERENCES ======================== -->

  <div class="tab-pane" id="preferences">
  <div class="row">
  <div class="col-md-3">  
        <div class="form-group">
            <label><?php echo __('Maximum Page size');?>:</label>
           
            <span class="input-group">
                <select name="max_page_size" class="form-control form-control-sm">
                    <option value="0">&mdash; <?php echo __('System Default');?> &mdash;</option>
                    <?php
                    $pagelimit = $staff->max_page_size ?: $cfg->getPageSize();
                    for ($i = 5; $i <= 100; $i += 5) {
                        $sel=($pagelimit==$i)?'selected="selected"':'';
                         echo sprintf('<option value="%d" %s>'.__('show %s records').'</option>',$i,$sel,$i);
                    } ?>
                </select> &nbsp;<?php echo __('per page.');?></span>
            
        </div>
        <div class="form-group">
            <label><?php echo __('Auto Refresh Rate');?>:
              </label>
            
                <select name="auto_refresh_rate" class="form-control form-control-sm">
                  <option value="0">&mdash; <?php echo __('Disabled');?> &mdash;</option>
                  <?php
                  $y=1;
                   for($i=1; $i <=30; $i+=$y) {
                     $sel=($staff->auto_refresh_rate==$i)?'selected="selected"':'';
                     echo sprintf('<option value="%d" %s>%s</option>', $i, $sel,
                        sprintf(_N('Every minute', 'Every %d minutes', $i), $i));
                     if($i>9)
                        $y=2;
                   } ?>
                </select>
                <span class="faded"><?php echo __('Tickets page refresh rate in minutes.'); ?></span>
            
        </div>

        <div class="form-group">
            <label><?php echo __('Default From Name');?>:</label>
              
            
                <select name="default_from_name" class="form-control form-control-sm">
                  <?php
                   $options=array(
                           'email' => __("Email Address Name"),
                           'dept' => sprintf(__("Department Name (%s)"),
                               __('if public' /* This is used in 'Department's Name (>if public<)' */)),
                           'mine' => __('My Name'),
                           '' => '— '.__('System Default').' —',
                           );
                  if ($cfg->hideStaffName())
                    unset($options['mine']);

                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k,($staff->default_from_name==$k)?'selected="selected"':'',$v);
                  }
                  ?>
                </select><div class="faded"><?php echo __('From name to use when replying to a thread');?></div>
                <div class="error"><?php echo $errors['default_from_name']; ?></div>
            
        </div>
    </div>
  <div class="col-md-3">
        <div class="form-group">
            <label><?php echo __('Thread View Order');?>:</label>
              
            
                <select name="thread_view_order" class="form-control form-control-sm">
                  <?php
                   $options=array(
                           'desc' => __('Descending'),
                           'asc' => __('Ascending'),
                           '' => '— '.__('System Default').' —',
                           );
                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k
                                ,($staff->thread_view_order == $k) ? 'selected="selected"' : ''
                                ,$v);
                  }
                  ?>
                </select><div class="faded"><?php echo __('The order of thread entries');?></div>
                <div class="error"><?php echo $errors['thread_view_order']; ?></div>
           </div>
           <div class="form-group">
            <label><?php echo __('Default Signature');?>:</label>
             <select name="default_signature_type" class="form-control form-control-sm">
                  <option value="none" selected="selected">&mdash; <?php echo __('None');?> &mdash;</option>
                  <?php
                   $options=array('mine'=>__('My Signature'),'dept'=>sprintf(__('Department Signature (%s)'),
                       __('if set' /* This is used in 'Department Signature (>if set<)' */)));
                  foreach($options as $k=>$v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $k,($staff->default_signature_type==$k)?'selected="selected"':'',$v);
                  }
                  ?>
                </select> <div class="faded"><?php echo __('This can be selected when replying to a thread');?></div>
                <div class="error"><?php echo $errors['default_signature_type']; ?></div>
            </div>
            <div class="form-group">
            <label><?php echo __('Default Paper Size');?>:</label>
                <select name="default_paper_size" class="form-control form-control-sm">
                  <option value="none" selected="selected">&mdash; <?php echo __('None');?> &mdash;</option>
                  <?php

                  foreach(Export::$paper_sizes as $v) {
                      echo sprintf('<option value="%s" %s>%s</option>',
                                $v,($staff->default_paper_size==$v)?'selected="selected"':'',__($v));
                  }
                  ?>
                </select>
                <div class="faded"><?php echo __('Paper size used when printing tickets to PDF');?></div>
                <div class="error"><?php echo $errors['default_paper_size']; ?></div>
            </div>
            
    </div>
    
<div class="col-md-3">
      
        <div class="form-group">
            <label><?php echo __('Time Zone');?>:</label>
            
                <?php
                $TZ_NAME = 'timezone';
                $TZ_TIMEZONE = $staff->timezone;
                include STAFFINC_DIR.'templates/timezone.tmpl.php'; ?>
                <div class="error"><?php echo $errors['timezone']; ?></div>
           
        </div>
         <div class="form-group">
        <label><?php echo __('Time Format');?>:</label>
            
                <select name="datetime_format" class="form-control form-control-sm">
<?php
    $datetime_format = $staff->datetime_format;
    foreach (array(
    'relative' => __('Relative Time'),
    '' => '— '.__('System Default').' —',
) as $v=>$name) { ?>
                    <option value="<?php echo $v; ?>" <?php
                    if ($v == $datetime_format)
                        echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
                
            </div>
      
<?php if ($cfg->getSecondaryLanguages()) { ?>
        <div class="form-group">
        <label><?php echo __('Preferred Language'); ?>:</label>
            
        <?php
        $langs = Internationalization::getConfiguredSystemLanguages(); ?>
                <select name="lang" class="form-control form-control-sm">
                    <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
<?php foreach($langs as $l) {
    $selected = ($staff->lang == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
<?php } ?>
                </select>
                <span class="error">&nbsp;<?php echo $errors['lang']; ?></span>
            
        </div>
<?php } ?>
</div>
<div class="col-md-3">
<?php if (extension_loaded('intl')) { ?>
        <div class="form-group">
        <label><?php echo __('Preferred Locale');?>:</label>
            
                <select name="locale" class="form-control form-control-sm">
                    <option value=""><?php echo __('Use Language Preference'); ?></option>
<?php foreach (Internationalization::allLocales() as $code=>$name) { ?>
                    <option value="<?php echo $code; ?>" <?php
                        if ($code == $staff->locale)
                            echo 'selected="selected"';
                    ?>><?php echo $name; ?></option>
<?php } ?>
                </select>
           
        </div>
<?php } ?>
    
  </div>
  </div>
   </div>

  <!-- ==================== SIGNATURES ======================== -->

  <div id="signature" class="tab-pane">
    <table class="table two-column" width="100%">
      <tbody>
        <tr class="header">
          <th colspan="2">
            <?php echo __('Signature'); ?>
            <div><small><?php echo __(
            "Optional signature used on outgoing emails.")
            .' '.
            __('Signature is made available as a choice, on ticket reply.'); ?>
            </small></div>
          </th>
        </tr>
        <tr>
            <td colspan="2">
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $staff->signature; ?></textarea>
            </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<div >
    <button class="btn btn-primary btn-sm" type="submit" name="submit" ><i class="fa fa-save"></i> <?php echo __('Save Changes'); ?></button>
    <button class="btn btn-warning btn-sm" type="reset"  name="reset"><i class="fa fa-undo"></i>
        <?php echo __('Reset');?></button>
    <button class="btn btn-danger btn-sm" type="button" name="cancel" onclick="window.history.go(-1);"><i class="fa fa-times-circle-o"></i> <?php echo __('Cancel');?></button>
</div>
    <div class="clear"></div>
</form>
</div>
</div>
</div>
<?php
if ($staff->change_passwd) { ?>
<script type="text/javascript">
    $(function() { $('#change-pw-button').trigger('click'); });
</script>
<?php
}
