<h3 class="drag-handle"><?php echo __('Original Thread Entry'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>

<div id="history" class="accordian">

<?php
$E = $entry;
$i = 0;
$omniscient = $thisstaff->hasPerm(ThreadEntry::PERM_EDIT);
do {
    $i++;
    if (!$omniscient
        // The current version is always visible
        && $i > 1
        // If you originally posted it, you can see all the edits
        && $E->staff_id != $thisstaff->getId()
        // You can see your own edits
        && ($E->editor != $thisstaff->getId() || $E->editor_type != 'S')
    ) {
        // Skip edits made by other agents
        continue;
    } ?>
<dt>
    <a href="#"><i class="icon-copy"></i>
    <strong><?php if ($E->title)
        echo Format::htmlchars($E->title).' — '; ?></strong>
    <em><?php if (strpos($E->updated, '0000-') === false)
        echo sprintf(__('Edited on %s by %s'), Format::datetime($E->updated),
            ($editor = $E->getEditor()) ? $editor->getName() : '');
    else
        echo __('Original'); ?></em>
    </a>
</dt>
<dd class="hidden" style="background-color:transparent">
    <div class="thread-body" style="background-color:transparent">
        <?php echo $E->getBody()->toHtml(); ?>
    </div>
</dd>
<?php
}
while (($E = $E->getParent()) && $E->type == $entry->type);
?>

</div>

<hr>
<p class="full-width">
    <span class="buttons pull-right">
        <input type="button" name="cancel" class="close"
            value="<?php echo __('Close'); ?>">
    </span>
</p>

</form>

<script type="text/javascript">
$(function() {
  var I = setInterval(function() {
    var A = $('#history.accordian');
    if (!A.length) return;
    clearInterval(I);

    var allPanels = $('dd', A).hide().removeClass('hidden');
    $('dt > a', A).click(function() {
      if (!$(this).parent().is('.active')) {
        $('dt', A).removeClass('active');
        allPanels.slideUp();
        $(this).parent().addClass('active').next().slideDown();
      }
      return false;
    });
    allPanels.last().show().prev().addClass('active');
  }, 100);
});
