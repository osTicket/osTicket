<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Accès refusé');

$matches=Filter::getSupportedMatches();
$match_types=Filter::getSupportedMatchTypes();

$info=array();
$qstr='';
if($filter && $_REQUEST['a']!='add'){
    $title='Mettre à jour le filtre';
    $action='update';
    $submit_text='Sauvegarder les modifications';
    $info=array_merge($filter->getInfo(),$filter->getFlatRules());
    $info['id']=$filter->getId();
    $qstr.='&id='.$filter->getId();
}else {
    $title='Ajouter un filtre';
    $action='add';
    $submit_text='Ajouter un filtre';
    $info['isactive']=isset($info['isactive'])?$info['isactive']:0;
    $qstr.='&a='.urlencode($_REQUEST['a']);
}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<form action="filters.php?<?php echo $qstr; ?>" method="post" id="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <h2>Filtres sur les tickets</h2>
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <h4><?php echo $title; ?></h4>
                <em>Les filtres sont exécutés selon l’ordre d’exécution. Un filtre peut cibler spécifiquement la source d’un ticket.</em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
              Nom du filtre
            </td>
            <td>
                <input type="text" size="30" name="name" value="<?php echo $info['name']; ?>">
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['name']; ?></span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
              Ordre d’exécution
            </td>
            <td>
                <input type="text" size="6" name="execorder" value="<?php echo $info['execorder']; ?>">
                <em>(1...99)</em>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['execorder']; ?></span>
                &nbsp;&nbsp;&nbsp;
                <input type="checkbox" name="stop_onmatch" value="1" <?php echo $info['stop_onmatch']?'checked="checked"':''; ?> >
                <strong>Interrompre</strong> le traitement lorsqu’une correspondance est trouvée&nbsp;!&nbsp;<i class="help-tip icon-question-sign" href="#execution_order"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Statut du filtre
            </td>
            <td>
                <input type="radio" name="isactive" value="1" <?php echo
                $info['isactive']?'checked="checked"':''; ?>> Activé
                <input type="radio" name="isactive" value="0" <?php echo !$info['isactive']?'checked="checked"':''; ?>> Désactivé
                &nbsp;<span class="error">*&nbsp;</span>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                Canal de la cible
            </td>
            <td>
                <select name="target">
                   <option value="">&mdash; Sélectionner un canal &dash;</option>
                   <?php
                   foreach(Filter::getTargets() as $k => $v) {
                       echo sprintf('<option value="%s" %s>%s</option>',
                               $k, (($k==$info['target'])?'selected="selected"':''), $v);
                    }
                    $sql='SELECT email_id,email,name FROM '.EMAIL_TABLE.' email ORDER by name';
                    if(($res=db_query($sql)) && db_num_rows($res)) {
                        echo '<OPTGROUP label="Système spécifique de courriel">';
                        while(list($id,$email,$name)=db_fetch_row($res)) {
                            $selected=($info['email_id'] && $id==$info['email_id'])?'selected="selected"':'';
                            if($name)
                                $email=Format::htmlchars("$name <$email>");
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$email);
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>
                &nbsp;
                <span class="error">*&nbsp;<?php echo $errors['target']; ?></span>&nbsp;
                <i class="help-tip icon-question-sign" href="#target_channel"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Règles de filtrage</strong>&nbsp;: des règles sont appliquées suivant les critères définis.&nbsp;<span class="error">*&nbsp;<?php echo
                $errors['rules']; ?></span></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
               <em>Critères de correspondance des règles</em>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="radio" name="match_all_rules" value="1" <?php echo $info['match_all_rules']?'checked="checked"':''; ?>>Correspondance sur tout
                &nbsp;&nbsp;&nbsp;
                <input type="radio" name="match_all_rules" value="0" <?php echo !$info['match_all_rules']?'checked="checked"':''; ?>>Correspondance sur l’un au moins
                &nbsp;<span class="error">*&nbsp;</span>
                <em>(comparaison insensible à la casse)</em>&nbsp;<i class="help-tip icon-question-sign" href="#rules_matching_criteria"></i>

            </td>
        </tr>
        <?php
        $n=($filter?$filter->getNumRules():0)+2; //2 extra rules of unlimited.
        for($i=1; $i<=$n; $i++){ ?>
        <tr id="r<?php echo $i; ?>">
            <td colspan="2">
                <div>
                    <select style="max-width: 200px;" name="rule_w<?php echo $i; ?>">
                        <option value="">&mdash; Sélectionner un élément &dash;</option>
                        <?php
                        foreach ($matches as $group=>$ms) { ?>
                            <optgroup label="<?php echo $group; ?>"><?php
                            foreach ($ms as $k=>$v) {
                                $sel=($info["rule_w$i"]==$k)?'selected="selected"':'';
                                echo sprintf('<option value="%s" %s>%s</option>',$k,$sel,$v);
                            } ?>
                        </optgroup>
                        <?php } ?>
                    </select>
                    <select name="rule_h<?php echo $i; ?>">
                        <option value="0">&mdash; Sélectionner un élément &dash;</option>
                        <?php
                        foreach($match_types as $k=>$v){
                            $sel=($info["rule_h$i"]==$k)?'selected="selected"':'';
                            echo sprintf('<option value="%s" %s>%s</option>',$k,$sel,$v);
                        }
                        ?>
                    </select>&nbsp;
                    <input type="text" size="60" name="rule_v<?php echo $i; ?>" value="<?php echo $info["rule_v$i"]; ?>">
                    &nbsp;<span class="error">&nbsp;<?php echo $errors["rule_$i"]; ?></span>
                <?php
                if($info["rule_w$i"] || $info["rule_h$i"] || $info["rule_v$i"]){ ?>
                <div style="float:right;text-align:right;padding-right:20px;"><a href="#" class="clearrule">(clear)</a></div>
                <?php
                } ?>
                </div>
            </td>
        </tr>
        <?php
            if($i>=25) //Hardcoded limit of 25 rules...also see class.filter.php
               break;
        } ?>
        <tr>
            <th colspan="2">
                <em><strong>Actions des filtres</strong>&nbsp;: peuvent être surchargées par d’autres filtres en fonction de l’ordre de traitement.&nbsp;</em>
            </th>
        </tr>
        <tr>
            <td width="180">
                Rejeter le ticket
            </td>
            <td>
                <input type="checkbox" name="reject_ticket" value="1" <?php echo $info['reject_ticket']?'checked="checked"':''; ?> >
                    <strong><font class="error">Rejeter le ticket</font></strong>&nbsp;<i class="help-tip icon-question-sign" href="#reject_ticket"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Répondre au courriel
            </td>
            <td>
                <input type="checkbox" name="use_replyto_email" value="1" <?php echo $info['use_replyto_email']?'checked="checked"':''; ?> >
                    <strong>Utiliser</strong> la réponse au courriel <em>(if available)&nbsp;<i class="help-tip icon-question-sign" href="#reply_to_email"></i></em>
            </td>
        </tr>
        <tr>
            <td width="180">
                Réponse automatique au ticket
            </td>
            <td>
                <input type="checkbox" name="disable_autoresponder" value="1" <?php echo $info['disable_autoresponder']?'checked="checked"':''; ?> >
                    <strong>Désactiver</strong> la réponse automatique&nbsp;<i class="help-tip icon-question-sign" href="#ticket_auto_response"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Réponse prédéfinie
            </td>
                <td>
                <select name="canned_response_id">
                    <option value="">&mdash; Aucune &mdash;</option>
                    <?php
                    $sql='SELECT canned_id, title, isenabled FROM '.CANNED_TABLE .' ORDER by title';
                    if ($res=db_query($sql)) {
                        while (list($id, $title, $isenabled)=db_fetch_row($res)) {
                            $selected=($info['canned_response_id'] &&
                                    $id==$info['canned_response_id'])
                                ? 'selected="selected"' : '';

                            if (!$isenabled)
                                $title .= ' (disabled)';

                            echo sprintf('<option value="%d" %s>%s</option>',
                                $id, $selected, $title);
                        }
                    }
                    ?>
                </select>
                &nbsp;<i class="help-tip icon-question-sign" href="#canned_response"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Département
            </td>
            <td>
                <select name="dept_id">
                    <option value="">&mdash; par défaut &mdash;</option>
                    <?php
                    $sql='SELECT dept_id,dept_name FROM '.DEPT_TABLE.' dept ORDER by dept_name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['dept_id'] && $id==$info['dept_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['dept_id']; ?></span>&nbsp;<i class="help-tip icon-question-sign" href="#department"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Priorité
            </td>
            <td>
                <select name="priority_id">
                    <option value="">&mdash; par défaut &mdash;</option>
                    <?php
                    $sql='SELECT priority_id,priority_desc FROM '.PRIORITY_TABLE.' pri ORDER by priority_urgency DESC';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['priority_id'] && $id==$info['priority_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error">*&nbsp;<?php echo $errors['priority_id']; ?></span>
                &nbsp;<i class="help-tip icon-question-sign" href="#priority"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Plan pour la convention de service (SLA)
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
                &nbsp;<span class="error">&nbsp;<?php echo $errors['sla_id']; ?></span>
                &nbsp;<i class="help-tip icon-question-sign" href="#sla_plan"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Attribution automatique à
            </td>
            <td>
                <select name="assign">
                    <option value="0">&mdash; Non attribué &mdash;</option>
                    <?php
                    if (($users=Staff::getStaffMembers())) {
                        echo '<OPTGROUP label="Membres du personnel">';
                        foreach($users as $id => $name) {
                            $name = new PersonsName($name);
                            $k="s$id";
                            $selected = ($info['assign']==$k || $info['staff_id']==$id)?'selected="selected"':'';
                            ?>
                            <option value="<?php echo $k; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        }
                        echo '</OPTGROUP>';
                    }
                    $sql='SELECT team_id, isenabled, name FROM '.TEAM_TABLE .' ORDER BY name';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        echo '<OPTGROUP label="Équipes">';
                        while (list($id, $isenabled, $name) = db_fetch_row($res)){
                            $k="t$id";
                            $selected = ($info['assign']==$k || $info['team_id']==$id)?'selected="selected"':'';
                            if (!$isenabled)
                                $name .= ' (disabled)';
                            ?>
                            <option value="<?php echo $k; ?>"<?php echo $selected; ?>><?php echo $name; ?></option>
                        <?php
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>
                &nbsp;<span class="error">&nbsp;<?php echo
                $errors['assign']; ?></span><i class="help-tip icon-question-sign" href="#auto_assign"></i>
            </td>
        </tr>
        <tr>
            <td width="180">
                Rubrique d’aide
            </td>
            <td>
                <select name="topic_id">
                    <option value="0" selected="selected">&mdash; Inchangé &mdash;</option>
                    <?php
                    $sql='SELECT topic_id, topic FROM '.TOPIC_TABLE.' T ORDER by topic';
                    if(($res=db_query($sql)) && db_num_rows($res)){
                        while(list($id,$name)=db_fetch_row($res)){
                            $selected=($info['topic_id'] && $id==$info['topic_id'])?'selected="selected"':'';
                            echo sprintf('<option value="%d" %s>%s</option>',$id,$selected,$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<span class="error"><?php echo $errors['topic_id']; ?></span><i class="help-tip icon-question-sign" href="#help_topic"></i>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong>Notes de l’admin</strong>&nbsp;: notes internes.</em>
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
    <input type="button" name="cancel" value="Annuler" onclick='window.location.href="filters.php"'>
</p>
</form>
