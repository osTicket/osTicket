<?php
//  Holidays schedules
$holidays = $_POST ? $_POST['holidays'] : ($schedule->getHolidays() ?:
        array());
$schedules = HolidaysSchedule::getSchedules();
//    ->order_by('name')
?>
<div>
<table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead><tr><th><em><?php
        echo __('Check all Holiday Schedules applicable to this schedule');
        ?></em></th></tr></thead>
    <tbody id="schedule-holidays">
        <?php
        foreach ($schedules as $schedule) {
            $id = $schedule->getId(); ?>
            <tr id="schedule--<?php echo $id; ?>">
                <td>
                    <input type="checkbox" name="holidays[]"
                        value="<?php echo $id; ?>"
                        <?php echo in_array($id, $holidays) ?
                        'checked="checked"' : ''; ?>
                        class="schedule-holiday nowarn"/>
                    &nbsp;
                    <a style="overflow:inherit"
                       href="schedules.php?id=<?php echo $id; ?>"> <?php
                    echo Format::htmlchars($schedule->getName()); ?>
                    </a>
                     &nbsp;&nbsp;
                    <span class="faded-more"><i
                        class="icon-calendar"></i>
                    <?php echo $schedule->getNumEntries(); ?></span>
                </td>
            </tr>
        <?php
        } ?>
    </tbody>
</table>
</div>
