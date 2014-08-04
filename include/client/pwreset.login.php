<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$userid=Format::input($_POST['userid']);
?>
<h1>J'ai oublié mon mot de passe</h1>
<p>
Saisissez votre nom d'utilisateur et votre adresse électronique dans les champs ci-dessous et cliquez sur
<strong>Connexion</strong> pour accéder à votre compte et réinitialiser votre mot de passe.

<form action="pwreset.php" method="post" id="clientLogin">
    <div style="width:50%;display:inline-block">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="reset"/>
    <input type="hidden" name="token" value="<?php echo Format::htmlchars($_REQUEST['token']); ?>"/>
    <strong><?php echo Format::htmlchars($banner); ?></strong>
    <br>
    <div>
        <label for="username">Nom d'utilisateur&nbsp;:</label>
        <input id="username" type="text" name="userid" size="30" value="<?php echo $userid; ?>">
    </div>
    <p>
        <input class="btn" type="submit" value="Connexion">
    </p>
    </div>
</form>
