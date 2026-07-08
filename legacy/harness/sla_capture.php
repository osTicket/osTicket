<?php
require_once __DIR__ . '/../../main.inc.php';

$input = json_decode($argv[1], true);
$schedule = BusinessHoursSchedule::lookup($input['schedule_id']);
$bh = new BusinessHours($schedule);
$date = new DateTime($input['start']);
$result = $bh->addWorkingHours($date, $input['hours']);

echo json_encode([
    'input' => $input,
    'output' => $result ? $result->format('Y-m-d\TH:i:s') : null,
]);
