<?php
require_once INCLUDE_DIR.'class.upgrader.php';

class CliUpgrader extends Module {
    var $prologue = "Upgrade an osTicket help desk";

    var $options = array(
        'summary' => array('-s', '--summary',
            'action' => 'store_true',
            'help' => 'Print an upgrade summary and exit'),
    );

    function run($args, $options) {
        Bootstrap::connect();

        // Pre-checks
        global $ost;
        $ost = osTicket::start();
        $upgrader = $this->getUpgrader();

        if ($options['summary']) {
            $this->doSummary($upgrader);
        }
        else {
            $this->upgrade($upgrader);
        }
    }

    function getUpgrader() {
        global $ost;

        if (!$ost->isUpgradePending()) {
            $this->fail('No upgrade is pending for this account');
        }

        $upgrader = new Upgrader(TABLE_PREFIX, UPGRADE_DIR.'streams/');
        if (!$upgrader->isUpgradable()) {
            $this->fail(__('The upgrader does NOT support upgrading from the current vesion!'));
        }
        elseif (!$upgrader->check_prereq()) {
            $this->fail(__('Minimum requirements not met! Refer to Release Notes for more information'));
        }
        elseif (!strcasecmp(basename(CONFIG_FILE), 'settings.php')) {
            $this->fail(__('Config file rename required to continue!'));
        }
        return $upgrader;
    }

    function upgrade($upgrader) {
        global $ost, $cfg;
        $cfg = $ost->getConfig();

        while (true) {
            // If there's anythin in the model cache (like a Staff
            // object or something), ensure that changes to the database
            // model won't cause crashes
            ModelInstanceManager::flushCache();

            if ($upgrader->getTask()) {
                // More pending tasks - doTasks returns the number of pending tasks
                $this->stdout->write("... {$upgrader->getNextAction()}\n");
                $upgrader->doTask();
            }
            elseif ($ost->isUpgradePending()) {
                if ($upgrader->isUpgradable()) {
                    $this->stdout->write("... {$upgrader->getNextVersion()}\n");
                    $upgrader->upgrade();
                    // Reload config to pull schema_signature changes
                    $cfg->load();
                }
                else {
                    $this->fail(sprintf(
                        __('Upgrade Failed: Invalid or wrong hash [%s]'),
                        $ost->getDBSignature()
                    ));
                }
            }
            elseif (!$ost->isUpgradePending()) {
                $this->stdout->write("Yay! All finished!\n");
                break;
            }
        }
    }

    function doSummary($upgrader) {
        foreach ($upgrader->getPatches() as $p) {
            $info = $upgrader->readPatchInfo($p);
            $this->stdout->write(sprintf(
                "%s :: %s (%s)\n", $info['version'], $info['title'],
                substr($info['signature'], 0, 8)
            ));
        }
    }
}

Module::register('upgrade', 'CliUpgrader');
