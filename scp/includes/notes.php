<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/main.inc.php');

function calculatePriority($noteColour) {
    $priorityMap = [
        "alert-danger" => 0,
        "alert-warning" => 1,
        "alert-success" => 2,
        "alert-primary" => 3,
        "alert-info" => 4,
        "alert-light" => 5,
        "alert-secondary" => 6,
        "alert-dark" => 7
    ];
    return $priorityMap[$noteColour] ?? null;
}

if (isset($_POST['save_user'])) {
    $id = intval($_POST['id']);
    $noteText = htmlspecialchars($_POST['noteText'], ENT_QUOTES);
    $expiryDate = $_POST['expiryDate'];
    $noteColour = $_POST['noteColour'];
    $priority = calculatePriority($noteColour);
    $staffid = intval($_POST['staffid']);

    $query = "INSERT INTO notes (text, colour, type, id, expiry, priority, staffid) VALUES (?, ?, 'u', ?, ?, ?, ?)";
    $stmt = db_prepare($query);
    $stmt->bind_param("ssissi", $noteText, $noteColour, $id, $expiryDate, $priority, $staffid);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Note saved successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }

    header("Location: " . $_SERVER["HTTP_REFERER"]);
    exit;
}

if (isset($_POST['save_company'])) {
    $id = intval($_POST['id']);
    $noteText = htmlspecialchars($_POST['noteText'], ENT_QUOTES);
    $expiryDate = $_POST['cexpiryDate'];
    $noteColour = $_POST['noteColour'];
    $priority = calculatePriority($noteColour);
    $staffid = intval($_POST['staffid']);

    $query = "INSERT INTO notes (text, colour, type, id, expiry, priority, staffid) VALUES (?, ?, 'c', ?, ?, ?, ?)";
    $stmt = db_prepare($query);
    $stmt->bind_param("ssissi", $noteText, $noteColour, $id, $expiryDate, $priority, $staffid);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Note saved successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }
    header("Location: " . $_SERVER["HTTP_REFERER"]);
    exit;
}

if(isset($_POST['edit_note'])) {
    $id_note = intval($_POST['id_note']);
    $noteText = htmlspecialchars($_POST['noteText'], ENT_QUOTES);
    $expiryDate = $_POST['eexpiryDate'];
    $noteColour = $_POST['noteColour'];
    $priority = calculatePriority($noteColour);

    $query = "UPDATE notes SET text = ?, colour = ?, expiry = ?, priority = ? WHERE id_note = ?";
    $stmt = db_prepare($query);
    $stmt->bind_param("ssisi", $noteText, $noteColour, $expiryDate, $priority, $id_note);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Note Updated successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }
    header("Location: " . $_SERVER["HTTP_REFERER"]);
    exit;
}

if(isset($_POST['delete_note'])) {
    $id_note = intval($_POST['id_note']);
    $expiryDate = date('Y-m-d', strtotime('-1 year'));

    $query = "UPDATE notes SET expiry = ? WHERE id_note = ?";
    $stmt = db_prepare($query);
    $stmt->bind_param("si", $expiryDate, $id_note);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Note Deleted successfully!";
    } else {
        echo "Error saving note. Please try again.";
    }
    header("Location: " . $_SERVER["HTTP_REFERER"]);
    exit;
}

?>
