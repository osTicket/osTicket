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
      $path = ltrim($ost->get_path_info(), '/');

      include STAFFINC_DIR . 'templates/set-password.tmpl.php';
  }
}
