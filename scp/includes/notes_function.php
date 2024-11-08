<?php
function company_notes($id) {
    $query = "SELECT * FROM notes WHERE id = '$id' AND type = 'c' ORDER BY id_note ASC";
    $commit = db_query($query, $logError = true, $buffered = true);
    while ($row = $commit->fetch_assoc()) {
        if (strtotime($row['expiry']) >= strtotime('today')) {
			$colour = $row['colour'];
			$text = htmlspecialchars_decode($row['text']);
			$expiry = $row['expiry'];
			$id_note = $row['id_note'];
			echo '
			<div class="d-flex justify-content-between align-items-center">
				<div class="col-md-1"></div>
				<div class="alert '.$colour.' text-center fs-3 col-md" role="alert" style="--bs-alert-padding-x: 0; --bs-alert-padding-y: 0;--bs-alert-margin-bottom:0;">
					'.$text.' <asa class="fs-6 text-end" >- Expiry: '.$expiry.'</asa>
				</div>
				<div class="col-md-1"><button type="button" class="btn btn-primary" id="add" data-bs-toggle="modal" data-bs-target="#editNoteModal-'.$id_note.'"><i class="bi bi-pencil"></i></button></div>
			</div>';
			include('includes/note_edit_modal.php');
		}
    }
}
function user_notes($id) {
    $query = "SELECT * FROM notes WHERE id = '$id' AND type = 'u' ORDER BY id_note ASC";
    $commit = db_query($query, $logError = true, $buffered = true);
    while ($row = $commit->fetch_assoc()) {
        if (strtotime($row['expiry']) >= strtotime('today')) {
			$colour = $row['colour'];
			$text = htmlspecialchars_decode($row['text']);
			$expiry = $row['expiry'];
			$id_note = $row['id_note'];
			echo '
			<div class="d-flex justify-content-between align-items-center">
				<div class="col-md-1"></div>
				<div class="alert '.$colour.' text-center fs-3 col-md" role="alert" style="--bs-alert-padding-x: 0; --bs-alert-padding-y: 0;--bs-alert-margin-bottom:0;">
					'.$text.' <asa class="fs-6 text-end" >- Expiry: '.$expiry.'</asa>
				</div>
				<div class="col-md-1"><button type="button" class="btn btn-primary" id="add" data-bs-toggle="modal" data-bs-target="#editNoteModal-'.$id_note.'"><i class="bi bi-pencil"></i></button></div>
			</div>';
			include('includes/note_edit_modal.php');
		}
    }
}
function auth_img($id) {
	$query = "SELECT contact_important, contact_billing, contact_decisions FROM `tbyte-portal`.contacts WHERE contact_ticket_id = '$id'";
	$commit = db_query($query, $logError = true, $buffered = true);
	
	echo '<div class="text-end">';
	while ($row = $commit->fetch_assoc()) {
		
		if ($row['contact_important']){
			echo '<i class="bi bi-exclamation-circle-fill"></i>';
		}
		if ($row['contact_billing']){
			echo '<i class="bi bi-cash-coin"></i>';
		}
		if ($row['contact_decisions']){
			echo '<i class="bi bi-person-fill-check"></i>';
		}
	}
	echo '</div>';
}
?>