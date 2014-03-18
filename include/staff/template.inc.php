<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info=array();
$qstr='';
if($template && $_REQUEST['a']!='add'){
    $title='Update Template';
    $action='update';
    $submit_text='Save Changes';
    $info=$template->getInfo();
    $info['tpl_id']=$template->getId();
    $qstr.='&tpl_id='.$template->getId();
}else {
    $title='Add New Template';
    $action='add';
    $submit_text='Add Template';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:0;
    $info['lang_id'] = $cfg->getSystemLanguage();
    $qstr.='&a='.urlencode($_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="templates.php?<?php echo $qstr; ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="tpl_id" value="<?php echo $info['tpl_id']; ?>">
 <h2>Email Template</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Template information.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
              Name:
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Status:
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong>Active</strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>>Disabled
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['isactive']; ?></span>
            </td>
        </tr>
        <?php
        if($template){ ?>
        <tr>
            <td width="180" class="required">
                Language:
            </td>
            <td>
                <?php
            $langs = Internationalization::availableLanguages();
            $lang = strtolower($info['lang']);
            if (isset($langs[$lang]))
                echo $langs[$lang]['desc'];
            else
                echo $info['lang']; ?>
            </td>
        </tr>
        <?php
            $current_group = false;
            $impl = $template->getTemplates();
            $_tpls = $template::$all_names;
            $_groups = $template::$all_groups;
            uasort($_tpls, function($a,$b) {
                return strcmp($a['group'].$a['name'], $b['group'].$b['name']);
            });
         foreach($_tpls as $cn=>$info){
             if (!$info['name'])
                 continue;
             if (!$current_group || $current_group != $info['group']) {
                $current_group = $info['group']; ?>
        <tr>
            <th colspan="2">
            <em><strong><?php echo isset($_groups[$current_group])
            ? $_groups[$current_group] : $current_group; ?></strong>
            :: Click on the title to edit.&nbsp;</em>
            </th>
        </tr>
<?php } # end if ($current_group)
            if (isset($impl[$cn])) {
                echo sprintf('<tr><td colspan="2">&nbsp;<strong><a href="templates.php?id=%d&a=manage">%s</a></strong>, <span class="faded">Updated %s</span><br/>&nbsp;%s</td></tr>',
                $impl[$cn]->getId(), Format::htmlchars($info['name']),
                Format::db_datetime($impl[$cn]->getLastUpdated()),
                Format::htmlchars($info['desc']));
            } else {
                echo sprintf('<tr><td colspan=2>&nbsp;<strong><a
                    href="templates.php?tpl_id=%d&a=implement&code_name=%s"
                    >%s</a></strong><br/>&nbsp%s</td></tr>',
                    $template->getid(),$cn,format::htmlchars($info['name']),
                    format::htmlchars($info['desc']));
            }
         } # endfor
        } else { ?>
        <tr>
            <td width="180" class="required">
                Language:
            </td>
            <td>
        <?php
        $langs = Internationalization::availableLanguages(); ?>
                <select name="lang_id">
<?php foreach($langs as $l) {
    $selected = ($info['lang_id'] == $l['code']) ? 'selected="selected"' : ''; ?>
                    <option value="<?php echo $l['code']; ?>" <?php echo $selected;
                        ?>><?php echo $l['desc']; ?></option>
<?php } ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['lang_id']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Template To Clone:
            </td>
            <td>
                <select name="tpl_id">
                    <option value="0">&mdash; Select One &dash;</option>
                    <?php
                    $sql='SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_GRP_TABLE.' ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['tpl_id'] && $id==$info['tpl_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['tpl_id']; ?></span>
                 <em>(select an existing template to copy and edit it thereafter)</em>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th colspan="2">
                <em><strong>Admin Notes</strong>: Internal notes.&nbsp;</em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="notes" cols="21"
                    rows="8" style="width: 80%;"><?php echo $info['notes']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="padding-left:225px;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Reset">
    <input type="button" name="cancel" value="Cancel" onclick='window.location.href="templates.php"'>
</p>
</form>
