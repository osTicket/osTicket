<h3><?php echo __('Child Tickets'); ?></i></h3>
<hr/>
<div>
<table border="0" cellspacing="" cellpadding="1">
<colgroup><col style="min-width: 250px;"></col></colgroup>
<?php
$tid = $ticket->getId();
if (($children=$ticket->getChildren()) && (count($children) > 0)) {
    foreach($children as $child)
        echo sprintf('<tr><td>%s</td></tr>', $child[1]);
} else
    echo __("Ticket doesn't have any children.");
?>
</table>
</div>
