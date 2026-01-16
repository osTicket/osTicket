<?php
 $path = 'language-bar.txt';
 if (isset($_POST['language-bar'])) {
    $fh = fopen($path,"wa+");
    $string = $_POST['language-bar'];
    fwrite($fh,$string); // Write information to the file
    fclose($fh); // Close the file
	header('Location: ' . $_SERVER['HTTP_REFERER']);
 }
?>