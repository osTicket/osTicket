<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);
?>
<h1><?php t("Check Ticket Status"); ?></h1>
<p><?php t("To view the status of a ticket, provide us with the login details below."); ?></p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    <br>
    <div>
        <label for="email"><?php t("E-Mail Address"); ?>:</label>
        <input id="email" type="text" name="lemail" size="30" value="<?php echo $email; ?>">
    </div>
    <div>
        <label for="ticketno"><?php t("Ticket ID"); ?>:</label>
        <input id="ticketno" type="text" name="lticket" size="16" value="<?php echo $ticketid; ?>"></td>
    </div>
    <p>
        <input class="btn" type="submit" value="<?php t("View Status"); ?>">
    </p>
</form>
<br>
<p>
<?php t("If this is your first time contacting us or you've lost the ticket ID, please %s open a new ticket%s.", '<a href="open.php">', "</a>"); ?>    
</p>
