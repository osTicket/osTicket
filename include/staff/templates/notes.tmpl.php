<div id="quick-notes">
<?php
$show_options = true;
foreach ($notes as $note) {
    include STAFFINC_DIR."templates/note.tmpl.php";
} ?>
</div>
<div class="quicknote" id="new-note" data-ext-id="<?php echo $ext_id; ?>">
<div class="body">
    <a href="#"><i class="icon-plus icon-large"></i> &nbsp; Click to create a new note</a>
</div>
</div>
