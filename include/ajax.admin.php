<?php

require_once(INCLUDE_DIR . 'class.dept.php');
require_once(INCLUDE_DIR . 'class.role.php');
require_once(INCLUDE_DIR . 'class.team.php');

class AdminAjaxAPI extends AjaxController {

    /**
     * Ajax: GET /admin/add/department
     *
     * Uses a dialog to add a new department
     *
     * Returns:
     * 200 - HTML form for addition
     * 201 - {id: <id>, name: <name>}
     *
     * Throws:
     * 403 - Not logged in
     * 403 - Not an administrator
     */
    function addDepartment() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');

        $form = new DepartmentQuickAddForm($_POST);

        if ($_POST && $form->isValid()) {
            $dept = Dept::create();
            $errors = array();
            $vars = $form->getClean();
            $vars += array(
                'group_membership' => Dept::ALERTS_DEPT_AND_EXTENDED,
            );
            if ($dept->update($vars, $errors)) {
                Http::response(201, $this->encode(array(
                    'id' => $dept->id,
                    'name' => $dept->name,
                ), 'application/json'));
            }
            foreach ($errors as $name=>$desc)
                if ($F = $form->getField($name))
                    $F->addError($desc);
        }

        $title = __("Add New Department");
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/quick-add.tmpl.php';
    }

    /**
     * Ajax: GET /admin/add/team
     *
     * Uses a dialog to add a new team
     *
     * Returns:
     * 200 - HTML form for addition
     * 201 - {id: <id>, name: <name>}
     *
     * Throws:
     * 403 - Not logged in
     * 403 - Not an adminitrator
     */
    function addTeam() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');

        $form = new TeamQuickAddForm($_POST);

        if ($_POST && $form->isValid()) {
            $team = Team::create();
            $errors = array();
            $vars = $form->getClean();
            $vars += array(
                'isenabled' => true,
            );
            if ($team->update($vars, $errors)) {
                Http::response(201, $this->encode(array(
                    'id' => $team->getId(),
                    'name' => $team->name,
                ), 'application/json'));
            }
            foreach ($errors as $name=>$desc)
                if ($F = $form->getField($name))
                    $F->addError($desc);
        }

        $title = __("Add New Team");
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/quick-add.tmpl.php';
    }

    /**
     * Ajax: GET /admin/add/role
     *
     * Uses a dialog to add a new role
     *
     * Returns:
     * 200 - HTML form for addition
     * 201 - {id: <id>, name: <name>}
     *
     * Throws:
     * 403 - Not logged in
     * 403 - Not an adminitrator
     */
    function addRole() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');

        $form = new RoleQuickAddForm($_POST);

        if ($_POST && $form->isValid()) {
            $role = Role::create();
            $errors = array();
            $vars = $form->getClean();
            if ($role->update($vars, $errors)) {
                Http::response(201, $this->encode(array(
                    'id' => $role->getId(),
                    'name' => $role->name,
                ), 'application/json'));
            }
            foreach ($errors as $name=>$desc)
                if ($F = $form->getField($name))
                    $F->addError($desc);
        }

        $title = __("Add New Role");
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/quick-add-role.tmpl.php';
    }

    function getRolePerms($id) {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');
        if (!($role = Role::lookup($id)))
            Http::response(404, 'No such role');

        return $this->encode($role->getPermissionInfo());
    }

    function addStaff() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');

        $form = new StaffQuickAddForm($_POST);

        if ($_POST && $form->isValid()) {
            $staff = Staff::create();
            $errors = array();
            if ($staff->update($form->getClean(), $errors)) {
                Http::response(201, $this->encode(array(
                    'id' => $staff->getId(),
                    'name' => (string) $staff->getName(),
                ), 'application/json'));
            }
            foreach ($errors as $name=>$desc) {
                if ($F = $form->getField($name)) {
                    $F->addError($desc);
                    unset($errors[$name]);
                }
            }
            $errors['err'] = implode(", ", $errors);
        }

        $title = __("Add New Agent");
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/quick-add.tmpl.php';
    }
}
