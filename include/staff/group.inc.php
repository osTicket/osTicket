<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Accès refusé');
$info=array();
$qstr='';
if($group && $_REQUEST['a']!='add'){
    $title='Mettre à jour le groupe';
    $action='update';
    $submit_text='Sauvegarder les modifications';
    $info=$group->getInfo();
    $info['id']=$group->getId();
    $info['depts']=$group->getDepartments();
    $qstr.='&id='.$group->getId();
}else {
    $title='Ajouter un groupe';
    $action='create';
    $submit_text='Créer un groupe';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:1;
    $info['can_create_tickets']=isset($info['can_create_tickets'])?$info['can_create_tickets']:1;
    $qstr.='&a='.$_REQUEST['a'];
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="groups.php?<?php echo $qstr; ?>" method="post" id="save" name="group">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Groupe d'utilisateurs</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em><strong>Informations sur les groupes</strong>&nbsp;: Les groupes désactivés limiteront les accès pour les membres de l’équipe. Cela ne concerne pas les admins.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                Nom
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Statut
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo $info['isactive']?'checked="checked"':''; ?>><strong>Activé</strong>
                &nbsp;
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>>Désactivé
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['status']; ?></span>
                <i class="help-tip icon-question-sign" href="#status"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Permissions du groupe</strong>&nbsp;: s’applique à tous les membres du groupe&nbsp;</em>
            </th>
        </tr>
        <tr><td>Peut <b>créer</b> des tickets</td>
            <td>
                <input type="radio" name="can_create_tickets"  value="1"   <?php echo $info['can_create_tickets']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_create_tickets"  value="0"   <?php echo !$info['can_create_tickets']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’ouvrir des tickets pour le compte d’utilisateurs.</i>
            </td>
        </tr>
        <tr><td>Peut <b>éditer</b> des tickets</td>
            <td>
                <input type="radio" name="can_edit_tickets"  value="1"   <?php echo $info['can_edit_tickets']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_edit_tickets"  value="0"   <?php echo !$info['can_edit_tickets']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’éditer des tickets.</i>
            </td>
        </tr>
        <tr><td>Peut <b>envoyer des réponses</b></td>
            <td>
                <input type="radio" name="can_post_ticket_reply"  value="1"   <?php echo $info['can_post_ticket_reply']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_post_ticket_reply"  value="0"   <?php echo !$info['can_post_ticket_reply']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’envoyer une réponse à un ticket.</i>
            </td>
        </tr>
        <tr><td>Peut <b>fermer</b> des tickets</td>
            <td>
                <input type="radio" name="can_close_tickets"  value="1" <?php echo $info['can_close_tickets']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_close_tickets"  value="0" <?php echo !$info['can_close_tickets']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité de fermer des tickets. L’équipe pourra continuer d’envoyer des réponses.</i>
            </td>
        </tr>
        <tr><td>Peut <b>attribuer</b> des tickets</td>
            <td>
                <input type="radio" name="can_assign_tickets"  value="1" <?php echo $info['can_assign_tickets']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_assign_tickets"  value="0" <?php echo !$info['can_assign_tickets']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’attribuer des tickets aux membres de l’équipe.</i>
            </td>
        </tr>
        <tr><td>Peut <b>transférer</b> des tickets</td>
            <td>
                <input type="radio" name="can_transfer_tickets"  value="1" <?php echo $info['can_transfer_tickets']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_transfer_tickets"  value="0" <?php echo !$info['can_transfer_tickets']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité de transférer des tickets d’un département à un autre.</i>
            </td>
        </tr>
        <tr><td>Peut <b>supprimer</b> des tickets</td>
            <td>
                <input type="radio" name="can_delete_tickets"  value="1"   <?php echo $info['can_delete_tickets']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_delete_tickets"  value="0"   <?php echo !$info['can_delete_tickets']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité de supprimer des tickets (les tickets supprimés ne pourront pas être récupérés&nbsp;!)</i>
            </td>
        </tr>
        <tr><td>Peut interdire des adresses de courriel</td>
            <td>
                <input type="radio" name="can_ban_emails"  value="1" <?php echo $info['can_ban_emails']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_ban_emails"  value="0" <?php echo !$info['can_ban_emails']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’ajouter/de retirer des adresses de courriels de la liste des interdictions via l’interface des tickets.</i>
            </td>
        </tr>
        <tr><td>Peut gérer les modèles</td>
            <td>
                <input type="radio" name="can_manage_premade"  value="1" <?php echo $info['can_manage_premade']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_manage_premade"  value="0" <?php echo !$info['can_manage_premade']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’ajouter/mettre à jour/désactiver/effacer les réponses prédéterminées et les fichiers attachés.</i>
            </td>
        </tr>
        <tr><td>Peut gérer les FAQ</td>
            <td>
                <input type="radio" name="can_manage_faq"  value="1" <?php echo $info['can_manage_faq']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_manage_faq"  value="0" <?php echo !$info['can_manage_faq']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité d’ajouter/mettre à jour/désactiver/effacer les catégories de la base de connaissance et les FAQ.</i>
            </td>
        </tr>
        <tr><td>Peut visualiser les statistiques de l’équipe</td>
            <td>
                <input type="radio" name="can_view_staff_stats"  value="1" <?php echo $info['can_view_staff_stats']?'checked="checked"':''; ?> />Oui
                &nbsp;&nbsp;
                <input type="radio" name="can_view_staff_stats"  value="0" <?php echo !$info['can_view_staff_stats']?'checked="checked"':''; ?> />Non
                &nbsp;&nbsp;<i>Possibilité de voir les statistiques des autres membres de l’équipe dans les départements autorisés.</i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Accès aux départements</strong>&nbsp;:
                <i class="help-tip icon-question-sign" href="#department_access"></i>
                &nbsp;<a id="selectAll" href="#deptckb">Sélectionner tout</a>
                &nbsp;&nbsp;
                <a id="selectNone" href="#deptckb">N'en sélectionner aucun</a></em>
            </th>
        </tr>
        <?php
         $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' ORDER BY dept_name';
         if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id,$name) = db_fetch_row($res)){
                $ck=($info['depts'] && in_array($id,$info['depts']))?'checked="checked"':'';
                echo sprintf('<tr><td colspan=2>&nbsp;&nbsp;<input type="checkbox" class="deptckb" name="depts[]" value="%d" %s>%s</td></tr>',$id,$ck,$name);
            }
         }
        ?>
        <tr>
            <th colspan="2">
                <em><strong>Remarques de l’admin</strong>&nbsp;: tous les admins peuvent voir les notes internes.&nbsp;</em>
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
<p style="text-align:center">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Réinitialiser">
    <input type="button" name="cancel" value="Annuler" onclick='window.location.href="groups.php"'>
</p>
</form>
