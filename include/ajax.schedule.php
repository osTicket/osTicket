<?php
require_once(INCLUDE_DIR . 'class.schedule.php');

class ScheduleAjaxAPI extends AjaxController {

    function add($sid=0) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        if ($sid && !($schedule = Schedule::lookup($sid)))
            Http::response(404, 'No such schedule');
        elseif ($_POST['sid'])
            $schedule =  Schedule::lookup($_POST['sid']);

        $action = '#schedule/add';
        $errors = array();
        $form = Schedule::basicForm($_POST ?: ($schedule ? array(
                        'type' => $schedule->getType(),
                        'timezone' => $schedule->timezone,
                        'description' => 'Copy of '.$schedule->getName()
                        ) : null));
        if ($_POST && $form->isValid()) {
            $data = $form->getClean();
            if (Schedule::getIdByName($data['name'])) {
                $form->getField('name')->addError(
                        __('Name already in use'));
            } elseif ($schedule  &&  strcasecmp($schedule->getType(),
                        $data['type'])) {
                $errors['sid'] = sprintf(
                        __('Type must be of %s to clone this Schedule'),
                        $schedule->getTypeDesc());
            } else {
                $vars = array_intersect_key($data, array_flip(['name',
                            'timezone', 'description']));
                $s = Schedule::create($vars);
                $s->setFlag(Schedule::FLAG_BIZHRS, ($data['type'] ==
                            'bizhrs'));
                if ($s->save()) {
                    if ($schedule) $s->cloneEntries($schedule);
                    Http::response(201, $s->getId());
                }
            }
        }

        include STAFFINC_DIR.'templates/schedule-add.tmpl.php';
    }

    function cloneSchedule($id) {
        return $this->add($id);
    }

    function addEntry($id) {
        global $thisstaff;

        if (!$thisstaff)
            Http::response(403, 'Login required');
        elseif (!($schedule = Schedule::lookup($id)))
            Http::response(404, 'No such schedule');

        $form = $schedule->getEntryForm($_POST ?: null);
        $errors = array();
        if ($_POST && $form->isValid()) {
            if (($entry = $schedule->addEntry($form, $errors)))
                Http::response(201, $entry->getId());

            foreach ($errors as $k => $error) {
                if (($f=$form->getField($k)))
                    $f->addError($error);
            }

        }

        include STAFFINC_DIR . 'templates/schedule-entry.tmpl.php';
    }

    function updateEntry($sid, $id) {
        global $thisstaff;

        if (!$thisstaff)
             Http::response(403, 'Login required');
        if (!($entry = ScheduleEntry::lookup($id))
                || !($schedule=$entry->getSchedule())
                || $schedule->getId() != $sid)
            Http::response(404, 'Unknown Entry');
        $form = $entry->getForm($_POST ?: null);
        $errors = array();
        if ($_POST && $entry->update($form, $errors))
            Http::response(201, $entry->getId());

        foreach ($errors as $k => $error) {
            if (($f=$form->getField($k)))
                $f->addError($error);
        }

        include STAFFINC_DIR . 'templates/schedule-entry.tmpl.php';
    }


    function diagnostic($id) {
        global $thisstaff;

        if (!$thisstaff)
             Http::response(403, 'Login required');
        elseif (!($schedule = BusinessHoursSchedule::lookup($id)))
            Http::response(404, 'No such schedule');

        $form = $schedule->getDiagnosticForm($_POST ?: null);
        if ($_POST) $form->isValid();
        include STAFFINC_DIR . 'templates/schedule-diagnostic.tmpl.php';
    }

    function deleteEntries($id) {
        global $thisstaff;

        if (!$thisstaff)
             Http::response(403, 'Login required');
        if (!($schedule = Schedule::lookup($id)))
            Http::response(404, 'Unknown Schedule');

        $count = 0;
        if (isset($_POST['ids']) && is_array($_POST['ids']))
            $count = $schedule->entries
                ->filter(array('id__in' => array_values($_POST['ids'])))
                ->delete();

        Http::response(200, $this->encode(array('success' => $count)));
    }
}
?>
