<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$matches=Filter::getSupportedMatches();
$match_types=Filter::getSupportedMatchTypes();

$info = $qs = array();
if($filter && $_REQUEST['a']!='add'){
    $title=__('Update Filter');
    $action='update';
    $submit_text=__('Save Changes');
    $info=array_merge($filter->getInfo(),$filter->getFlatRules());
    $info['id']=$filter->getId();
    $qs += array('id' => $filter->getId());
}else {
    $title=__('Add New Filter');
    $action='add';
    $submit_text=__('Add Filter');
    $info['isactive']=isset($info['isactive'])?$info['isactive']:0;
    $qs += array('a' => $_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="filters.php?<?php echo Http::build_query($qs); ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2><?php echo __('Ticket Filter');?></h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><?php echo __('Filters are executed based on execution order. Filter can target specific ticket source.');?></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
              <?php echo __('Filter Name');?>:
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
              <?php echo __('Execution Order');?>:
            </td>
            <td>
                <input type="text" size="6" name="execorder" value="<?php echo $info['execorder']; ?>">
                <em>(1...99)</em>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['execorder']; ?></span>
                &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="stop_onmatch" value="1" <?php echo $info['stop_onmatch']?'checked="checked"':''; ?> >
                <?php echo __('<strong>Stop</strong> processing further on match!');?>
                &nbsp;<i class="help-tip icon-question-sign" href="#execution_order"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Filter Status');?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo
                $info['isactive']?'checked="checked"':''; ?>> <?php echo __('Active'); ?>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>
                > <?php echo __('Disabled'); ?>
                &nbsp;<span class="error">*&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Target Channel');?>:
            </td>
            <td>
                <select name="target">
                   <option value="">&mdash; <?php echo __('Select a Channel');?> &dash;</option>
                   <?php
                   foreach(Filter::getTargets() as $k => $v) {
                       echo sprintf('<option value="%s" %s>%s</option>',
                               $k, (($k==$info['target'])?'selected="selected"':''), $v);
                    }
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' email ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)) {
                        echo sprintf('<OPTGROUP label="%s">', __('System Emails'));
                        while(list($id,$email,$name)=db_fetch_row($res)) {
                            $selected=($info['email_id'] && $id==$info['email_id'])?'selected="selected"':'';
                            if($name)
                                $email=Format::htmlchars("$name <$email>");
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$email);
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>
                &nbsp;
                <span class="error">*&nbsp;<?php echo $errors['target']; ?></span>&nbsp;
                <i class="help-tip icon-question-sign" href="#target_channel"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Filter Rules');?></strong>: <?php
                echo __('Rules are applied based on the criteria.');?>&nbsp;<span class="error">*&nbsp;<?php echo
                $errors['rules']; ?></span></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
               <em><?php echo __('Rules Matching Criteria');?>:</em>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="radio" name="match_all_rules" value="1" <?php echo $info['match_all_rules']?'checked="checked"':''; ?>><?php echo __('Match All');?>
                &nbsp;&nbsp;&nbsp;
                <input type="radio" name="match_all_rules" value="0" <?php echo !$info['match_all_rules']?'checked="checked"':''; ?>><?php echo __('Match Any');?>
                &nbsp;<span class="error">*&nbsp;</span>
                <em>(<?php echo __('case-insensitive comparison');?>)</em>
                &nbsp;<i class="help-tip icon-question-sign" href="#rules_matching_criteria"></i>

            </td>
        </tr>
        <?php
        $n=($filter?$filter->getNumRules():0)+2; //2 extra rules of unlimited.
        for($i=1; $i<=$n; $i++){ ?>
        <tr id="r<?php echo $i; ?>">
            <td colspan="2">
                <div>
                    <select style="max-width: 200px;" name="rule_w<?php echo $i; ?>">
                        <option value="">&mdash; <?php echo __('Select One');?> &mdash;</option>
                        <?php
                        foreach ($matches as $group=>$ms) { ?>
                            <optgroup label="<?php echo __($group); ?>"><?php
                            foreach ($ms as $k=>$v) {
                                $sel=($info["rule_w$i"]==$k)?'selected="selected"':'';
                                echo sprintf('<option value="%s" %s>%s</option>',
                                    $k,$sel,__($v));
                            } ?>
                        </optgroup>
                        <?php } ?>
                    </select>
                    <select name="rule_h<?php echo $i; ?>">
                        <option value="0">&mdash; <?php echo __('Select One');?> &dash;</option>
                        <?php
                        foreach($match_types as $k=>$v){
                            $sel=($info["rule_h$i"]==$k)?'selected="selected"':'';
                            echo sprintf('<option value="%s" %s>%s</option>',
                                $k,$sel,$v);
                        }
                        ?>
                    </select>&nbsp;
                    <input class="ltr" type="text" size="60" name="rule_v<?php echo $i; ?>" value="<?php echo $info["rule_v$i"]; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors["rule_$i"]; ?></span>
                <?php
                if($info["rule_w$i"] || $info["rule_h$i"] || $info["rule_v$i"]){ ?>
                <div class="pull-right" style="padding-right:20px;"><a href="#" class="clearrule">(<?php echo __('clear');?>)</a></div>
                <?php
                } ?>
                </div>
            </td>
        </tr>
        <?php
            if($i>=25) //Hardcoded limit of 25 rules...also see class.filter.php
               break;
        } ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Filter Actions');?></strong>: <?php
                echo __('Can be overwridden by other filters depending on processing order.');?>&nbsp;</em>
            </th>
        </tr>
    </tbody>
    <tbody id="dynamic-actions">
<?php
$existing = array();
if ($filter) { foreach ($filter->getActions() as $A) {
    $existing[] = $A->type;
?>
        <tr><td><?php echo $A->getImpl()->getName(); ?>:</td>
            <td><div style="position:relative"><?php
                $form = $A->getImpl()->getConfigurationForm($_POST ?: false);
                // XXX: Drop this when the ORM supports proper caching
                $form->isValid();
                include STAFFINC_DIR . 'templates/dynamic-form-simple.tmpl.php';
?>
                <input type="hidden" name="actions[]" value="I<?php echo $A->getId(); ?>"/>
                <div class="pull-right" style="position:absolute;top:2px;right:2px;">
                    <a href="#" title="<?php echo __('clear'); ?>" onclick="javascript:
        if (!confirm(__('You sure?')))
            return false;
        $(this).closest('td').find('input[name=\'actions[]\']')
            .val(function(i,v) { return 'D' + v.substring(1); });
        $(this).closest('tr').fadeOut(400, function() { $(this).hide(); });
        return false;"><i class="icon-trash"></i></a>
                </div>
</div>
            </td>
        </tr>
<?php } } ?>
    </tbody>
    <tbody>
        <tr>
            <td><strong>
                <?php echo __('Add'); ?>:
            </strong></td>
            <td>
                <select name="new-action" id="new-action-select"
                    onchange="javascript: $('#new-action-btn').trigger('click');">
                    <option value=""><?php echo __('— Select an Action —'); ?></option>
<?php foreach (FilterAction::allRegistered() as $type=>$name) {
    if (in_array($type, $existing))
        continue;
?>
                    <option data-title="<?php echo $name; ?>" value="<?php echo $type; ?>"><?php echo $name; ?></option>
<?php } ?>
                </select>
                <input id="new-action-btn" type="button" value="<?php echo __('Add'); ?>"
                onclick="javascript:
        var selected = $('#new-action-select').find(':selected');
        $('#dynamic-actions')
          .append($('<tr></tr>')
            .append($('<td></td>')
              .text(selected.data('title') + ':')
            ).append($('<td></td>')
              .append($('<em></em>').text(__('Loading ...')))
              .load('ajax.php/filter/action/' + selected.val() + '/config', function() {
                selected.prop('disabled', true);
              })
            )
          ).append(
            $('<input>').attr({type:'hidden',name:'actions[]',value:'N'+selected.val()})
          );"/>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Notes');?></strong>: <?php
                    echo __("be liberal, they're internal");?></em>
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
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="filters.php"'>
</p>
</form>
