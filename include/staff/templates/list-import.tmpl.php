<h3 class="drag-handle"><?php echo $info['title']; ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<ul class="tabs" id="user-import-tabs">
    <li class="active"><a href="#copy-paste"
        ><i class="icon-edit"></i>&nbsp;<?php echo __('Copy Paste'); ?></a></li>
    <li><a href="#upload"
        ><i class="icon-fixed-width icon-cloud-upload"></i>&nbsp;<?php echo __('Upload'); ?></a></li>
</ul>

<form action="<?php echo $info['action']; ?>" method="post" enctype="multipart/form-data"
    onsubmit="javascript:
    if ($(this).find('[name=import]').val()) {
        $(this).attr('action', '<?php echo $info['upload_url']; ?>');
        $(document).unbind('submit.dialog');
    }">
<?php echo csrf_token();
if ($org_id) { ?>
    <input type="hidden" name="id" value="<?php echo $org_id; ?>"/>
<?php } ?>
<div id="user-import-tabs_container">
<div class="tab_content" id="copy-paste" style="margin:5px;">
<h2 style="margin-bottom:10px"><?php echo __('Value and Abbreviation'); ?></h2>
<p><?php echo __(
'Enter one name and abbreviation per line.'); ?><br/><em><?php echo __(
'To import items with properties, use the Upload tab.'); ?></em>
</p>
<textarea name="pasted" style="display:block;width:100%;height:8em;padding:5px"
    placeholder="<?php echo __('e.g. My Location, MY'); ?>">
<?php echo $info['pasted']; ?>
</textarea>
</div>

<div class="hidden tab_content" id="upload" style="margin:5px;">
<h2 style="margin-bottom:10px"><?php echo __('Import a CSV File'); ?></h2>
<p>
<em><?php echo __(
'Use the columns shown in the table below. To add more properties, use the Properties tab.  Only properties with `variable` defined can be imported.'); ?>
</p>
<table class="list"><tr>
<?php
    $fields = array('Value', 'Abbreviation');
    $data = array(
        array('Value' => __('My Location'), 'Abbreviation' => 'MY')
    );
    foreach ($list->getConfigurationForm()->getFields() as $f)
        if ($f->get('name'))
            $fields[] = $f->get('label');
    foreach ($fields as $f) { ?>
        <th><?php echo mb_convert_case($f, MB_CASE_TITLE); ?></th>
<?php } ?>
</tr>
<?php
    foreach ($data as $d) {
        foreach ($fields as $f) {
            ?><td><?php
            if (isset($d[$f])) echo $d[$f];
            ?></td><?php
        }
    } ?>
</tr></table>
<br/>
<input type="file" name="import"/>
</div>

    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"  value="<?php
            echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo __('Import Items'); ?>">
        </span>
     </p>
</form>
