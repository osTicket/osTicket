<?php
 $path = 'mobile-text.txt';
 if (isset($_POST['title'])) {
    $fh = fopen($path,"wa+");
    $string = $_POST['title'];
    fwrite($fh,$string); // Write information to the file
    fclose($fh); // Close the file
	header('Location: ' . $_SERVER['HTTP_REFERER']);
 }
?>