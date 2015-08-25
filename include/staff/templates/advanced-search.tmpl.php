<div id="advanced-search" class="advanced-search">
<h3 class="drag-handle"><?php echo __('Advanced Ticket Search');?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<form action="#tickets/search" method="post" name="search">
<div class="row">
<div class="span6">
    <input type="hidden" name="a" value="search">
    <?php include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php'; ?>
</div>
<div class="span6" style="border-left:1px solid #888;position:relative;padding-bottom:26px;">
<div style="margin-bottom: 0.5em;"><b style="font-size: 110%;"><?php echo __('Saved Searches'); ?></b></div>
<hr>
<div id="saved-searches" class="accordian" style="max-height:200px;overflow-y:auto;">
<?php foreach (SavedSearch::forStaff($thisstaff) as $S) { ?>
    <dt class="saved-search">
        <a href="#" class="load-search"><?php echo $S->title; ?>
        <i class="icon-chevron-down pull-right"></i>
        </a>
    </dt>
    <dd>
        <span>
            <button type="button" onclick="javascript:$(this).closest('form').attr({
'method': 'get', 'action': '#tickets/search/<?php echo $S->id; ?>'}).trigger('submit');"><i class="icon-chevron-left"></i> <?php echo __('Load'); ?></button>
            <button type="button" onclick="javascript:
var that = this;
$.ajax({
    url: 'ajax.php/tickets/search/<?php echo $S->id; ?>',
    type: 'POST',
    data: {'form': $(this).closest('.dialog').find('form[name=search]').serializeArray()},
    dataType: 'json',
    success: function(json) {
      if (!json.id)
        return;
      $(that).closest('dd').effect('highlight');
    }
});
return false;
"><i class="icon-save"></i> <?php echo __('Update'); ?></button>
        </span>
        <span class="pull-right">
            <button type="button" title="<?php echo __('Delete'); ?>" onclick="javascript:
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
<div style="position:absolute;bottom:0">
<hr>
    <form method="post">
    <div class="attached input">
    <input name="title" type="text" size="27" placeholder="<?php
        echo __('Enter a title for the search'); ?>"/>
        <a class="attached button" href="#tickets/search/create" onclick="javascript:
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
"><i class="icon-save"></i></a>
    </div>
</div>
</div>
</div>

<hr/>
<div>
    <div id="search-hint" class="pull-left">
    </div>
    <div class="buttons pull-right">
        <button class="button" type="submit" id="do_search"><i class="icon-search"></i>
            <?php echo __('Search'); ?></button>
    </div>
</div>

</form>

<style type="text/css">
#advanced-search .span6 .select2 {
  max-width: 300px !important;
}
</style>

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
