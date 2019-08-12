<?php
// Migration task to add schedules on upgrade
class ScheduleCreator extends MigrationTask {
    var $description = "Load customziable ticket queues";
    function run($time) {
        $i18n = new Internationalization('en_US');
        $schedules = $i18n->getTemplate('schedule.yaml')->getData();
        foreach ($schedules as $schedule) {
            Schedule::__create($schedule);
        }
    }
}
return 'ScheduleCreator';
