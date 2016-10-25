<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $qs = $forms = array();
if($topic && $_REQUEST['a']!='add') {
    $title=__('Update Help Topic');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$topic->getInfo();
    $info['id']=$topic->getId();
    $info['pid']=$topic->getPid();
    $trans['name'] = $topic->getTranslateTag('name');
    $qs += array('id' => $topic->getId());
    $forms = $topic->getForms();
} else {
    $title=__('Add New Help Topic');
    $action='create';
    $submit_text=__('Add Topic');
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $qs += array('a' => $_REQUEST['a']);
    $forms = TicketForm::objects();
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>

<h2><?php echo $title; ?>
    <?php if (isset($info['topic'])) { ?><small>
    — <?php echo $info['topic']; ?></small>
<?php } ?>
 <i class="help-tip icon-question-sign" href="#help_topic_information"></i></h2>

<ul class="clean tabs" id="topic-tabs">
    <li class="active"><a href="#info"><i class="icon-info-sign"></i> <?php echo __('Help Topic Information'); ?></a></li>
    <li><a href="#routing"><i class="icon-ticket"></i> <?php echo __('New ticket options'); ?></a></li>
    <li><a href="#forms"><i class="icon-paste"></i> <?php echo __('Forms'); ?></a></li>
</ul>

<form action="helptopics.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">

<div id="topic-tabs_container">
<div class="tab_content" id="info">
 <table class="table" border="0" cellspacing="0" cellpadding="2">
    <tbody>
        <tr>
            <td width="180" class="required">
               <?php echo __('Topic');?>:
            </td>
            <td>
                <input type="text" size="30" name="topic" value="<?php echo $info['topic']; ?>"
                autofocus data-translate-tag="<?php echo $trans['name']; ?>"/>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['topic']; ?></span> <i class="help-tip icon-question-sign" href="#topic"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Status');?>:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>> <?php echo __('Active'); ?>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>> <?php echo __('Disabled'); ?>
                &nbsp;<span class="error">*&nbsp;</span> <i class="help-tip icon-question-sign" href="#status"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Type');?>:
            </td>
            <td>
                <input type="radio" name="ispublic" value="1" <?php echo $info['ispublic']?'checked="checked"':''; ?>> <?php echo __('Public'); ?>
                <input type="radio" name="ispublic" value="0" <?php echo !$info['ispublic']?'checked="checked"':''; ?>> <?php echo __('Private/Internal'); ?>
                &nbsp;<span class="error">*&nbsp;</span> <i class="help-tip icon-question-sign" href="#type"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Parent Topic');?>:
            </td>
            <td>
                <select name="topic_pid">
                    <option value="">&mdash; <?php echo __('Top-Level Topic'); ?> &mdash;</option><?php
                    $topics = Topic::getAllHelpTopics();
                    while (list($id,$topic) = each($topics)) {
                        if ($id == $info['topic_id'])
                            continue; ?>
                        <option value="<?php echo $id; ?>"<?php echo ($info['topic_pid']==$id)?'selected':''; ?>><?php echo $topic; ?></option>
                    <?php
                    } ?>
                </select> <i class="help-tip icon-question-sign" href="#parent_topic"></i>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['pid']; ?></span>
            </td>
        </tr>

    </tbody>
    </table>

        <div style="padding:8px 3px;border-bottom: 2px dotted #ddd;">
            <strong><?php echo __('Internal Notes');?>:</strong>
            <?php echo __("Be liberal, they're internal");?>
        </div>

        <textarea class="richtext no-bar" name="notes" cols="21"
            rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>

</div>

<div class="hidden tab_content" id="routing">
<div style="padding:8px 0;border-bottom: 2px dotted #ddd;">
<div><b class="big"><?php echo __('New ticket options');?></b></div>
</div>

 <table class="table" border="0" cellspacing="0" cellpadding="2">
        <tbody>
        <tr>
            <td width="180" class="required">
                <?php echo __('Department'); ?>:
            </td>
            <td>
                <select name="dept_id" data-quick-add="department">
                    <option value="0">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    foreach (Dept::getDepartments() as $id=>$name) {
                        $selected=($info['dept_id'] && $id==$info['dept_id'])?'selected="selected"':'';
                        echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                    } ?>
                    <option value="0" data-quick-add>&mdash; <?php echo __('Add New');?> &mdash;</option>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['dept_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#department"></i>
            </td>
        </tr>
        <tr class="border">
            <td>
                <?php echo __('Ticket Number Format'); ?>:
            </td>
            <td>
                <label>
                <input type="radio" name="custom-numbers" value="0" <?php echo !$info['custom-numbers']?'checked="checked"':''; ?>
                    onchange="javascript:$('#custom-numbers').hide();"> <?php echo __('System Default'); ?>
                </label>&nbsp;<label>
                <input type="radio" name="custom-numbers" value="1" <?php echo $info['custom-numbers']?'checked="checked"':''; ?>
                    onchange="javascript:$('#custom-numbers').show(200);"> <?php echo __('Custom'); ?>
                </label>&nbsp; <i class="help-tip icon-question-sign" href="#custom_numbers"></i>
            </td>
        </tr>
    </tbody>
    <tbody id="custom-numbers" style="<?php if (!$info['custom-numbers']) echo 'display:none'; ?>">
        <tr>
            <td style="padding-left:20px">
                <?php echo __('Format'); ?>:
            </td>
            <td>
                <input type="text" name="number_format" value="<?php echo $info['number_format']; ?>"/>
                <span class="faded"><?php echo __('e.g.'); ?> <span id="format-example"><?php
                    if ($info['custom-numbers']) {
                        if ($info['sequence_id'])
                            $seq = Sequence::lookup($info['sequence_id']);
                        if (!isset($seq))
                            $seq = new RandomSequence();
                        echo $seq->current($info['number_format']);
                    } ?></span></span>
                <div class="error"><?php echo $errors['number_format']; ?></div>
            </td>
        </tr>
        <tr>
<?php $selected = 'selected="selected"'; ?>
            <td style="padding-left:20px">
                <?php echo __('Sequence'); ?>:
            </td>
            <td>
                <select name="sequence_id">
                <option value="0" <?php if ($info['sequence_id'] == 0) echo $selected;
                    ?>>&mdash; <?php echo __('Random'); ?> &mdash;</option>
<?php foreach (Sequence::objects() as $s) { ?>
                <option value="<?php echo $s->id; ?>" <?php
                    if ($info['sequence_id'] == $s->id) echo $selected;
                    ?>><?php echo $s->name; ?></option>
<?php } ?>
                </select>
                <button class="action-button pull-right" onclick="javascript:
                $.dialog('ajax.php/sequence/manage', 205);
                return false;
                "><i class="icon-gear"></i> <?php echo __('Manage'); ?></button>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td width="180">
                <?php echo __('Status'); ?>:
            </td>
            <td>
                <span>
                <select name="status_id">
                    <option value="">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    foreach (TicketStatusList::getStatuses(array('states'=>array('open'))) as $status) {
                        $name = $status->getName();
                        if (!($isenabled = $status->isEnabled()))
                            $name.=' '.__('(disabled)');

                        echo sprintf('<option value="%d" %s %s>%s</option>',
                                $status->getId(),
                                ($info['status_id'] == $status->getId())
                                 ? 'selected="selected"' : '',
                                 $isenabled ? '' : 'disabled="disabled"',
                                 $name
                                );
                    }
                    ?>
                </select>
                &nbsp;
                <span class="error"><?php echo $errors['status_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#status"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Priority'); ?>:
            </td>
            <td>
                <select name="priority_id">
                    <option value="">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    if (($priorities=Priority::getPriorities())) {
                        foreach ($priorities as $id => $name) {
                            $selected=($info['priority_id'] && $id==$info['priority_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>', $id, $selected, $name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['priority_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#priority"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('SLA Plan');?>:
            </td>
            <td>
                <select name="sla_id">
                    <option value="0">&mdash; <?php echo __("Department's Default");?> &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['sla_id']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['sla_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#sla_plan"></i>
            </td>
        </tr>
        <tr>
            <td width="180"><?php echo __('Thank-You Page'); ?>:</td>
            <td>
                <select name="page_id">
                    <option value="">&mdash; <?php echo __('System Default'); ?> &mdash;</option>
                    <?php
                    if(($pages = Page::getActiveThankYouPages())) {
                        foreach($pages as $page) {
                            if(strcasecmp($page->getType(), 'thank-you')) continue;
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $page->getId(),
                                    ($info['page_id']==$page->getId())?'selected="selected"':'',
                                    $page->getName());
                        }
                    }
                    ?>
                </select>&nbsp;<font class="error"><?php echo $errors['page_id']; ?></font>
                <i class="help-tip icon-question-sign" href="#thank_you_page"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Auto-assign To');?>:
            </td>
            <td>
                <select name="assign" data-quick-add>
                    <option value="0">&mdash; <?php echo __('Unassigned'); ?> &mdash;</option>
                    <?php
                    if (($users=Staff::getStaffMembers())) {
                        echo sprintf('<OPTGROUP label="%s">',
                                sprintf(__('Agents (%d)'), count($users)));
                        foreach ($users as $id => $name) {
                            $k="s$id";
                            $selected = ($info['assign']==$k || $info['staff_id']==$id)?'selected="selected"':'';
                            ?>
                            <option value="<?php echo $k; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>

                        <?php
                        }
                        echo '</OPTGROUP>';
                    }
                    if ($teams = Team::getTeams()) { ?>
                      <optgroup data-quick-add="team" label="<?php
                        echo sprintf(__('Teams (%d)'), count($teams)); ?>"><?php
                        foreach ($teams as $id => $name) {
                            $k="t$id";
                            $selected = ($info['assign']==$k || $info['team_id']==$id) ? 'selected="selected"' : '';
                            ?>
                            <option value="<?php echo $k; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        } ?>
                        <option value="0" data-quick-add data-id-prefix="t">— <?php echo __('Add New Team'); ?> —</option>
                      </optgroup>
                    <?php
                    } ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['assign']; ?></span>
                <i class="help-tip icon-question-sign" href="#auto_assign_to"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Auto-Response'); ?>:
            </td>
            <td>
                <input type="checkbox" name="noautoresp" value="1" <?php echo $info['noautoresp']?'checked="checked"':''; ?> >
                    <?php echo __('<strong>Disable</strong> new ticket auto-response'); ?>
                    <i class="help-tip icon-question-sign" href="#ticket_auto_response"></i>
            </td>
        </tr>
    </tbody>
 </table>
</div>

<div class="hidden tab_content" id="forms">
 <table id="topic-forms" class="table" border="0" cellspacing="0" cellpadding="2">

<?php
$current_forms = array();
foreach ($forms as $F) {
    $current_forms[] = $F->id; ?>
    <tbody data-form-id="<?php echo $F->get('id'); ?>">
        <tr>
            <td class="handle" colspan="6">
                <input type="hidden" name="forms[]" value="<?php echo $F->get('id'); ?>" />
                <div class="pull-right">
                <i class="icon-large icon-move icon-muted"></i>
<?php if ($F->get('type') != 'T') { ?>
                <a href="#" title="<?php echo __('Delete'); ?>" onclick="javascript:
                if (confirm(__('You sure?')))
                    var tbody = $(this).closest('tbody');
                    tbody.fadeOut(function(){this.remove()});
                    $(this).closest('form')
                        .find('[name=form_id] [value=' + tbody.data('formId') + ']')
                        .prop('disabled', false);
                return false;"><i class="icon-large icon-trash"></i></a>
<?php } ?>
                </div>
                <div><strong><?php echo Format::htmlchars($F->getLocal('title')); ?></strong></div>
                <div><?php echo Format::display($F->getLocal('instructions')); ?></div>
            </td>
        </tr>
        <tr style="text-align:left">
            <th><?php echo __('Enable'); ?></th>
            <th><?php echo __('Label'); ?></th>
            <th><?php echo __('Type'); ?></th>
            <th><?php echo __('Visibility'); ?></th>
            <th><?php echo __('Variable'); ?></th>
        </tr>
    <?php
        foreach ($F->getDynamicFields() as $f) { ?>
        <tr>
            <td><input type="checkbox" name="fields[]" value="<?php
                echo $f->get('id'); ?>" <?php
                if ($f->isEnabled()) echo 'checked="checked"'; ?>/></td>
            <td><?php echo $f->get('label'); ?></td>
            <td><?php $t=FormField::getFieldType($f->get('type')); echo __($t[0]); ?></td>
            <td><?php echo $f->getVisibilityDescription(); ?></td>
            <td><?php echo $f->get('name'); ?></td>
        </tr>
        <?php } ?>
    </tbody>
    <?php } ?>
 </table>

   <br/>
   <strong><?php echo __('Add Custom Form'); ?></strong>:
   <select name="form_id" id="newform">
    <option value=""><?php echo '— '.__('Add a custom form') . ' —'; ?></option>
    <?php foreach (DynamicForm::objects()
        ->filter(array('type'=>'G'))
        ->exclude(array('flags__hasbit' => DynamicForm::FLAG_DELETED))
    as $F) { ?>
        <option value="<?php echo $F->get('id'); ?>"
           <?php if (in_array($F->id, $current_forms))
               echo 'disabled="disabled"'; ?>
           <?php if ($F->get('id') == $info['form_id'])
                echo 'selected="selected"'; ?>>
           <?php echo $F->getLocal('title'); ?>
        </option>
    <?php } ?>
   </select>
   &nbsp;<span class="error">&nbsp;<?php echo $errors['form_id']; ?></span>
   <i class="help-tip icon-question-sign" href="#custom_form"></i>
</div>

</div>

<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="helptopics.php"'>
</p>
</form>
<script type="text/javascript">
$(function() {
    var request = null,
      update_example = function() {
      request && request.abort();
      request = $.get('ajax.php/sequence/'
        + $('[name=sequence_id] :selected').val(),
        {'format': $('[name=number_format]').val()},
        function(data) { $('#format-example').text(data); }
      );
    };
    $('[name=sequence_id]').on('change', update_example);
    $('[name=number_format]').on('keyup', update_example);

    $('form select#newform').change(function() {
        var $this = $(this),
            val = $this.val();
        if (!val) return;
        $.ajax({
            url: 'ajax.php/form/' + val + '/fields/view',
            dataType: 'json',
            success: function(json) {
                if (json.success) {
                    $(json.html).appendTo('#topic-forms').effect('highlight');
                    $this.find(':selected').prop('disabled', true);
                }
            }
        });
    });
    $('table#topic-forms').sortable({
      items: 'tbody',
      handle: 'td.handle',
      tolerance: 'pointer',
      forcePlaceholderSize: true,
      helper: function(e, ui) {
        ui.children().each(function() {
          $(this).children().each(function() {
            $(this).width($(this).width());
          });
        });
        ui=ui.clone().css({'background-color':'white', 'opacity':0.8});
        return ui;
      }
    }).disableSelection();
});
</script>
