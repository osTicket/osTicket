<div style="width:700;padding-top:5px; float:left;">
 <h2>Listes personnalisées</h2>
</div>
<div style="float:right;text-align:right;padding-top:5px;padding-right:5px;">
 <b><a href="lists.php?a=add" class="Icon list-add">Ajouter une liste personnalisée</a></b></div>
<div class="clear"></div>

<?php
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = DynamicList::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('lists.php');
$showing=$pageNav->showing().' custom lists';
?>

<form action="lists.php" method="POST" name="lists">
<?php csrf_token(); ?>
<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th>Nom de la liste</th>
            <th>Date de création</th>
            <th>Dernière mise à jour</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach (DynamicList::objects()->order_by('name')
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart()) as $list) {
            $sel = false;
            if ($ids && in_array($form->get('id'),$ids))
                $sel = true; ?>
        <tr>
            <td>
                <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $list->get('id'); ?>"
                    <?php echo $sel?'checked="checked"':''; ?>></td>
            <td><a href="?id=<?php echo $list->get('id'); ?>"><?php echo $list->get('name'); ?></a></td>
            <td><?php echo $list->get('created'); ?></td>
            <td><?php echo $list->get('updated'); ?></td>
        </tr>
    <?php }
    ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="4">
            <?php if($count){ ?>
            Select:&nbsp;
            <a id="selectAll" href="#ckb">Tout</a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb">Aucun</a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb">Basculer</a>&nbsp;&nbsp;
            <?php } else {
                echo 'Aucune liste personnalisée n’a été définie pour l’instant &mdash; ajoutez-en une&nbsp;!';
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count) //Show options..
    echo '<div>&nbsp;Page:'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

<p class="centered" id="actions">
    <input class="button" type="submit" name="delete" value="Supprimer">
</p>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3>Please Confirm</h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong>Êtes-vous sûr.e de vouloir SUPPRIMER les listes sélectionnéese&nbsp;?</strong></font>
        <br><br>Les formulaires supprimés ne POURRONT PAS être récupérés.
    </p>
    <div>Veuillez confirmer pour continuer.</div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons" style="float:left">
            <input type="button" value="Non, annuler" class="close">
        </span>
        <span class="buttons" style="float:right">
            <input type="button" value="Oui, je confirme&nbsp;!" class="confirm">
        </span>
    </p>
    <div class="clear"></div>
</div>
