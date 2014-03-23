<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);
?>
<h1>Check Ticket Status</h1>
<p>Please provide us with your email address and a ticket number, and an access
link will be emailed to you.</p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
<div style="display:table-row">
    <div style="display:table-cell;width:40%">
    <strong><?php echo Format::htmlchars($errors['login']); ?></strong>
    <br>
    <div>
        <label for="email">E-Mail Address:</label><br/>
        <input id="email" type="text" name="lemail" size="30" value="<?php echo $email; ?>">
    </div>
    <div>
        <label for="ticketno">Ticket Number:</label><br/>
        <input id="ticketno" type="text" name="lticket" size="16" value="<?php echo $ticketid; ?>"></td>
    </div>
    <p>
        <input class="btn" type="submit" value="Email Access Link">
    </p>
    </div>
    <div style="display:table-cell"></div>
</div>
</form>
<br>
<p>
If this is your first time contacting us or you've lost the ticket number, please <a href="open.php">open a new ticket</a>.
</p>
