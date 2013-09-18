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
    $msgtemplates=$template->all_names;
    // Attempt to lookup the default data if it is defined
    $default = @$template->getMsgTemplate($selected);
    if ($default) {
        $info['subj'] = $default->getSubject();
        $info['body'] = $default->getBody();
    }
} else {
    // Template edit
    $id = $template->getId();
    $tpl_id = $template->getTplId();
    $name = $template->getGroup()->getName();
    $group = $template->getGroup();
    $selected = $template->getCodeName();
    $action = 'updatetpl';
    $extras = array();
    $msgtemplates=$template->getGroup()->all_names;
    $info=array_merge(array('subj'=>$template->getSubject(), 'body'=>$template->getBody()),$info);
}
$tpl=$msgtemplates[$selected];

?>
<h2>Email Template Message - <span><?php echo $name; ?></span></h2>
<div style="padding-top:10px;padding-bottom:5px;">
    <form method="get" action="templates.php">
    <input type="hidden" name="a" value="manage">
    Message Template:
    <select id="tpl_options" name="id" style="width:300px;">
        <option value="">&mdash; Select Setting Group &mdash;</option>
        <?php
        foreach($group->getTemplates() as $cn=>$t) {
            $nfo=$t->getDescription();
            if (!$nfo['name'])
                continue;
            $sel=($selected==$cn)?'selected="selected"':'';
            echo sprintf('<option value="%s" %s>%s</option>',
                    $t->getId(),$sel,$nfo['name']);
        }
        if ($id == 0) { ?>
            <option selected="selected" value="<?php echo $id; ?>"><?php
            echo $msgtemplates[$selected]['name']; ?></option>
        <?php }
        ?>
    </select>
    <input type="submit" value="Go">
    &nbsp;&nbsp;&nbsp;<font color="red"><?php echo $errors['tpl']; ?></font>
    </form>
</div>
<form action="templates.php?id=<?php echo $id; ?>" method="post" id="save">
<?php csrf_token(); ?>
<?php foreach ($extras as $k=>$v) { ?>
    <input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>" />
<?php } ?>
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="hidden" name="a" value="manage">
<input type="hidden" name="do" value="<?php echo $action; ?>">

<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
   <thead>
     <tr>
        <th colspan="2">
            <h4><?php echo Format::htmlchars($nfo['desc']); ?></h4>
            <em>Subject and body required.  <a class="tip" href="ticket_variables.txt">Supported Variables</a>.</em>
        </th>
     </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan=2>
                <strong>Message Subject:</strong> <em>Email message subject</em> <font class="error">*&nbsp;<?php echo $errors['subj']; ?></font><br>
                <input type="text" name="subj" size="60" value="<?php echo $info['subj']; ?>" >
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>Message Body:</strong> <em>Email message body.</em> <font class="error">*&nbsp;<?php echo $errors['body']; ?></font><br>
                <textarea name="body" cols="21" rows="16" style="width:98%;" wrap="soft" ><?php echo $info['body']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:210px;">
    <input class="button" type="submit" name="submit" value="Save Changes">
    <input class="button" type="reset" name="reset" value="Reset Changes">
    <input class="button" type="button" name="cancel" value="Cancel Changes"
        onclick='window.location.href="templates.php?tpl_id=<?php echo $tpl_id; ?>"'>
</p>
</form>
