<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Accès refusé');
$info=array();
$qstr='';
if($dept && $_REQUEST['a']!='add') {
    //Editing Department.
    $title='Mettre à jour la section';
    $action='Mettre à jour';
    $submit_text='Sauvegardere les modifications';
    $info=$dept->getInfo();
    $info['id']=$dept->getId();
    $info['groups'] = $dept->getAllowedGroups();

    $qstr.='&id='.$dept->getId();
} else {
    $title='Ajouter une section';
    $action='créer';
    $submit_text='Créer une section';
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
    $info['ticket_auto_response']=isset($info['ticket_auto_response'])?$info['ticket_auto_response']:1;
    $info['message_auto_response']=isset($info['message_auto_response'])?$info['message_auto_response']:1;
    if (!isset($info['group_membership']))
        $info['group_membership'] = 1;

    $qstr.='&a='.$_REQUEST['a'];
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="departments.php?<?php echo $qstr; ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Section</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Informations sur la section</em>
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
                Type
            </td>
            <td>
                <input type="radio" name="ispublic" value="1" <?php echo $info['ispublic']?'checked="checked"':''; ?>><strong>Public</strong>
                &nbsp;
                <input type="radio" name="ispublic" value="0" <?php echo !$info['ispublic']?'checked="checked"':''; ?>><strong>Privé</strong> (Interne)
                &nbsp;<i class="help-tip icon-question-sign" href="#type"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Accords de niveaux de service (SLA)
            </td>
            <td>
                <select name="sla_id">
                    <option value="0">&mdash; Valeurs par défaut du système &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['sla_id']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['sla_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#sla"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Gestionnaire <!-- pour 'Manager' ? contexte ?-->
            </td>
            <td>
                <span>
                <select name="manager_id">
                    <option value="0">&mdash; Aucun &mdash;</option>
                    <?php
                    $sql='SELECT staff_id,CONCAT_WS(", ",lastname, firstname) as name '
                        .' FROM '.STAFF_TABLE.' staff '
                        .' ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)) {
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['manager_id'] && $id==$info['manager_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['manager_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#manager"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td>Attribution du ticket</td>
            <td>
                <span>
                <input type="checkbox" name="assign_members_only" <?php echo
                $info['assign_members_only']?'checked="checked"':''; ?>>
                Restreindre l’attribution du ticket aux membres de la section
                <i class="help-tip icon-question-sign" href="#sandboxing"></i>
                </span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Paramètres de messagerie en sortie</strong>&nbsp;:</em>
            </th>
        </tr>
        <tr>
            <td width="180">
                Courriel sortant
            </td>
            <td>
                <select name="email_id">
                    <option value="0">&mdash; Valeurs par défaut du système &mdash;</option>
                    <?php
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' email ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$email,$name)=db_fetch_row($res)){
                            $selected=($info['email_id'] && $id==$info['email_id'])?'selected="selected"':'';
                            if($name)
                                $email=Format::htmlchars("$name <$email>");
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$email);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['email_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#email"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Série de templates
            </td>
            <td>
                <select name="tpl_id">
                    <option value="0">&mdash; Valeurs par défaut du système &mdash;</option>
                    <?php
                    $sql='SELECT tpl_id,name FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl WHERE isactive=1 ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['tpl_id'] && $id==$info['tpl_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo $errors['tpl_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#template"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Paramètres de réponse automatique</strong>&nbsp;:
                <i class="help-tip icon-question-sign" href="#auto_response_settings"></i></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                Nouveau ticket
            </td>
            <td>
                <span>
                <input type="checkbox" name="ticket_auto_response" value="0" <?php echo !$info['ticket_auto_response']?'checked="checked"':''; ?> >

                <strong>Désactiver</strong> pour cette section&nbsp;<i class="help-tip icon-question-sign" href="#new_ticket"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Nouveau message
            </td>
            <td>
                <span>
                <input type="checkbox" name="message_auto_response" value="0" <?php echo !$info['message_auto_response']?'checked="checked"':''; ?> >
                    <strong>Désactiver</strong> pour cette section&nbsp;<i class="help-tip icon-question-sign" href="#new_message"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="180">
                Courriel de réponse automatique
            </td>
            <td>
                <span>
                <select name="autoresp_email_id">
                    <option value="0" selected="selected">&mdash; Courriel de la section &mdash;</option>
                    <?php
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' email ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$email,$name)=db_fetch_row($res)){
                            $selected = (isset($info['autoresp_email_id'])
                                    && $id == $info['autoresp_email_id'])
                                ? 'selected="selected"' : '';
                            if($name)
                                $email=Format::htmlchars("$name <$email>");
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$email);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['autoresp_email_id']; ?></span>
                <i class="help-tip icon-question-sign" href="#auto_response_email"></i>
                </span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Alertes &amp; notifications:</strong>&nbsp;<i class="help-tip icon-question-sign" href="#group_membership"></i></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                Destinataires
            </td>
            <td>
                <span>
                <select name="group_membership">
<?php foreach (array(
    Dept::ALERTS_DISABLED =>        "Personne (désactiver les alertes &amp; notifications)",
    Dept::ALERTS_DEPT_ONLY =>       "Membres de la section seulement",
    Dept::ALERTS_DEPT_AND_GROUPS => "Membres de la section et du groupe",
) as $mode=>$desc) { ?>
    <option value="<?php echo $mode; ?>" <?php
        if ($info['group_membership'] == $mode) echo 'selected="selected"';
    ?>><?php echo $desc; ?></option><?php
} ?>
                </select>
                <i class="help-tip icon-question-sign" href="#group_membership"></i>
                </span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Accès du groupe</strong>&nbsp;: vérifier tous les groupes autorisés à accéder à cette section.&nbsp;<i class="help-tip icon-question-sign" href="#department_access"></i></em>
            </th>
        </tr>
        <?php
         $sql='SELECT group_id, group_name, count(staff.staff_id) as members '
             .' FROM '.GROUP_TABLE.' grp '
             .' LEFT JOIN '.STAFF_TABLE. ' staff USING(group_id) '
             .' GROUP by grp.group_id '
             .' ORDER BY group_name';
         if(($res=db_query($sql)) && db_num_rows($res)){
            while(list($id, $name, $members) = db_fetch_row($res)) {
                if($members>0)
                    $members=sprintf('<a href="staff.php?a=filter&gid=%d">%d</a>', $id, $members);

                $ck=($info['groups'] && in_array($id,$info['groups']))?'checked="checked"':'';
                echo sprintf('<tr><td colspan=2>&nbsp;&nbsp;<label><input type="checkbox" name="groups[]" value="%d" %s>&nbsp;%s</label> (%s)</td></tr>',
                        $id, $ck, $name, $members);
            }
         }
        ?>
        <tr>
            <th colspan="2">
                <em><strong>Signature de la section</strong>:&nbsp;<span class="error">&nbsp;<?php echo $errors['signature']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#department_signature"></i></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea class="richtext no-bar" name="signature" cols="21"
                    rows="5" style="width: 60%;"><?php echo $info['signature']; ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="Réinitialiser">
    <input type="button" name="cancel" value="Annuler" onclick='window.location.href="departments.php"'>
</p>
</form>
