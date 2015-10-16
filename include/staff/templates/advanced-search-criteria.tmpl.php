<?php
foreach ($form->errors(true) ?: array() as $message) {
    ?><div class="error-banner"><?php echo $message;?></div><?php
}

$info = $search->getSearchFields($form);
foreach (array_keys($info) as $F) {
    ?><input type="hidden" name="fields[]" value="<?php echo $F; ?>"/><?php
}
$errors = !!$form->errors();
$inbody = false;
$first_field = true;
foreach ($form->getFields() as $name=>$field) {
    @list($name, $sub) = explode('+', $field->get('name'), 2);
    if ($sub === 'search') {
        if (!$first_field) {
            echo '</div></div>';
        }
        echo '<div class="adv-search-field-container">';
        $inbody = false;
        $first_field = false;
    }
    elseif (!$first_field && !$inbody) {
        echo sprintf('<div class="adv-search-field-body %s">',
            !$errors && isset($info[$name]) && $info[$name]['active'] ? 'hidden' : '');
        $inbody = true;
    }
?>
    <fieldset id="field<?php echo $field->getWidget()->id; ?>" <?php
        $class = array();
        if (!$field->isVisible())
            $class[] = "hidden";
        if ($sub === 'method')
            $class[] = "adv-search-method";
        elseif ($sub === 'search')
            $class[] = "adv-search-field";
        elseif ($field->get('__searchval__'))
            $class[] = "adv-search-val";
        if ($class)
            echo 'class="'.implode(' ', $class).'"';
        ?>>
        <?php echo $field->render(); ?>
        <?php if (!$errors && $sub === 'search' && isset($info[$name]) && $info[$name]['active']) { ?>
            <span style="padding-left: 5px">
            <a href="#"  data-name="<?php echo Format::htmlchars($name); ?>" onclick="javascript:
    var $this = $(this),
        name = $this.data('name'),
        expanded = $this.data('expanded') || false;
    $this.closest('.adv-search-field-container').find('.adv-search-field-body').slideDown('fast');
    $this.find('span.faded').hide();
    $this.find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
    return false;
"><i class="icon-caret-right"></i>
            <span class="faded"><?php echo $search->describeField($info[$name]); ?></span>
            </a>
            </span>
        <?php } ?>
        <?php foreach ($field->errors() as $E) {
            ?><div class="error"><?php echo $E; ?></div><?php
        } ?>
    </fieldset>
    <?php if ($name[0] == ':' && substr($name, -7) == '+search') {
        list($N,) = explode('+', $name, 2);
?>
    <input type="hidden" name="fields[]" value="<?php echo $N; ?>"/>
    <?php }
}
if (!$first_field)
    echo '</div></div>';
?>
<div id="extra-fields"></div>
<hr/>
<i class="icon-plus-sign"></i>
<select id="search-add-new-field" name="new-field" style="max-width: 300px;">
    <option value="">— <?php echo __('Add Other Field'); ?> —</option>
<?php
if (is_array($matches)) {
foreach ($matches as $path => $F) {
    list($label, $field) = $F; ?>
    <option value="<?php echo $path; ?>" <?php
        if (isset($state[$path])) echo 'disabled="disabled"';
        ?>><?php echo $label; ?></option>
<?php }
} ?>
</select>
<script>
$(function() {
  $('#search-add-new-field').on('change', function() {
    var that=this;
    $.ajax({
      url: 'ajax.php/tickets/search/field/'+$(this).val(),
      type: 'get',
      dataType: 'json',
      success: function(json) {
        if (!json.success)
          return false;
        $(that).find(':selected').prop('disabled', true);
        $('#extra-fields').append($(json.html));
      }
    });
  });
});
</script>
