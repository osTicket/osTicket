<?php
#
# Context
#
# $forms - array<DynamicForm> list of currently attached forms
?>
<table id="topic-forms" class="table" border="0" cellspacing="0" cellpadding="2">

<?php
$current_forms = array();
foreach ($forms as $F) {
  $current_forms[] = $F->id;
  $form = $F;
  include 'dynamic-form-fields-view.tmpl.php';
}
?>
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

<script type="text/javascript">
$(function() {
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
      cursor: 'move',
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
