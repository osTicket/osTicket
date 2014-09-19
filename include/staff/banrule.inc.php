<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Accès refusé');

$info=array();
$qstr='';
if($rule && $_REQUEST['a']!='add'){
    $title='Mettre à jour les règles d’interdiction';
    $action='Mettre à jour';
    $submit_text='Mettre à jour';
    $info=$rule->getInfo();
    $info['id']=$rule->getId();
    $qstr.='&id='.$rule->getId();
}else {
    $title='Ajouter une adresse courriel à la liste des interdictions';
    $action='Ajouter';
    $submit_text='Ajouter';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $qstr.='&a='.urlencode($_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="banlist.php?<?php echo $qstr; ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Gérer les règles d’interdiction des adresses courriel
    <i class="help-tip icon-question-sign" href="#ban_list"></i>
    </h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Une adresse de courriel valide est requise.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                Statut
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong>Activé</strong>
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>>Désactivé
                &nbsp;<span class="error">*&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Adresse de courriel
            </td>
            <td>
                <input name="val" type="text" size="24" value="<?php echo $info['val']; ?>">
                 &nbsp;<span class="error">*&nbsp;<?php echo $errors['val']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Notes internes</strong>&nbsp;: remarques admin&nbsp;</em>
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
    <input type="reset"  name="reset"  value="Réinitialiser">
    <input type="button" name="cancel" value="Annuler" onclick='window.location.href="banlist.php"'>
</p>
</form>
