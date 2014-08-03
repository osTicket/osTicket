<?php
if(!defined('OSTCLIENTINC')) die('Accès Refusé');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = "Envoyer le lien d'accès";
else
    $button = "Voir le Ticket";
?>
<h1>Vérification du statut du ticket</h1>
<p>Veuillez nous fournir votre adresse de courriel, un numéro de ticket, et nous vous enverrons un lien d'accès.</p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div style="width:40%;display:table-cell;box-shadow: 12px 0 15px -15px rgba(0,0,0,0.4);padding-right: 2em;">
    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    <br>
    <div>
        <label for="email">Adresse de courriel&nbsp;:
        <input id="email" placeholder="e.g. john.doe@osticket.com" type="text"
            name="lemail" size="30" value="<?php echo $email; ?>"></label>
    </div>
    <div>
        <label for="ticketno">Numéro de ticket&nbsp;:
        <input id="ticketno" type="text" name="lticket" placeholder="e.g. 051243"
            size="30" value="<?php echo $ticketid; ?>"></label>
    </div>
    <p>
        <input class="btn" type="submit" value="<?php echo $button; ?>">
    </p>
    </div>
    <div style="display:table-cell;padding-left: 2em;padding-right:90px;">
<?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
        Vous avez déjà un compte chez nous ?
        <a href="login.php">Connectez-vous</a> <?php
    if ($cfg->isClientRegistrationEnabled()) { ?>
        ou <a href="account.php?do=create">créez un compte</a> <?php
    } ?> pour accéder à tous vos tickets.
<?php
} ?>
    </div>
</div>
</form>
<br>
<p>
Si c'est la première fois que vous nous contactez ou si vous avez oublié votre numéro de ticket, veuillez <a href="open.php">ouvrir un nouveau ticket</a>.
</p>
