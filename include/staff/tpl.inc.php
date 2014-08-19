<?php
$info=Format::htmlchars(($errors && $_POST)?$_POST:$_REQUEST);

if (is_a($template, EmailTemplateGroup)) {
    // New template implementation
    $id = 0;
    $tpl_id = $template->getId();
    $name = $template->getName();
    $group = $template;
    $selected = $_REQUEST['code_name'];
    $action = 'implement';
    $extras = array('code_name'=>$selected, 'tpl_id'=>$tpl_id);
    $msgtemplates=$template::$all_names;
    $desc = $msgtemplates[$selected];
    // Attempt to lookup the default data if it is defined
    $default = @$template->getMsgTemplate($selected);
    if ($default) {
        $info['subject'] = $default->getSubject();
        $info['body'] = Format::viewableImages($default->getBody());
    }
} else {
    // Template edit
    $id = $template->getId();
    $tpl_id = $template->getTplId();
    $name = $template->getGroup()->getName();
    $desc = $template->getDescription();
    $group = $template->getGroup();
    $selected = $template->getCodeName();
    $action = 'updatetpl';
    $extras = array();
    $msgtemplates=$group::$all_names;
    $info=array_merge(array('subject'=>$template->getSubject(), 'body'=>$template->getBodyWithImages()),$info);
}
$tpl=$msgtemplates[$selected];

?>
<h2>Email Template Message - <span><?php echo $name; ?></span></h2>
<div style="padding-top:10px;padding-bottom:5px;">
    <form method="get" action="templates.php?">
    <input type="hidden" name="a" value="manage">
    <input type="hidden" name="tpl_id" value="<?php echo $tpl_id; ?>">
    Message Template:
    <select id="tpl_options" name="id" style="width:300px;">
        <option value="">&mdash; Select Setting Group &mdash;</option>
        <?php
        $impl = $group->getTemplates();
        $current_group = false;
        $_tpls = $group::$all_names;
        $_groups = $group::$all_groups;
        uasort($_tpls, function($a,$b) {
            return strcmp($a['group'].$a['name'], $b['group'].$b['name']);
        });
        foreach($_tpls as $cn=>$nfo) {
            if (!$nfo['name'])
                continue;
            if (!$current_group || $current_group != $nfo['group']) {
                if ($current_group)
                    echo "</optgroup>";
                $current_group = $nfo['group']; ?>
                <optgroup label="<?php echo isset($_groups[$current_group])
                    ? $_groups[$current_group] : $current_group; ?>">
            <?php }
            $sel=($selected==$cn)?'selected="selected"':'';
            echo sprintf('<option value="%s" %s>%s</option>',
                isset($impl[$cn]) ? $impl[$cn]->getId() : $cn,
                $sel,$nfo['name']);
        }
        if ($current_group)
            echo "</optgroup>";
        ?>
    </select>
    <input type="submit" value="Go">
    &nbsp;&nbsp;&nbsp;<font color="red"><?php echo $errors['tpl']; ?></font>
    </form>
</div>
<form action="templates.php?id=<?php echo $id; ?>&amp;a=manage" method="post" id="save">
<?php csrf_token(); ?>
<?php foreach ($extras as $k=>$v) { ?>
    <input type="hidden" name="<?php echo $k; ?>" value="<?php echo Format::htmlchars($v); ?>" />
<?php } ?>
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="hidden" name="a" value="manage">
<input type="hidden" name="do" value="<?php echo $action; ?>">

<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
   <thead>
     <tr>
        <th colspan="2">
            <h4><?php echo Format::htmlchars($desc['desc']); ?></h4>
            <em>Subject and body required.  <a class="tip" href="ticket_variables.txt">Supported Variables</a>.</em>
        </th>
     </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan=2>
                <strong>Message Subject:</strong> <em>Email message subject</em> <font class="error">*&nbsp;<?php echo $errors['subject']; ?></font><br>
                <input type="text" name="subject" size="60" value="<?php echo $info['subject']; ?>" >
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div style="margin-bottom:0.5em;margin-top:0.5em">
                <strong>Message Body:</strong> <em>Email message body.</em> <font class="error">*&nbsp;<?php echo $errors['body']; ?></font>
                </div>
                <input type="hidden" name="draft_id" value=""/>
                <textarea name="body" cols="21" rows="16" style="width:98%;" wrap="soft"
                    class="richtext draft" data-draft-namespace="tpl.<?php echo Format::htmlchars($selected); ?>"
                    data-draft-object-id="<?php echo $tpl_id; ?>"><?php echo $info['body']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:210px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes" onclick="javascript:
        setTimeout('location.reload()', 25);" />
    <input class="button" type="button" name="cancel" value="Cancel Changes"
        onclick='window.location.href="templates.php?tpl_id=<?php echo $tpl_id; ?>"'>
</p>
</form>
