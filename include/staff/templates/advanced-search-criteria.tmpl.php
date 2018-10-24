<?php
// Display errors if any
foreach ($form->errors(true) ?: array() as $message)
    echo sprintf('<div class="error-banner">%s</div>',
            Format::htmlchars($message));
// Current search fields.
$info = $search->getSearchFields($form) ?: array();
if (($search instanceof SavedQueue) && !$search->checkOwnership($thisstaff)) {
    $matches = $search->getSupplementalMatches();
    // Uneditable core criteria for the queue
    echo '<div class="faded">'.  nl2br(Format::htmlchars($search->describeCriteria())).
                    '</div><br>';
    // Show any supplemental filters
    if ($matches) {
        ?>
        <div id="ticket-flags"
            style="padding:5px; border-top: 1px dotted #777;">
            <strong><i class="icon-caret-down"></i>&nbsp;<?php
                echo __('Supplemental Filters'); ?></strong>
        </div>
<?php
    }
} else {
    $matches = $search->getSupportedMatches();
}

foreach (array_keys($info) as $F) {
    ?><input type="hidden" name="fields[]" value="<?php echo $F; ?>"/><?php
}
$has_errors = !!$form->errors();
$inbody = false;
$already_listed = [];
$first_field = true;
foreach ($form->getFields() as $name=>$field) {
    @list($name, $sub) = explode('+', $field->get('name'), 2);
    $already_listed[$name] = 1;
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
            !$has_errors && isset($info[$name]) && $info[$name]['active'] ? 'hidden' : '');
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
        <?php if (!$has_errors && $sub === 'search' && isset($info[$name]) && $info[$name]['active']) { ?>
            <span style="padding-left: 5px">
            <a href="#"  data-name="<?php echo Format::htmlchars($name); ?>" onclick="javascript:
    var $this = $(this),
        name = $this.data('name'),
        expanded = $this.data('expanded') || false;
    $this.closest('.adv-search-field-container').find('.adv-search-field-body').slideDown('fast');
    $this.find('span.faded').hide();
    $this.find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
    return false; "><i class="icon-caret-right"></i>
            <span class="faded"><?php echo $search->describeField($info[$name]); ?></span>
            </a>
            </span>
        <?php } ?>
        <?php foreach ($field->errors() as $E) {
            ?><div class="error"><?php echo $E; ?></div><?php
        } ?>
    </fieldset>
    <?php if ($name[0] == ':' && substr($name, -7) == '+search') {
        list($N,) = explode('+', $name, 2); ?>
    <input type="hidden" name="fields[]" value="<?php echo $N; ?>"/>
    <?php }
}
if (!$first_field)
    echo '</div></div>';

if ($matches && is_array($matches)) { ?>
<div id="extra-fields"></div>
<hr/>
<i class="icon-plus-sign"></i>
<select id="search-add-new-field" name="new-field" style="max-width: 300px;">
    <option value="">— <?php echo __('Add Other Field'); ?> —</option>
<?php
foreach ($matches as $path => $F) {
    # Skip fields already listed above the drop-down
    if (isset($already_listed[$path]))
        continue;
    list($label, $field) = $F; ?>
    <option value="<?php echo $path; ?>" <?php
        if (isset($state[$path])) echo 'disabled="disabled"';
        ?>><?php echo $label; ?></option>
<?php }
?>
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
        $(that).find('option:eq("")').prop('selected', true);
        $('#extra-fields').append($(json.html));
      }
    });
  });
});
</script>
<?php
} ?>
