<?php

require_once(INCLUDE_DIR . 'class.staff.php');

class StaffAjaxAPI extends AjaxController {

  /**
   * Ajax: GET /staff/<id>/set-password
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
   * 404 - No such agent exists
   */
  function setPassword($id) {
      global $ost, $thisstaff;

      if (!$thisstaff)
          Http::response(403, 'Agent login required');
      if (!$thisstaff->isAdmin())
          Http::response(403, 'Access denied');
      if (!$id || !($staff = Staff::lookup($id)))
          Http::response(404, 'No such agent');

      $form = new PasswordResetForm($_POST);

      if ($_POST && $form->isValid()) {
          $clean = $form->getClean();
          try {
              if ($clean['email']) {
                  $staff->sendResetEmail();
              }
              else {
                  $staff->setPassword($clean['passwd1'], null);
                  if ($clean['temporary'])
                      $staff->change_passwd = 1;
              }
              if ($staff->save())
                  Http::response(201, 'Successfully updated');
          }
          catch (BadPassword $ex) {
              $passwd1 = $form->getField('passwd1');
              $passwd1->addError($ex->getMessage());
          }
          catch (PasswordUpdateFailed $ex) {
              // TODO: Add a warning banner or crash the update
          }
      }

      $title = __("Set Agent Password");
      $verb = __('Update');
      $path = ltrim($ost->get_path_info(), '/');

      include STAFFINC_DIR . 'templates/quick-add.tmpl.php';
  }

    function changePassword($id) {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$id || $thisstaff->getId() != $id)
            Http::response(404, 'No such agent');

        $form = new PasswordChangeForm($_POST);

        if ($_POST && $form->isValid()) {
            $clean = $form->getClean();
            try {
                $thisstaff->setPassword($clean['passwd1'], $clean['current']);
                if ($thisstaff->save())
                    Http::response(201, 'Successfully updated');
            }
            catch (BadPassword $ex) {
                $passwd1 = $form->getField('passwd1');
                $passwd1->addError($ex->getMessage());
            }
            catch (PasswordUpdateFailed $ex) {
                // TODO: Add a warning banner or crash the update
            }
        }

        $title = __("Change Password");
        $verb = __('Update');
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/quick-add.tmpl.php';
    }

    function getAgentPerms($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');
        if (!($staff = Staff::lookup($id)))
            Http::response(404, 'No such agent');

        return $this->encode($staff->getPermissionInfo());
    }

    function resetPermissions() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');

        $form = new ResetAgentPermissionsForm($_POST);

        if (@is_array($_GET['ids'])) {
            $perms = new RolePermission();
            $selected = Staff::objects()->filter(array('staff_id__in' => $_GET['ids']));
            foreach ($selected as $staff)
                // XXX: This maybe should be intersection rather than union
                $perms->merge($staff->getPermission());
            $form->getField('perms')->setValue($perms->getInfo());
        }

        if ($_POST && $form->isValid()) {
            $clean = $form->getClean();
            Http::response(201, $this->encode(array('perms' => $clean['perms'])));
        }

        $title = __("Reset Agent Permissions");
        $verb = __("Continue");
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/reset-agent-permissions.tmpl.php';
    }

    function changeDepartment() {
        global $ost, $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Agent login required');
        if (!$thisstaff->isAdmin())
            Http::response(403, 'Access denied');

        $form = new ChangeDepartmentForm($_POST);

        // Preselect reasonable dept and role based on the current  settings
        // of the received staff ids
        if (@is_array($_GET['ids'])) {
            $dept_id = null;
            $role_id = null;
            $selected = Staff::objects()->filter(array('staff_id__in' => $_GET['ids']));
            foreach ($selected as $staff) {
                if (!isset($dept_id)) {
                    $dept_id = $staff->dept_id;
                    $role_id = $staff->role_id;
                }
                elseif ($dept_id != $staff->dept_id)
                    $dept_id = 0;
                elseif ($role_id != $staff->role_id)
                    $role_id = 0;
            }
            $form->getField('dept_id')->setValue($dept_id);
            $form->getField('role_id')->setValue($role_id);
        }

        if ($_POST && $form->isValid()) {
            $clean = $form->getClean();
            Http::response(201, $this->encode($clean));
        }

        $title = __("Change Primary Department");
        $verb = __("Continue");
        $path = ltrim($ost->get_path_info(), '/');

        include STAFFINC_DIR . 'templates/quick-add.tmpl.php';
    }
}
