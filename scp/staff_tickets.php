<?php
/*********************************************************************
 * staff_tickets.php
 * 
 * Custom staff ticket detail page filtered by staff_id
 *********************************************************************/

require('staff.inc.php'); // ✅ Loads osTicket admin framework

// Access check — only staff allowed
if (!$thisstaff || !$thisstaff->isStaff()) {
    die('Access Denied');
}

// Get staff_id from URL
$staff_id = isset($_GET['staff_id']) ? (int)$_GET['staff_id'] : 0;

if (!$staff_id) {
    echo "<h2>Invalid or unknown staff</h2>";
    exit;
}

// SQL query to fetch ticket details for this staff
$sql = "
SELECT 
    t.ticket_id,
    t.number AS ticket_number,
    c.subject AS ticket_subject,
    u.name AS user_name,
    ue.address AS user_email,
    s.firstname AS staff_firstname,
    s.lastname AS staff_lastname,
    ts.name AS ticket_status,
    t.created AS ticket_created
FROM ost_ticket t
LEFT JOIN ost_ticket__cdata c ON t.ticket_id = c.ticket_id
LEFT JOIN ost_user u ON t.user_id = u.id
LEFT JOIN ost_user_email ue ON u.id = ue.user_id
LEFT JOIN ost_staff s ON t.staff_id = s.staff_id
LEFT JOIN ost_ticket_status ts ON t.status_id = ts.id
WHERE s.staff_id = $staff_id
ORDER BY t.created DESC
";

$res = db_query($sql);

require_once(STAFFINC_DIR.'header.inc.php'); // ✅ include header template
?>

<h2>Tickets Assigned to Staff</h2>
<table class="list" border="0" cellspacing="0" cellpadding="2" width="100%">
    <thead>
        <tr>
            <th>Ticket #</th>
            <th>Subject</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (db_num_rows($res)) {
            while ($row = db_fetch_array($res)) {
                echo '<tr>';
                echo '<td><a href="tickets.php?id=' . $row['ticket_id'] . '">' . $row['ticket_number'] . '</a></td>';
                echo '<td>' . Format::htmlchars($row['ticket_subject']) . '</td>';
                echo '<td>' . Format::htmlchars($row['user_name']) . '</td>';
                echo '<td>' . Format::htmlchars($row['user_email']) . '</td>';
                echo '<td>' . Format::htmlchars($row['ticket_status']) . '</td>';
                echo '<td>' . Format::htmlchars($row['ticket_created']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6"><em>No tickets found for this staff member.</em></td></tr>';
        }
        ?>
    </tbody>
</table>

<?php require_once(STAFFINC_DIR.'footer.inc.php'); ?>
