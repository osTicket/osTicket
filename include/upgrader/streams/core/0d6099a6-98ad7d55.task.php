<?php

class StaffPermissions extends MigrationTask {
    var $description = "Add staff permissions";

    function run($time) {
        foreach (Staff::objects() as $staff) {
            $role = $staff->getRole()->getPermission();
            $perms = array(
                User::PERM_CREATE,
                User::PERM_EDIT,
                User::PERM_DELETE,
                User::PERM_MANAGE,
                User::PERM_DIRECTORY,
                Organization::PERM_CREATE,
                Organization::PERM_EDIT,
                Organization::PERM_DELETE,
            );
            if ($role->has(FAQ::PERM_MANAGE))
                $perms[] = FAQ::PERM_MANAGE;
            if ($role->has(Email::PERM_BANLIST))
                $perms[] = Email::PERM_BANLIST;

            $errors = array();
            $staff->updatePerms($perms, $errors);
            $staff->save();
        }
    }
}
return 'StaffPermissions';
