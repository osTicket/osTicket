<?php
require_once __DIR__ . '/../../main.inc.php';

$input = json_decode($argv[1], true);
$sla = SLA::__hydrate(['flags' => $input['flags']]);
$result = $sla->priorityEscalation();

echo json_encode([
    'input' => $input,
    'output' => $result,
]);
