<?php
if(!defined('OSTCLIENTINC')) die('Accès Refusé');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);

$content = Page::lookup(Page::getIdByType('banner-client'));

if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
        array($content->getName(), $content->getBody()));
} else {
    $title = 'Enregistrez-vous';
    $body = 'Pour mieux vous rendre service, nous vous invitons à ouvrir un compte et vérifier l\'adresse électronique que nous avons enregistrée.';
}

?>
<h1><?php echo Format::display($title); ?></h1>
<p><?php echo Format::display($body); ?></p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div style="width:40%;display:table-cell;box-shadow: 12px 0 15px -15px rgba(0,0,0,0.4);padding:15px;">
    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    <div>
        <input id="username" placeholder="Courriel ou identifiant" type="text" name="luser" size="30" value="<?php echo $email; ?>">
    </div>
    <div>
        <input id="passwd" placeholder="Mot de passe" type="password" name="lpasswd" size="30" value="<?php echo $passwd; ?>"></td>
    </div>
    <p>
        <input class="btn" type="submit" value="Sign In">
<?php if ($suggest_pwreset) { ?>
        <a style="padding-top:4px;display:inline-block;" href="pwreset.php">J'ai oublié mon mot de passe</a>
<?php } ?>
    </p>
    </div>
    <div style="display:table-cell;padding: 15px;vertical-align:top">
<?php

$ext_bks = array();
foreach (UserAuthenticationBackend::allRegistered() as $bk)
    if ($bk instanceof ExternalAuthentication)
        $ext_bks[] = $bk;

if (count($ext_bks)) {
    foreach ($ext_bks as $bk) { ?>
<div class="external-auth"><?php $bk->renderExternalLink(); ?></div><?php
    }
}
if ($cfg && $cfg->isClientRegistrationEnabled()) {
    if (count($ext_bks)) echo '<hr style="width:70%"/>'; ?>
    Pas encore enregistré&nbsp;? <a href="account.php?do=create">Créer un compte</a>
    <br/>
    <div style="margin-top: 5px;">
    <b>Je suis un agent</b> —
    <a href="<?php echo ROOT_PATH; ?>scp">connectez-vous ici</a>
    </div>
<?php } ?>
    </div>
</div>
</form>
<br>
<p>
<?php if ($cfg && !$cfg->isClientLoginRequired()) { ?>
Si vous nous contactez pour la première fois ou si vous avez perdu votre numéro de ticket, veuillez <a href="open.php">ouvrir un nouveau ticket</a>.
<?php } ?>
</p>
