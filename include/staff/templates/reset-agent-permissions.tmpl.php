<?php
include 'quick-add.tmpl.php';
$clone = $form->getField('clone')->getWidget()->name;
$permissions = $form->getField('perms')->getWidget()->name;
?>
<script type="text/javascript">
  $('#_<?php echo $clone; ?>').change(function() {
    var $this = $(this),
        id = $this.val(),
        form = $this.closest('form');
    $.ajax({
      url: 'ajax.php/staff/'+id+'/perms',
      dataType: 'json',
      success: function(json) {
        $('[name="<?php echo $permissions; ?>[]"]', form).prop('checked', false);
        $.each(json, function(k, v) {
          form.find('[value="'+k+'"]', form).prop('checked', !!v);
        });
      }
    });
  });
</script>
