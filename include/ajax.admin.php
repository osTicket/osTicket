<?php

require_once(INCLUDE_DIR . 'class.dept.php');

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
     */
    function addDepartment() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');

        $form = new DepartmentQuickAddForm($_POST);

        if ($_POST && $form->isValid()) {
            $dept = Dept::create();
            $errors = array();
            $vars = $form->getClean();
            $vars += array(
                'group_membership' => Dept::ALERTS_DEPT_AND_GROUPS,
            );
            if ($dept->update($vars, $errors))
                Http::response(201, $this->encode(array(
                    'id' => $dept->id,
                    'name' => $dept->name,
                ), 'application/json'));

            foreach ($errors as $name=>$desc)
                if ($F = $form->getField($name))
                    $F->addError($desc);
        }

        $title = __("Add New Department");
        $path = $ost->get_path_info();

        include STAFFINC_DIR . 'templates/quick-add-department.tmpl.php';
    }
}
