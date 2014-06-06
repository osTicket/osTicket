<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = "Email Access Link";
else
    $button = "View Ticket";
?>
<h1>Check Ticket Status</h1>
<p>Please provide us with your email address and a ticket number, and an access
link will be emailed to you.</p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div style="width:40%;display:table-cell;box-shadow: 12px 0 15px -15px rgba(0,0,0,0.4);padding-right: 2em;">
    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    <br>
    <div>
        <label for="email">E-Mail Address:
        <input id="email" placeholder="e.g. john.doe@osticket.com" type="text"
            name="lemail" size="30" value="<?php echo $email; ?>"></label>
    </div>
    <div>
        <label for="ticketno">Ticket Number:</label><br/>
        <input id="ticketno" type="text" name="lticket" placeholder="e.g. 051243"
            size="30" value="<?php echo $ticketid; ?>"></td>
    </div>
    <p>
        <input class="btn" type="submit" value="<?php echo $button; ?>">
    </p>
    </div>
    <div style="display:table-cell;padding-left: 2em;padding-right:90px;">
<?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
        Have an account with us?
        <a href="login.php">Sign In</a> <?php
    if ($cfg->isClientRegistrationEnabled()) { ?>
        or <a href="account.php?do=create">register for an account</a> <?php
    } ?> to access all your tickets.
<?php
} ?>
    </div>
</div>
</form>
<br>
<p>
If this is your first time contacting us or you've lost the ticket number, please <a href="open.php">open a new ticket</a>.
</p>
