<div id="the-lookup-form">
<h3><?php echo $info['title']; ?></h3>
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
<ul class="tabs">
    <li><a href="#copy-paste" class="active"
        ><i class="icon-edit"></i>&nbsp;Copy Paste</a></li>
    <li><a href="#upload"
        ><i class="icon-fixed-width icon-cloud-upload"></i>&nbsp;Upload</a></li>
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

<div class="tab_content" id="copy-paste" style="margin:5px;">
<h2 style="margin-bottom:10px">Name and Email</h2>
<p>
Enter one name and email address per line.<br/>
<em>To import more other fields, use the Upload tab.</em>
</p>
<textarea name="pasted" style="display:block;width:100%;height:8em"
    placeholder="e.g. John Doe, john.doe@osticket.com">
<?php echo $info['pasted']; ?>
</textarea>
</div>

<div class="tab_content" id="upload" style="display:none;margin:5px;">
<h2 style="margin-bottom:10px">Import a CSV File</h2>
<p>
<em>Use the columns shown in the table below. To add more fields, visit the
Admin Panel -&gt; Manage -&gt; Forms -&gt; <?php echo
UserForm::getUserForm()->get('title'); ?> page to edit the available fields.
Only fields with `variable` defined can be imported.</em>
</p>
<table class="list"><tr>
<?php
    $fields = array();
    $data = array(
        array('name' => 'John Doe', 'email' => 'john.doe@osticket.com')
    );
    foreach (UserForm::getUserForm()->getFields() as $f)
        if ($f->get('name'))
            $fields[] = $f->get('name');
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
        <span class="buttons" style="float:left">
            <input type="reset" value="Reset">
            <input type="button" name="cancel" class="close"  value="Cancel">
        </span>
        <span class="buttons" style="float:right">
            <input type="submit" value="Import Users">
        </span>
     </p>
</form>
