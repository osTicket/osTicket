<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Accès refusé');
//Get the config info.
$config=($errors && $_POST)?Format::input($_POST):$cfg->getConfigInfo();
?>
<table width="100%" border="0" cellspacing=0 cellpadding=0>
    <form action="admin.php?t=attach" method="post">
    <input type="hidden" name="t" value="attach">
    <tr>
      <td>
        <table width="100%" border="0" cellspacing=0 cellpadding=2 class="tform">
          <tr class="header">
            <td colspan=2>&nbsp;Paramètres pour les fichiers attachés</td>
          </tr>
          <tr class="subheader">
            <td colspan=2">
                Avant d’autoriser les fichiers joints, assurez-vous de bien comprendre les paramètres de sécurité et autres questions relatives au chargement de fichiers.</td>
          </tr>
          <tr>
            <th width="165">Autoriser les fichiers joints</th>
            <td>
              <input type="checkbox" name="allow_attachments" <?php echo $config['allow_attachments'] ?'checked':''; ?>><b>Autoriser les fichiers joints</b>
                &nbsp; (<i>Paramètre général</i>)
                &nbsp;<font class="error">&nbsp;<?php echo $errors['allow_attachments']; ?></font>
            </td>
          </tr>
          <tr>
            <th>Fichiers joints par courriel</th>
            <td>
                <input type="checkbox" name="allow_email_attachments" <?php echo $config['allow_email_attachments'] ? 'checked':''; ?> > Accepter les fichiers joints par courriel
                    &nbsp;<font class="warn">&nbsp;<?php echo $warn['allow_email_attachments']; ?></font>
            </td>
          </tr>
         <tr>
            <th>Fichiers joints en ligne</th>
            <td>
                <input type="checkbox" name="allow_online_attachments" <?php echo $config['allow_online_attachments'] ?'checked':''; ?> >
                    Autoriser le chargement de fichiers en ligne<br/>&nbsp;&nbsp;&nbsp;&nbsp; <!-- je suppose qu’il s’agit de joindre une URL/URI ?-->
                <input type="checkbox" name="allow_online_attachments_onlogin" <?php echo $config['allow_online_attachments_onlogin'] ?'checked':''; ?> >
                    Utilisateurs authentifés seulement (<i>L’utilisateur doit être connecté pour pouvoir charger des fichiers.</i>)
                    <font class="warn">&nbsp;<?php echo $warn['allow_online_attachments']; ?></font>
            </td>
          </tr>
          <tr>
            <th>Fichiers de réponse de l’équipe</th> <!-- je traduis staff=> équipe, dans le contexte, ça me semble plus juste ^^-->
            <td>
                <input type="checkbox" name="email_attachments" <?php echo $config['email_attachments']?'checked':''; ?> >Fichiers joints par courriel à l’utilisateur
            </td>
          </tr>
          <tr>
            <th nowrap>Taille maximale du fichier</th>
            <td>
              <input type="text" name="max_file_size" value="<?php echo $config['max_file_size']; ?>"> <i>bytes</i>
                <font class="error">&nbsp;<?php echo $errors['max_file_size']; ?></font>
            </td>
          </tr>
          <tr>
            <th>Dossier des fichiers joints</th>
            <td>
                L’utilisateur web (par exemple, apache) doit avoir un accès en écriture au dossier. &nbsp;<font class="error">&nbsp;<?php echo $errors['upload_dir']; ?></font><br>
              <input type="text" size=60 name="upload_dir" value="<?php echo $config['upload_dir']; ?>"> 
              <font color=red>
              <?php echo $attwarn; ?>
              </font>
            </td>
          </tr>
          <tr>
            <th valign="top"><br/>Types de fichiers acceptés</th>
            <td>
                Entrer les extensions de fichiers autorisées en les séparant par une virgule, par exemple <i>.doc, .pdf, </i> <br>
                Pour accepter tous les types de fichiers, entrer le métacaractère <b><i>.*</i></b>&nbsp;&nbsp;c.-à-d. le caraactère ‘étoile’ (NON recommandé).
                <textarea name="allowed_filetypes" cols="21" rows="4" style="width: 65%;" wrap=HARD ><?php echo $config['allowed_filetypes']; ?></textarea>
            </td>
          </tr>
        </table>
    </td></tr>
    <tr><td style="padding:10px 0 10px 200px">
        <input class="button" type="submit" name="submit" value="Sauvegarder les modifications">
        <input class="button" type="reset" name="reset" value="Réinitialiser les modifications">
    </td></tr>
  </form>
</table>
