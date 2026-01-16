<div class="pull-left">
<h2><?php echo __('Update Schedule') ?>
    <small> — <?php echo $schedule->getName(); ?></small>
</h2>
</div>
<div class="pull-right flush-right">
    <span class="action-button pull-right" data-dropdown="#schedule-dropdown-more">
        <i class="icon-caret-down pull-right"></i>
        <span ><i class="icon-cog"></i></span>
    </span>
    <div id="schedule-dropdown-more" class="action-dropdown anchor-right">
        <ul id="schedule-actions">
            <li><a class="schedule-action"
            href="#schedule/<?php echo $schedule->getId(); ?>/clone">
                <i class="icon-copy icon-fixed-width"></i>
                <?php echo __('Clone'); ?></a></li>
            <?php
            if ($schedule->isBusinessHours()) { ?>
            <li><a class="schedule-action"
            href="#schedule/<?php echo $schedule->getId(); ?>/diagnostic">
                <i class="icon-eye-open icon-fixed-width"></i>
                <?php echo __('Diagnostic'); ?></a></li>
            <?php
            } ?>
        </ul>
    </div>
</div>
<div class="clear"></div>
<form action="" method="post" class="save">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="update">
    <input type="hidden" name="id" value="<?php echo $schedule->getId(); ?>">
<ul class="clean tabs" id="schedule-tabs">
    <li><a href="#schedule">
        <i class="icon-cog"></i> <?php echo __('Schedule'); ?></a></li>
    <li class="active"><a href="#entries">
        <i class="icon-calendar"></i> <?php echo sprintf('%s (%d)',
                __('Entries'),
                $schedule->getNumEntries()); ?></a></li>
    <?php
    if ($schedule->isBusinessHours()) {?>
    <li><a href="#holidays">
        <i class="icon-list"></i> <?php echo sprintf('%s (%d)',
                __('Holidays'),
                $schedule->getNumHolidaysSchedules());
                ?></a></li>
    <?php
    } ?>
</ul>
<div id="schedule-tabs_container">
<div id="schedule" class="tab_content hidden">
   <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('Schedule Details'); ?><i
                class="help-tip icon-question-sign"
                href="#schedule"></i></em>
            </th>
        </tr>
    </thead>
    <tbody>
      <tr><td colspan="2">
     <?php
        $form =$form ?: $schedule->getForm();
         include STAFFINC_DIR.'templates/dynamic-form-simple.tmpl.php';
     ?>
      </td></tr>
    </tbody>
   </table>
</div>
<div id="entries" class="tab_content">
<?php
    $pjax_container = '#entries';
    include STAFFINC_DIR . 'templates/schedule-entries.tmpl.php'; ?>
</div>
<?php
if ($schedule->isBusinessHours()) {?>
<div id="holidays" class="tab_content hidden">
<?php
    $pjax_container = '#holidays';
    include STAFFINC_DIR . 'templates/schedule-holidays.tmpl.php'; ?>
</div>
<?php
} ?>
<p class="centered">
    <input type="submit" name="submit" value="<?php echo __('Save'); ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>"
        onclick='window.location.href="?"'>
</p>
</form>

<script type="text/javascript">
$(function() {
    $('#entries, #schedule-actions').on('click', 'a.entry-action, a.schedule-action', function(e) {
        e.preventDefault();
        var $id = $(this).attr('id');
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.dialog(url, [201], function (xhr, resp) {
           $.pjax.reload('#pjax-container');
        });
        return false;
    });
    $('#entries').on('click', 'a.entries-action', function(e) {
        e.preventDefault();
        var ids = [];
        $('form.save :checkbox.schedule-entry:checked').each(function() {
            ids.push($(this).val());
        });
        if (!ids.length)
            alert('<?php echo __('Please select at least one entry.');?>');
        if (ids.length && confirm(__('Are you sure?'))) {
            $.ajax({
              url: 'ajax.php/' + $(this).attr('href').substr(1),
              type: 'POST',
              data: {count:ids.length, ids:ids},
              dataType: 'json',
              success: function(json) {
                if (json.success) {
                  if (window.location.search.indexOf('a=entries') != -1)
                    $.pjax.reload('#entries');
                  else
                    $.pjax.reload('#pjax-container');
                }
              }
            });
        }
        return false;
    });
});
</script>
