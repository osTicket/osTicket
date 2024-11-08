<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/main.inc.php');
if (isset($_POST['save_user'])) {
    $id = intval($_POST['id']);
    $noteText = htmlspecialchars($_POST['noteText'], ENT_QUOTES);
    $expiryDate = $_POST['expiryDate'];
    $noteColour = $_POST['noteColour'];

    $query = "INSERT INTO notes (text, colour, type, id, expiry) VALUES ('$noteText', '$noteColour', 'u', '$id', '$expiryDate')";

    $result = db_query($query, $logError = true, $buffered = true);
    
    if ($result) {
        echo "Note saved successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }

    header("Location: " . $_SERVER["HTTP_REFERER"]);
}
if (isset($_POST['save_company'])) {
    $id = intval($_POST['id']);
    $noteText = htmlspecialchars($_POST['noteText'], ENT_QUOTES);
    $expiryDate = $_POST['cexpiryDate'];
    $noteColour = $_POST['noteColour'];
    $query = "INSERT INTO notes (text, colour, type, id, expiry) VALUES ('$noteText', '$noteColour', 'c', '$id', '$expiryDate')";

    $result = db_query($query, $logError = true, $buffered = true);
    
    if ($result) {
        echo "Note saved successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}
if(isset($_POST['edit_note'])) {
    $id_note = $_POST['id_note'];
    $noteText = htmlspecialchars($_POST['noteText'], ENT_QUOTES);
    $expiryDate = $_POST['eexpiryDate'];
    $noteColour = $_POST['noteColour'];

    $query = "UPDATE notes SET text = '$noteText', colour = '$noteColour', expiry = '$expiryDate' WHERE id_note = '$id_note'";

    $result = db_query($query, $logError = true, $buffered = true);
    
    if ($result) {
        echo "Note Updated successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}
if(isset($_POST['delete_note'])) {
    $id_note = $_POST['id_note'];
    $expiryDate = date('Y-m-d', strtotime('-1 year'));

    $query = "UPDATE notes SET expiry = '$expiryDate' WHERE id_note = '$id_note'";

    $result = db_query($query, $logError = true, $buffered = true);
    
    if ($result) {
        echo "Note Deleted successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}

?>