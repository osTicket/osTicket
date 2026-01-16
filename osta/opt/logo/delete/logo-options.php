<?php
 $path = 'logo-options.txt';
 if (isset($_POST['logo-options'])) {
    $fh = fopen($path,"wa+");
    $string = $_POST['logo-options'];
    fwrite($fh,$string); // Write information to the file
    fclose($fh); // Close the file
	header('Location: ' . $_SERVER['HTTP_REFERER']);
 }
?>