<?php
 $path = 'subtitle.txt';
 if (isset($_POST['subtitle'])) {
    $fh = fopen($path,"wa+");
    $string = $_POST['subtitle'];
    fwrite($fh,$string); // Write information to the file
    fclose($fh); // Close the file
	header('Location: ' . $_SERVER['HTTP_REFERER']);
 }
?>