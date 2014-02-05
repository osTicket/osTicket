<div>
<table border="0" cellspacing="" cellpadding="1">
<colgroup><col style="min-width: 250px;"></col></colgroup>
<?php
if (($users=$ticket->getCollaborators())) {?>
<?php
    foreach($users as $user) {
        echo sprintf('<tr><td %s><i class="icon-%s"></i> %s <em>&lt;%s&gt;</em></td></tr>',
                ($user->isActive()? '' : 'class="faded"'),
                ($user->isActive()? 'comments' :  'comment-alt'),
                $user->getName(),
                $user->getEmail());
    }
}  else {
    echo "<strong>Ticket doesn't have collaborators.</strong>";
}?>
</table>
<?php
$options = array();

$options[] = sprintf(
        '<a class="collaborators" id="managecollab" href="#tickets/%d/collaborators">%s</a>',
        $ticket->getId(),
        $ticket->getNumCollaborators()
        ? 'Manage Collaborators' : 'Add Collaborator'
        );

if ($options) {
    echo '<ul class="tip_menu">';
    foreach($options as $option)
        echo sprintf('<li>%s</li>', $option);
    echo '</ul>';
}
?>
</div>
<script type="text/javascript">
$(function() {
    $(document).on('click', 'a#managecollab', function (e) {
        e.preventDefault();
        $('.tip_box').remove();
        return false;
    });
});
</script>
