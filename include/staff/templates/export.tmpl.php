<?php

if (!$info[':title'])
    $info[':title'] = __('Export');
?>
<h3 class="drag-handle"><?php echo $info[':title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($errors['err']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
}

$action = $info[':action'] ?: ('#');
?>
<div style="display:block; margin:5px;">
<form method="get" name="export" id="exportchecker"
    action="<?php echo $action; ?>">
    <div>
    <h3  style="color:#000;">
    <i class="icon-spinner icon-spin icon-2x"></i>&nbsp;&nbsp;
    <?php echo __('Please wait while we generate the export'); ?></h3>
    </div>
    <br>
    <div style="margin-top:10px;">
    <?php
    echo sprintf(
            __("We know you're busy, you can close this popup and the export will be sent to %s"),
            $thisstaff->getEmail());
    ?>
    </div>
    <hr>
    <p class="full-width">
        <span class="buttons pull-right">
            <input type="button" name="cancel" class="close"
            value="<?php echo __('Yes, Email Me'); ?>">
        </span>
     </p>
</form>
</div>
<div class="clear"></div>
<script>
+function() {
    var $popup = $('.dialog#popup');
    var interval = setInterval(function() {
        $.ajax({
            type: 'POST',
            url: '<?php echo sprintf('ajax.php/export/%s/check',
                    $exporter->getId()); ?>',
            dataType: 'json',
            cache: false,
            success: function (resp, status, xhr)  {
                if (xhr.status == 201) {
                    clearInterval(interval);
                    $('a.close', $popup).trigger('click');
                    var aElement = document.createElement('a');
                    aElement.href = resp.href;
                    aElement.target = '_blank';
                    aElement.download = resp.filename;
                    aElement.click();
                    aElement.remove();
                }
            },
            error: function (xhr) {
                clearInterval(interval);
            }
        });
    }, <?php echo ($exporter->getInterval()*1000); ?>);

    $('input.close, a.close', $popup).on('click', function () {
        clearInterval(interval);
     });
}();
</script>
