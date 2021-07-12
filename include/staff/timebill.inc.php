<?php
?>
	<h1>Ticket Information</h1>
	<h2>Request Details</h2>
    <form action="timebill.php?id=<?php echo $_REQUEST['id']?>&view=invoice" method="post" id="save">
    <?php csrf_token(); ?>
		Form still in production; but this page works from a ticket direct<br />
		to test append your url with ?id=8668&view=invoice for example
	</form>
<?php
?>