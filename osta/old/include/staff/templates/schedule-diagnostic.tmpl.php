<?php
$action = "#schedule/{$schedule->getId()}/diagnostic";
?>
<h3 class="drag-handle"><?php
    echo __('Timeline Diagnostics');
    ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<div><em><?php echo $schedule->getName(); ?></em></div>
<hr/>
<form method="post" action="<?php echo $action; ?>">
    <?php
    echo csrf_token();
    $form = $form ?: $schedule->getDiagnosticForm();
    echo $form->asTable('');
    if ($form->isValid()) {
        $timeline = array();
        $data = $form->getClean();
        // Add timezone if it's not ISO compliant date (GET request)
        if (strlen($data['date']) <= 16)
            $data['date'] .= ' '.$schedule->getTimezone();

        $schedule->addWorkingHours(Format::parseDateTime($data['date']), $data['hours'], $timeline);
        ?>
        <div id="diagnostic-results"
            style="overflow-y: auto; max-height:400px; margin-bottom:5px;">
        <table class="custom-info" with="100%">
            <tbody><tr><th><?php echo __('Working Hours Timeline');
                ?></th></tr>
                <?php
                foreach ($timeline as $v)
                    echo sprintf('<tr><td>%s</td></tr>', $v);
                ?>
            </tbody>
        </table>
        </div>
   <?php
    }
    ?>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" value="<?php echo __('Done'); ?>" class="close">
        </span>
        <span class="buttons pull-right">
        <input type="submit" value="<?php echo __('Apply'); ?>">
        </span>
     </p>
</form>
<?php
// echo $form->emitJavascript();
?>
