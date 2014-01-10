<div>
<table border="0" cellspacing="" cellpadding="1">
<colgroup><col style="min-width: 250px;"></col></colgroup>
<?php
if (($users=$ticket->getCollaborators())) {?>
<?php
    foreach($users as $user) {
        echo sprintf('<tr><td %s><i class="icon-%s"></i> %s <em>&lt;%s&gt;</em></td></tr>',
                ($user->isActive()? 'class="faded"' : ''),
                ($user->isActive()? 'comments' :  'comment-alt'),
                $user->getName(),
                $user->getEmail());
    }
}  else {
    echo "Bro, not sure how you got here!";
}?>
</table>
<?php
$options = array();
//TODO: Add options to manage collaborators
if ($options) {
    echo '<ul class="tip_menu">';
    foreach($options as $option)
        echo sprintf('<li><a href="%s">%s</a></li>', $option['url'], $option['action']);
    echo '</ul>';
}
?>
</div>
