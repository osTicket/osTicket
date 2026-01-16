<?php
$name = $item->getValue();
if ($abbrev=$item->getAbbrev())
    $name = sprintf('%s (%s)', $name, $abbrev);

?>
<h2><?php echo Format::htmlchars($name); ?></h2>
<hr/>

<?php
if ($item->hasProperties()) { ?>
<div>
    <table class="custom-info" width="100%">
        <?php
        foreach ($item->getFields() as $f) {
            if (!$f->isVisible()) continue;
        ?>
            <tr><td style="width:30%;"><?php echo
                Format::htmlchars($f->get('label')); ?>:</td>
            <td><?php echo $f->display($f->value); ?></td>
            </tr>
        <?php }
        ?>
    </table>
</div>
<?php
} ?>
