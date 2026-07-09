<?php
require_once __DIR__ . '/../../main.inc.php';
require_once INCLUDE_DIR . 'class.i18n.php';

$i18n = new Internationalization('en_US');
$schedules = $i18n->getTemplate('schedule.yaml')->getData();

if (!$schedules) {
    fwrite(STDERR, "No schedule data found in i18n\n");
    exit(1);
}

foreach ($schedules as $schedule) {
    Schedule::__create($schedule);
}

echo json_encode(['loaded' => count($schedules)]) . "\n";
