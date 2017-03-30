<h3 class="drag-handle"><i class="icon-wrench"></i> <?php echo __('Manage Sequences'); ?></i></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/><?php echo __(
'Sequences are used to generate sequential numbers. Various sequences can be
used to generate sequences for different purposes.'); ?>
<br/>
<br/>
<form method="post" action="<?php echo $info['action']; ?>">
<div id="sequences">
<?php
$current_list = array();
foreach ($sequences as $e) {
    $field = function($field, $name=false) use ($e) { ?>
    <input class="f<?php echo $field; ?>" type="hidden" name="seq[<?php echo $e->id;
        ?>][<?php echo $name ?: $field; ?>]" value="<?php echo $e->{$field}; ?>"/>
<?php }; ?>
    <div class="row-item">
        <?php echo $field('name'); echo $field('current', 'next'); echo $field('increment'); echo $field('padding'); ?>
        <input type="hidden" class="fdeleted" name="seq[<?php echo $e->get('id'); ?>][deleted]" value="0"/>
        <i class="icon-sort-by-order"></i>
        <div style="display:inline-block" class="name"> <?php echo $e->getName(); ?> </div>
        <div class="manage-buttons pull-right">
            <span class="faded"><?php echo __('next'); ?></span>
            <span class="current"><?php echo $e->current(); ?></span>
        </div>
        <div class="button-group">
            <div class="manage"><a href="#"><i class="icon-cog"></i></a></div>
            <div class="delete"><?php if (!$e->hasFlag(Sequence::FLAG_INTERNAL)) { ?>
                <a href="#"><i class="icon-trash"></i></a><?php } ?></div>
        </div>
        <div class="management hidden" data-id="<?php echo $e->id; ?>">
            <table width="100%"><tbody>
                <tr><td><label style="padding:0"><?php echo __('Increment'); ?>:
                    <input class="-increment" type="text" size="4" value="<?php echo Format::htmlchars($e->increment); ?>"/>
                    </label></td>
                    <td><label style="padding:0"><?php echo __('Padding Character'); ?>:
                    <input class="-padding" maxlength="1" type="text" size="4" value="<?php echo Format::htmlchars($e->padding); ?>"/>
                    </label></td></tr>
            </tbody></table>
        </div>
    </div>
<?php } ?>
</div>

<div class="row-item hidden" id="template">
    <i class="icon-sort-by-order"></i>
    <div style="display:inline-block" class="name"> <?php echo __('New Sequence'); ?> </div>
    <div class="manage-buttons pull-right">
        <span class="faded">next</span>
        <span class="next">1</span>
    </div>
    <div class="button-group">
        <div class="manage"><a href="#"><i class="icon-cog"></i></a></div>
        <div class="delete new"><a href="#"><i class="icon-trash"></i></a></div>
    </div>
    <div class="management hidden" data-id="<?php echo $e->id; ?>">
        <table width="100%"><tbody>
            <tr><td><label style="padding:0"><?php echo __('Increment'); ?>:
                <input class="-increment" type="text" size="4" value="1"/>
                </label></td>
                <td><label style="padding:0"><?php echo __('Padding Character'); ?>:
                <input class="-padding" maxlength="1" type="text" size="4" value="0"/>
                </label></td></tr>
        </tbody></table>
    </div>
</div>

<hr/>
<button onclick="javascript:
  var id = ++$.uid, base = 'seq[new-'+id+']';
  var clone = $('.row-item#template').clone()
    .appendTo($('#sequences'))
    .removeClass('hidden')
    .append($('<input>').attr({type:'hidden',class:'fname',name:base+'[name]',value:'<?php echo __('New Sequence'); ?>'}))
    .append($('<input>').attr({type:'hidden',class:'fcurrent',name:base+'[current]',value:'1'}))
    .append($('<input>').attr({type:'hidden',class:'fincrement',name:base+'[increment]',value:'1'}))
    .append($('<input>').attr({type:'hidden',class:'fpadding',name:base+'[padding]',value:'0'})) ;
  clone.find('.manage a').trigger('click');
  return false;
  "><i class="icon-plus"></i> <?php echo __('Add New Sequence'); ?></button>
<div id="delete-warning" style="display:none">
<hr>
    <div id="msg_warning"><?php echo __(
    'Clicking <strong>Save Changes</strong> will permanently remove the
    deleted sequences.'); ?>
    </div>
</div>
<hr>
<div>
    <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('Save Changes'); ?>" onclick="javascript:
$('#sequences .save a').each(function() { $(this).trigger('click'); });
">
    </span>
</div>

<script type="text/javascript">
$(function() {
  var remove = function() {
    if (!$(this).parent().hasClass('new')) {
      $('#delete-warning').show();
      $(this).closest('.row-item').hide()
        .find('input.fdeleted').val('1');
      }
    else
      $(this).closest('.row-item').remove();
    return false;
  }, manage = function() {
    var top = $(this).closest('.row-item');
    top.find('.management').show(200);
    top.find('.name').empty().append($('<input class="-name" type="text" size="40">')
      .val(top.find('input.fname').val())
    );
    top.find('.current').empty().append($('<input class="-current" type="text" size="10">')
      .val(top.find('input.fcurrent').val())
    );
    $(this).find('i').attr('class','icon-save');
    $(this).parent().attr('class','save');
    return false;
  }, save = function() {
    var top = $(this).closest('.row-item');
    top.find('.management').hide(200);
     $.each(['name', 'current'], function(i, t) {
      var val = top.find('input.-'+t).val();
      top.find('.'+t).empty().text(val);
      top.find('input.f'+t).val(val);
    });
    $.each(['increment', 'padding'], function(i, t) {
      top.find('input.f'+t).val(top.find('input.-'+t).val());
    });
    $(this).find('i').attr('class','icon-cog');
    $(this).parent().attr('class','manage');
    return false;
  };
  $(document).on('click.seq', '#sequences .manage a', manage);
  $(document).on('click.seq', '#sequences .save a', save);
  $(document).on('click.seq', '#sequences .delete a', remove);
  $('.close, input:submit').click(function() {
      $(document).off('click.seq');
  });
});
</script>
