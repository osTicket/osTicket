<?php

class ReportModel {

    const PERM_AGENTS = 'stats.agents';

    static protected $perms = array(
            self::PERM_AGENTS => array(
                'title' =>
                /* @trans */ 'Stats',
                'desc'  =>
                /* @trans */ 'Ability to view stats of other agents in allowed departments',
                'primary' => true,
            ));

    static function getPermissions() {
        return self::$perms;
    }
}

RolePermission::register(/* @trans */ 'Miscellaneous', ReportModel::getPermissions());
?>
