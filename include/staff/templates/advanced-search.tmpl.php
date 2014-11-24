<?php
?>
<div id="advanced-search">
<h3><?php echo __('Advanced Ticket Search');?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form action="#tickets/search" method="post" name="search">
<div class="row">
<div class="span6">
    <input type="hidden" name="a" value="search">
<?php
foreach ($form->errors(true) ?: array() as $message) {
    ?><div class="error-banner"><?php echo $message;?></div><?php
}

foreach ($form->getFields() as $name=>$field) { ?>
    <fieldset id="field<?php echo $field->getWidget()->id;
        ?>" <?php if (!$field->isVisible()) echo 'style="display:none;"'; ?>>
        <?php echo $field->render(); ?>
        <?php foreach ($field->errors() as $E) {
            ?><div class="error"><?php echo $E; ?></div><?php
        } ?>
    </fieldset>
<?php }
?>
<hr/>
<select name="new-field" style="max-width: 100%;">
    <option value="">— <?php echo __('Add Other Field'); ?> —</option>
<?php
foreach ($matches as $name => $fields) { ?>
    <optgroup label="<?php echo $name; ?>">
<?php
    foreach ($fields as $id => $desc) { ?>
        <option value="<?php echo $id; ?>"><?php echo $desc; ?></option>
<?php } ?>
    </optgroup>
<?php } ?>
</select>

</div>
<div class="span6" style="border-left: 1px solid #888;">
<div style="margin-bottom: 0.5em;"><b style="font-size: 110%;"><?php echo __('Saved Searches'); ?></b></div>
<div id="saved-searches" class="accordian">
<?php foreach (SavedSearch::forStaff($thisstaff) as $S) { ?>
    <dt class="saved-search">
        <a href="#" class="load-search"><?php echo $S->title; ?>
        <i class="icon-chevron-down pull-right"></i>
        </a>
    </dt>
    <dd>
        <span>
            <button onclick="javascript:$(this).closest('form').attr({
'method': 'get', 'action': '#tickets/search/<?php echo $S->id; ?>'});"><i class="icon-chevron-left"></i> Load</button>
            <?php if ($thisstaff->isAdmin()) { ?>
                <button><i class="icon-bullhorn"></i> <?php echo __('Publish'); ?></button>
            <?php } ?>
            <button onclick="javascript:
$.ajax({
    url: 'ajax.php/tickets/search/<?php echo $S->id; ?>',
    type: 'POST',
    data: {'form': $(this).closest('.dialog').find('form[name=search]').serializeArray()},
    dataType: 'json',
    success: function(json) {
      if (!json.id)
        return;
      $('<dt>').effect('highlight');
    }
});
return false;
"><i class="icon-save"></i> <?php echo __('Update'); ?></button>
        </span>
        <span class="pull-right">
            <button title="<?php echo __('Delete'); ?>" onclick="javascript:
    if (!confirm(__('You sure?'))) return false;
    var that = this;
    $.ajax({
        'url': 'ajax.php/tickets/search/<?php echo $S->id; ?>',
        'type': 'delete',
        'dataType': 'json',
        'success': function(json) {
            if (json.success) {
                $(that).closest('dd').prev('dt').slideUp().next('dd').slideUp();
            }
        }
    });
    return false;
"><i class="icon-trash"></i></button>
        </span>
    </dd>
<?php } ?>
</div>
<div>
    <form method="post">
    <fieldset>
    <input name="title" type="text" size="30" placeholder="Enter a title for the search"/>
    <span class="action-buttons">
        <span class="action-button">
            <a href="#tickets/search/create" onclick="javascript:
$.ajax({
    url: 'ajax.php/' + $(this).attr('href').substr(1),
    type: 'POST',
    data: {'name': $(this).closest('form').find('[name=title]').val(),
           'form': $(this).closest('.dialog').find('form[name=search]').serializeArray()},
    dataType: 'json',
    success: function(json) {
      if (!json.id)
        return;
      $('<dt>')
        .append($('<a>').text(' ' + json.title)
          .prepend($('<i>').addClass('icon-chevron-left'))
        ).appendTo($('#saved-searches'));
    }
});
return false;
"><i class="icon-save"></i> <?php echo __('Save'); ?></a>
        </span>
        <span class="action-button pull-right" data-dropdown="#save-dropdown-more">
            <i class="icon-caret-down pull-right"></i>
        </span>
    </span>
    </fieldset>
    <div id="save-dropdown-more" class="action-dropdown anchor-right">
      <ul>
        <li><a href="#queue/create">
            <i class="icon-list"></i> <?php echo __('Create Queue'); ?></a>
        </li>
      </ul>
    </div>
</div>
</div>
</div>

<hr/>
<div>
    <div id="search-hint" class="pull-left">
    </div>
    <div class="buttons pull-right">
        <button class="button" id="do_search"><i class="icon-search"></i> <?php echo __('Search'); ?></button>
    </div>
</div>

</form>

<link rel="stylesheet" type="text/css" href="<?php echo ROOT_PATH; ?>css/jquery.multiselect.css"/>

<script type="text/javascript">
$(function() {
  $('#advanced-search [data-dropdown]').dropdown();

  var I = setInterval(function() {
    var A = $('#saved-searches.accordian');
    if (!A.length) return;
    clearInterval(I);

    var allPanels = $('dd', A).hide();
    $('dt > a', A).click(function() {
      $('dt', A).removeClass('active');
      allPanels.slideUp();
      $(this).parent().addClass('active').next().slideDown();
      return false;
    });
  }, 200);
});
</script>
