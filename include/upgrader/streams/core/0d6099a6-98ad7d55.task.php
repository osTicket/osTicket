<?php

class StaffPermissions extends MigrationTask {
    var $description = "Add staff permissions";

    function run($time) {
        foreach (Staff::objects() as $staff) {
            $role = $staff->getRole();
            if ($role)
                $role_perms = $role->getPermission();
            else
                $role_perms = new RolePermission(null);
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
            if ($role_perms->has(FAQ::PERM_MANAGE))
                $perms[] = FAQ::PERM_MANAGE;
            if ($role_perms->has(Email::PERM_BANLIST))
                $perms[] = Email::PERM_BANLIST;

            $errors = array();
            $staff->updatePerms($perms, $errors);
            $staff->save();
        }

        // Update user's with <div> in their name (regression from v1.9.9)
        foreach (
            User::objects()->filter(array('name__startswith' => ' <div>'))
            as $user
        ) {
            $user->name = ltrim(str_replace(' <div>', '', $user->name));
            $user->save();
        }
    }
}
return 'StaffPermissions';
