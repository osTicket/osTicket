<?php
?>
	<h1>Ticket Information</h1>
	<h2>Request Details</h2>
    <!--<form action="timebill.php?id=<?php echo $_REQUEST['id']?>&view=invoice" method="post" id="save">-->
	<form action="timebill.php" method="get" id="searchform">
    <!--<?php csrf_token(); ?>-->
		<input type="hidden" id="search" name="search" value="yes">
		<p>Fill out the below form to view required report.</p>
		<label for="view">Choose a view:</label>
		<select name="view" id="view">
			<option value="time">Time Report</option>
			<option value="invoice">Invoice</option>
		</select><br />
		<label for="id">Ticket Number:</label>
		<input type="text" id="id" name="id"><br />
		<input type="submit" value="Search">
	</form>
<?php
?>