<?php
 $path = 'selected.txt';
 if (isset($_POST['theme'])) {
    $fh = fopen($path,"wa+");
    $string = $_POST['theme'];
    fwrite($fh,$string); // Write information to the file
    fclose($fh); // Close the file
	header('Location: ' . $_SERVER['HTTP_REFERER']);
 }
?>