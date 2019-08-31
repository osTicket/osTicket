<?php
require_once INCLUDE_DIR . 'class.export.php';

class ExportAjaxAPI extends AjaxController {

    function check($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login is required');
        elseif (!($exporter=Exporter::load($id)) || !$exporter->isAvailable())
            Http::response(404, 'No such export');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($exporter->isReady())
                Http::response(201, $this->json_encode([
                            'status' => 'ready',
                            'href' => sprintf('export.php?id=%s',
                                $exporter->getId()),
                            'filename' => $exporter->getFilename()]));
            else // Export is not ready... checkback in a few
                Http::response(200, $this->json_encode([
                        'status' => 'notready']));
        }

        include STAFFINC_DIR . 'templates/export.tmpl.php';
    }
}
