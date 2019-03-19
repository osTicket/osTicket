<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $qs = array();
if($sla && $_REQUEST['a']!='add'){
    $title=__('Update SLA Plan' /* SLA is abbreviation for Service Level Agreement */);
    $action='update';
    $submit_text=__('Save Changes');
    $info=$sla->getInfo();
    $info['id']=$sla->getId();
    $trans['name'] = $sla->getTranslateTag('name');
    $qs += array('id' => $sla->getId());
}else {
    $title=__('Add New SLA Plan' /* SLA is abbreviation for Service Level Agreement */);
    $action='add';
    $submit_text=__('Add Plan');
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $info['enable_priority_escalation']=isset($info['enable_priority_escalation'])?$info['enable_priority_escalation']:1;
    $info['disable_overdue_alerts']=isset($info['disable_overdue_alerts'])?$info['disable_overdue_alerts']:0;
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="slas.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo $title; ?>
    <?php if (isset($info['name'])) { ?><small>
    — <?php echo $info['name']; ?></small>
     <?php } ?>
</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('Tickets are marked overdue on grace period violation.');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
              <?php echo __('Name');?>:
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo $info['name']; ?>"
                    autofocus data-translate-tag="<?php echo $trans['name']; ?>"/>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#name"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
              <?php echo __('Grace Period');?>:
            </td>
            <td>
                <input type="text" size="10" name="grace_period" value="<?php echo $info['grace_period']; ?>">
                <em>( <?php echo __('in hours');?> )</em>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['grace_period']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#grace_period"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Status');?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong><?php echo __('Active');?></strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>><?php echo __('Disabled');?>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['isactive']; ?></span>
            </td>
        </tr>

        <tr>
            <td width="180">
                Work Hours:
            </td>
            <td>
    <table style="text-align:center;">
  <tbody>
  <tr>
    <th></th>
    <th>None</th>
    <th>24 hrs</th>
    <th>Timed</th>
    <th>Working hours</th>
  </tr>
  <tr>
    <td>Sunday</td>
    <td>
         <input type="radio" name="sun_mode" value="0" onclick="$('.work-hours').prop('disabled', false); $('.work-hours').prop('disabled', true); $('#sun-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sun-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' );$('#sun-time1').val(''); $('#sun-time').val('');" <?php echo ($info['sun_mode']==0)?'checked="checked"':''; ?>>
    </td>
    <td>
        <input type="radio" name="sun_mode" value="1" onclick="$('.work-hours').prop('disabled', false); $('.work-hours').prop('disabled', true); $('#sun-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sun-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sun-time1').val(''); $('#sun-time').val('');" <?php echo ($info['sun_mode']==1)?'checked="checked"':''; ?>>
    </td>
    <td>
    <input type="radio" name="sun_mode" value="2" onclick="$('.work-hours').prop('disabled', false); $('#sun-time' ).removeClass( 'disable-time' ); $('#sun-time1' ).removeClass( 'disable-time' );" <?php echo ($info['sun_mode']==2)?'checked="checked"':''; ?>>
    </td>
    <td>
        <input type="time" name="sun_start_time" placeholder="e.g. 00:00" id="sun-time" class="work-hours <?php echo ($info['sun_mode']!=2)?'disable-time':''; ?> " <?php echo ($info['sun_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['sun_start_time']) && $info['sun_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['sun_start_time'] . ':00')) . '"') ?>> to 
        <input type="time" name="sun_end_time" placeholder="e.g. 00:00" id="sun-time1" class="work-hours <?php echo ($info['sun_mode']!=2)?'disable-time':''; ?> " <?php echo ($info['sun_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['sun_end_time']) && $info['sun_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['sun_end_time'] . ':00')) . '"') ?>>
        &nbsp;<span class="error">*&nbsp;<?php echo $errors['sun_start_time']; ?></span>
    </td>
  </tr>
  <tr>
    <td>Monday</td>
     <td>
        <input type="radio" name="mon_mode" value="0" onclick="$('.work-hours1').prop('disabled', false); $('.work-hours1').prop('disabled', true); $('#mon-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#mon-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#mon-time1').val(''); $('#mon-time').val('');"<?php echo ($info['mon_mode']==0)?'checked="checked"':''; ?>>		</td>
    <td>
        <input type="radio" name="mon_mode" value="1" onclick="$('.work-hours1').prop('disabled', false); $('.work-hours1').prop('disabled', true); $('#mon-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#mon-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#mon-time1').val(''); $('#mon-time').val('');"<?php echo ($info['mon_mode']==1)?'checked="checked"':''; ?>>
      </td>
       <td>
        <input type="radio" name="mon_mode" value="2" onclick="$('.work-hours1').prop('disabled', false); $('#mon-time' ).removeClass( 'disable-time' ); $('#mon-time1' ).removeClass( 'disable-time' );" <?php echo ($info['mon_mode']==2)?'checked="checked"':''; ?>>
      </td>
      <td>
      <input type="time" name="mon_start_time" placeholder="e.g. 00:00" id="mon-time" class="work-hours1 <?php echo ($info['mon_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['mon_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['mon_start_time']) && $info['mon_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['mon_start_time'] . ':00')) . '"') ?>> to 
      <input type="time" name="mon_end_time" placeholder="e.g. 00:00" id="mon-time1" class="work-hours1 <?php echo ($info['mon_mode']!=2)?'disable-time':''; ?>"<?php echo ($info['mon_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['mon_end_time']) && $info['mon_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['mon_end_time'] . ':00')) . '"') ?>>
      &nbsp;<span class="error">*&nbsp;<?php echo $errors['mon_start_time']; ?></span>

      </td>
  </tr>
  <tr>
    <td>Tuesday</td>
     <td>
        <input type="radio" name="tue_mode" value="0" onclick="$('.work-hours2').prop('disabled', false); $('.work-hours2').prop('disabled', true); $('#tue-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#tue-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#tue-time1').val(''); $('#tue-time').val('');"<?php echo ($info['tue_mode']==0)?'checked="checked"':''; ?>>		</td>
    <td>
        <input type="radio" name="tue_mode" value="1" onclick="$('.work-hours2').prop('disabled', false); $('.work-hours2').prop('disabled', true); $('#tue-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#tue-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#tue-time1').val(''); $('#tue-time').val('');"<?php echo ($info['tue_mode']==1)?'checked="checked"':''; ?>>
      </td>
       <td>
        <input type="radio" name="tue_mode" value="2" onclick="$('.work-hours2').prop('disabled', false); $('#tue-time' ).removeClass( 'disable-time' ); $('#tue-time1' ).removeClass( 'disable-time' );"<?php echo ($info['tue_mode']==2)?'checked="checked"':''; ?>>
      </td>
       <td>
       <input type="time" name="tue_start_time" placeholder="e.g. 00:00" id="tue-time" class="work-hours2 <?php echo ($info['tue_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['tue_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['tue_start_time']) && $info['tue_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['tue_start_time'] . ':00')) . '"') ?>> to 
       <input type="time" name="tue_end_time" placeholder="e.g. 00:00" id="tue-time1" class="work-hours2 <?php echo ($info['tue_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['tue_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['tue_end_time']) && $info['tue_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['tue_end_time'] . ':00')) . '"') ?>>
       &nbsp;<span class="error">*&nbsp;<?php echo $errors['tue_start_time']; ?></span>
       


      </td>
  </tr>
  <tr>
    <td>Wednesday</td>
     <td>
        <input type="radio" name="wed_mode" value="0" onclick="$('.work-hours3').prop('disabled', false); $('.work-hours3').prop('disabled', true);  $('#wed-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#wed-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#wed-time1').val(''); $('#wed-time').val('');"<?php echo ($info['wed_mode']==0)?'checked="checked"':''; ?>>		</td>
    <td>
        <input type="radio" name="wed_mode" value="1"onclick="$('.work-hours3').removeClass('disable-time'); $('.work-hours3').addClass('disable-time'); $('#wed-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#wed-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#wed-time1').val(''); $('#wed-time').val('');"<?php echo ($info['wed_mode']==1)?'checked="checked"':''; ?>>
      </td>
       <td>
        <input type="radio" name="wed_mode" value="2" onclick="$('.work-hours3').prop('disabled', false); $('#wed-time' ).removeClass( 'disable-time' ); $('#wed-time1' ).removeClass( 'disable-time' );"<?php echo ($info['wed_mode']==2)?'checked="checked"':''; ?>>
      </td>
       <td>
       <input type="time" name="wed_start_time" placeholder="e.g. 00:00" id="wed-time" class="work-hours3 <?php echo ($info['wed_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['wed_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['wed_start_time']) && $info['wed_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['wed_start_time'] . ':00')) . '"') ?>> to 
       <input type="time" name="wed_end_time" placeholder="e.g. 00:00" id="wed-time1" class="work-hours3 <?php echo ($info['wed_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['wed_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['wed_end_time']) && $info['wed_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['wed_end_time'] . ':00')) . '"') ?>>
       &nbsp;<span class="error">*&nbsp;<?php echo $errors['wed_start_time']; ?></span>

      </td>
  </tr>
  <tr>
    <td>Thursday</td>
    <td>
        <input type="radio" name="thu_mode" value="0" onclick="$('.work-hours4').prop('disabled', false); $('.work-hours4').prop('disabled', true); $('#thu-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#thu-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#thu-time1').val(''); $('#thu-time').val('');"<?php echo ($info['thu_mode']==0)?'checked="checked"':''; ?>>		</td>
    <td>
        <input type="radio" name="thu_mode" value="1" onclick="$('.work-hours4').prop('disabled', false); $('.work-hours4').prop('disabled', true); $('#thu-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#thu-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#thu-time1').val(''); $('#thu-time').val('');"<?php echo ($info['thu_mode']==1)?'checked="checked"':''; ?>>
      </td>
       <td>
        <input type="radio" name="thu_mode" value="2" onclick="$('.work-hours4').prop('disabled', false); $('#thu-time' ).removeClass( 'disable-time' ); $('#thu-time1' ).removeClass( 'disable-time' );"<?php echo ($info['thu_mode']==2)?'checked="checked"':''; ?>>
      </td>
       <td>
       <input type="time" name="thu_start_time" placeholder="e.g. 00:00" id="thu-time" class="work-hours4 <?php echo ($info['thu_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['thu_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['thu_start_time']) && $info['thu_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['thu_start_time'] . ':00')) . '"') ?>> to 
       <input type="time" name="thu_end_time" placeholder="e.g. 00:00" id="thu-time1" class="work-hours4 <?php echo ($info['thu_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['thu_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['thu_end_time']) && $info['thu_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['thu_end_time'] . ':00')) . '"') ?>>
       &nbsp;<span class="error">*&nbsp;<?php echo $errors['thu_start_time']; ?></span>

      </td>
  </tr>
  <tr>
    <td>Friday</td>
     <td>
        <input type="radio" name="fri_mode" value="0" onclick="$('.work-hours5').prop('disabled', false); $('.work-hours5').prop('disabled', true); $('#fri-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#fri-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#fri-time1').val(''); $('#fri-time').val('');"<?php echo ($info['fri_mode']==0)?'checked="checked"':''; ?>>		</td>
    <td>
        <input type="radio" name="fri_mode" value="1" onclick="$('.work-hours5').prop('disabled', false); $('.work-hours5').prop('disabled', true); $('#fri-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#fri-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#fri-time1').val(''); $('#fri-time').val('');"<?php echo ($info['fri_mode']==1)?'checked="checked"':''; ?>>
      </td>
       <td>
        <input type="radio" name="fri_mode" value="2" onclick="$('.work-hours5').prop('disabled', false); $('#fri-time' ).removeClass( 'disable-time' ); $('#fri-time1' ).removeClass( 'disable-time' );"<?php echo ($info['fri_mode']==2)?'checked="checked"':''; ?>>
      </td>
       <td>
       <input type="time" name="fri_start_time" placeholder="e.g. 00:00" id="fri-time" class="work-hours5 <?php echo ($info['fri_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['fri_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['fri_start_time']) && $info['fri_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['fri_start_time'] . ':00')) . '"') ?>> to 
       <input type="time" name="fri_end_time" placeholder="e.g. 00:00" id="fri-time1" class="work-hours5 <?php echo ($info['fri_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['fri_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['fri_end_time']) && $info['fri_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['fri_end_time'] . ':00')) . '"') ?>>
       &nbsp;<span class="error">*&nbsp;<?php echo $errors['fri_start_time']; ?></span>


      </td>
  </tr>
  <tr>
    <td>Saturday</td>
     <td>
        <input type="radio" name="sat_mode" value="0" onclick="$('.work-hours6').prop('disabled', false); $('.work-hours6').prop('disabled', true); $('#sat-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sat-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sat-time1').val(''); $('#sat-time').val('');"<?php echo ($info['sat_mode']==0)?'checked="checked"':''; ?>>		</td>
    <td>
        <input type="radio" name="sat_mode" value="1" onclick="$('.work-hours6').prop('disabled', false); $('.work-hours6').prop('disabled', true); $('#sat-time' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sat-time1' ).removeClass( 'disable-time' ).addClass( 'disable-time' ); $('#sat-time1').val(''); $('#sat-time').val('');"<?php echo ($info['sat_mode']==1)?'checked="checked"':''; ?>>
      </td>
       <td>
        <input type="radio" name="sat_mode" value="2" onclick="$('.work-hours6').prop('disabled', false); $('#sat-time' ).removeClass( 'disable-time' ); $('#sat-time1' ).removeClass( 'disable-time' );"<?php echo ($info['sat_mode']==2)?'checked="checked"':''; ?>>
      </td>
      <td >
         <input type="time" name="sat_start_time" placeholder="e.g. 00:00" id="sat-time" class="work-hours6 <?php echo ($info['sat_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['sat_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['sat_start_time']) && $info['sat_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['sat_start_time'] . ':00')) . '"') ?>> to 
         <input type="time" name="sat_end_time" placeholder="e.g. 00:00" id="sat-time1" class="work-hours6 <?php echo ($info['sat_mode']!=2)?'disable-time':''; ?>" <?php echo ($info['sat_mode']!=2)?'disabled="disabled"':''; ?> <?php if (isset($info['sat_end_time']) && $info['sat_mode'] == 2) echo __(' value="' . date('H:i', strtotime('1970-01-01 ' . $info['sat_end_time'] . ':00')) . '"') ?>>
         &nbsp;<span class="error">*&nbsp;<?php echo $errors['sat_start_time']; ?></span>

      </td>
  </tr>
</tbody></table>
    
             
            </td>
        </tr>

        <tr>
            <td width="180">
                <?php echo __('Transient'); ?>:
            </td>
            <td>
                <input type="checkbox" name="transient" value="1" <?php echo $info['transient']?'checked="checked"':''; ?> >
                <?php echo __('SLA can be overridden on ticket transfer or help topic change'); ?>
                &nbsp;<i class="help-tip icon-question-sign" href="#transient"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Ticket Overdue Alerts');?>:
            </td>
            <td>
                <input type="checkbox" name="disable_overdue_alerts" value="1" <?php echo $info['disable_overdue_alerts']?'checked="checked"':''; ?> >
                    <?php echo __('<strong>Disable</strong> overdue alerts notices.'); ?>
                    <em><?php echo __('(Override global setting)'); ?></em>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Notes');?></strong>: <?php echo __("Be liberal, they're internal");?>
                </em>
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
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="slas.php"'>
</p>
</form>
