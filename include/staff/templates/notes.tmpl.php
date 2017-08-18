<div id="quick-notes" style="padding-top:15px">
<?php
$show_options = true;
foreach ($notes as $note) {
    include STAFFINC_DIR."templates/note.tmpl.php";
} ?>
</div>
<div id="new-note-box">
<div class="quicknote" id="new-note" data-url="<?php echo $create_note_url; ?>">
<div class="body">
    <a href="#"   data-placement="bottom"
                    data-toggle="tooltip" title="<?php echo __('Click to create a new note'); ?>"><i class="fa fa-plus-square"  style="color:#000;margin-left: -15px;"></i> &nbsp;
    </a>
</div>
</div>
</div>
