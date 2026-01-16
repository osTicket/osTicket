<?php
require_once(INCLUDE_DIR . 'class.plugin.php');

class PluginsAjaxAPI extends AjaxController {

    /*
     * Protect all routines in this controller
     */
    function access() {
        global $thisstaff;
        if (!$thisstaff || !$thisstaff->isAdmin())
             Http::response(403, 'Access Denied');

        return true;
    }

    // helper func to look up plugin & instance
    private function lookup($plugin_id, $instance_id=0) {
        if (!($plugin = PluginManager::lookup( (int) $plugin_id)))
            Http::response(404, 'No such plugin');

        if ($instance_id && !($instance = $plugin->getInstance( (int) $instance_id)))
            Http::response(404, 'No such plugin instance');

        return [$plugin, $instance];
    }

    function getInstances($plugin_id) {
        list($plugin,)= $this->lookup($plugin_id);
        $pjax_container = '#items';
        include(STAFFINC_DIR . 'templates/plugin-instances.tmpl.php');
    }

    function updateInstance($plugin_id, $instance_id) {
        list($plugin, $instance) = $this->lookup($plugin_id, $instance_id);
        $errors = array();
        if ($_POST && $instance->update($_POST, $errors))
            Http::response(201, $this->encode([
                'redirect' => sprintf('plugins.php?id=%d#instances',
                    $plugin->getId())
            ]));
        $form = $instance->getForm();
        $action = "#plugins/{$plugin->getId()}/instances/{$instance->getId()}/update";
        include STAFFINC_DIR . 'templates/plugin-instance-modal.tmpl.php';
    }

    function addInstance($plugin_id) {
        list($plugin,) = $this->lookup($plugin_id);
        $errors = array();
        if ($_POST  && ($instance=$plugin->addInstance($_POST, $errors)))
            Http::response(201, $this->encode([
                'redirect' => sprintf('plugins.php?id=%d#instances',
                    $plugin->getId())
            ]));
        // This should return cached form with errors (if any)
        $form = $plugin->getConfigForm();
        // Set action
        $action = "#plugins/{$plugin->getId()}/instances/add";
        include(STAFFINC_DIR . 'templates/plugin-instance-modal.tmpl.php');
    }
}
?>
