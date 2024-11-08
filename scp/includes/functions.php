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
function auth_img($id, $cid) {
    $query = "SELECT contact_important, contact_billing, contact_decisions, contact_notes FROM `tbyte-portal`.contacts WHERE contact_ticket_id = '$id'";
    $commit = db_query($query, $logError = true, $buffered = true);
	echo '&nbsp;<a class="bi bi-list-check fs-5 text-secondary" title="Auth List" href="https://portal.remoteit.co.uk/client/contacts/?ticket_id='.$cid.'"></a>&nbsp;';
    while ($row = $commit->fetch_assoc()) {
        $clean_notes = strip_tags(html_entity_decode($row['contact_notes']));
        
        if ($row['contact_important']) {
            echo '&nbsp;<a class="bi bi-exclamation-circle-fill fs-5 text-black" title="IMPORTANT: ' . htmlspecialchars($clean_notes) . '" href="https://portal.remoteit.co.uk/client/contacts/?ticket_id='.$cid.'"></a>&nbsp;';
        }
        if ($row['contact_billing']) {
            echo '&nbsp;<a class="bi bi-cash-coin fs-5 text-black" title="BILLING: ' . htmlspecialchars($clean_notes) . '" href="https://portal.remoteit.co.uk/client/contacts/?ticket_id='.$cid.'"></a>&nbsp;';
        }
        if ($row['contact_decisions']) {
            echo '&nbsp;<a class="bi bi-person-fill-check fs-5 text-black" title="DECISIONS: ' . htmlspecialchars($clean_notes) . '" href="https://portal.remoteit.co.uk/client/contacts/?ticket_id='.$cid.'"></a>&nbsp;';
        }
    }
}


?>