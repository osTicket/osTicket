<?php
include 'quick-add.tmpl.php';
$clone = $form->getField('clone')->getWidget()->name;
$permissions = $form->getField('perms')->getWidget()->name;
$name = $form->getField('name')->getWidget()->name;
?>
<script type="text/javascript">
  $('#_<?php echo $clone; ?>').change(function() {
    var $this = $(this),
        id = $this.val(),
        form = $this.closest('form'),
        name = $('[name="<?php echo $name; ?>"]:first', form);
    $.ajax({
      url: 'ajax.php/admin/role/'+id+'/perms',
      dataType: 'json',
      success: function(json) {
        $('[name="<?php echo $permissions; ?>[]"]', form).prop('checked', false);
        $.each(json, function(k, v) {
          form.find('[value="'+k+'"]', form).prop('checked', !!v);
        });
        if (!name.val())
          name.val(__('Copy of {0}').replace('{0}', $this.find(':selected').text()));
      }
    });
  });
</script>
