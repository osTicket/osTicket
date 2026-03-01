<div>
<table border="0" cellspacing="" cellpadding="1">
<colgroup><col style="min-width: 250px;"></col></colgroup>
<?php
if (($users=$thread->getCollaborators())) {?>
<?php
    foreach($users as $user) {
        echo sprintf('<tr><td %s>%s%s <em class="faded">&lt;%s&gt;</em></td></tr>',
                ($user->isActive()? '' : 'class="faded"'),
                (($U = $user->getUser()) && ($A = $U->getAvatar()))
                    ? $A->getImageTag(20) : sprintf('<i class="icon-%s"></i>',
                        ($user->isActive()? 'comments' :  'comment-alt')),
                Format::htmlchars($user->getName()),
                $user->getEmail());
    }
}  else {
    echo "<strong>".__("Thread doesn't have any collaborators.")."</strong>";
}?>
</table>
<?php
$options = array();

if ($manage)
    $options[] = sprintf(
            '<a class="collaborators" id="managecollab" href="#thread/%d/collaborators/1">%s</a>',
            $thread->getId(),
            $thread->getNumCollaborators()
            ? __('Manage Collaborators') : __('Add Collaborator')
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
