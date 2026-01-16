<?php
 $path = 'mobile-link.txt';
 if (isset($_POST['link'])) {
    $fh = fopen($path,"wa+");
    $string = $_POST['link'];
    fwrite($fh,$string); // Write information to the file
    fclose($fh); // Close the file
	header('Location: ' . $_SERVER['HTTP_REFERER']);
 }
?>