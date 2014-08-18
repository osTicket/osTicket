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
<form method="get" action="templates.php?">
<h2><span>Email Template Set &nbsp;/&nbsp; <span><a href="templates.php?tpl_id=<?php echo $tpl_id; ?>"><?php echo $name; ?></a>
    <input type="hidden" name="a" value="manage">
    <input type="hidden" name="tpl_id" value="<?php echo $tpl_id; ?>">
<div class="pull-right">
    <span style="font-size:10pt">Viewing:</span>
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
    </div>
</h2>
</form>
<hr/>
<form action="templates.php?id=<?php echo $id; ?>&amp;a=manage" method="post" id="save">
<?php csrf_token(); ?>
<?php foreach ($extras as $k=>$v) { ?>
    <input type="hidden" name="<?php echo $k; ?>" value="<?php echo Format::htmlchars($v); ?>" />
<?php } ?>
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="hidden" name="a" value="manage">
<input type="hidden" name="do" value="<?php echo $action; ?>">

<div style="border:1px solid #ccc;background:#f0f0f0;padding:5px 10px;
    margin:10px 0;">
<h3 style="font-size:12pt;margin:0"><?php echo $desc['name']; ?>
    &nbsp;<i class="help-tip icon-question-sign"
        data-content="<?php echo Format::htmlchars($desc['desc']); ?>"
        data-title="<?php echo Format::htmlchars($desc['name']); ?>"></i>
    <a style="font-size:10pt" class="tip pull-right" href="#ticket_variables.txt">
    <i class="icon-tags"></i>
    Supported Variables</a>
    </h3>
<?php if ($errors) { ?>
    <font class="error"><?php echo $errors['subject']; ?>&nbsp;<?php echo $errors['body']; ?></font>
<?php } ?>
</div>

<div style="padding-bottom:3px;" class="faded"><strong>Email Subject and Body:</strong></div>
<div id="toolbar"></div>
<div id="save" style="padding-top:5px;">
    <input type="text" name="subject" size="65" value="<?php echo $info['subject']; ?>"
    style="font-size:14pt;width:100%;box-sizing:border-box">
    <div style="margin-bottom:0.5em;margin-top:0.5em">
    </div>
    <input type="hidden" name="draft_id" value=""/>
    <textarea name="body" cols="21" rows="16" style="width:98%;" wrap="soft"
        data-toolbar-external="#toolbar"
        class="richtext draft" data-draft-namespace="tpl.<?php echo $selected; ?>"
        data-draft-object-id="<?php echo $tpl_id; ?>"><?php echo $info['body']; ?></textarea>
</div>

<p style="text-align:center">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes" onclick="javascript:
        setTimeout('location.reload()', 25);" />
    <input class="button" type="button" name="cancel" value="Cancel Changes"
        onclick='window.location.href="templates.php?tpl_id=<?php echo $tpl_id; ?>"'>
</p>
</form>
