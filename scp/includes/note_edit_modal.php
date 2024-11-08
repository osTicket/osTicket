<div class="modal fade" id="editNoteModal-<?php echo $id_note ?>" tabindex="-1" role="dialog" aria-labelledby="editNoteModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
<script>
    $('#editNoteModal-<?php echo $id_note ?>').on('shown.bs.modal', function () {
        $(function() {
            $("#eexpiryDate").datepicker({
                showButtonPanel: true,
                dateFormat: 'yy-mm-dd',
                numberOfMonths: 2,
            });
        });
    });
</script>
    <div class="modal-dialog modal-dialog-centered" style="max-width: 35%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editNoteModalLabel">Edit Note - <?php echo $id_note ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action='/scp/includes/notes.php' method="post" autocomplete="off" id="addNoteForm">
                <input type="hidden" name="id_note" value="<?php echo $id_note ?>">    
                <div class="mb-4">
                        <label for="noteText">Note Text</label>
                        <textarea class="form-control" id="noteText" name="noteText" rows="3"><?php echo $text ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md mb-4">
                            <label for="noteColour">Note Colour</label>
                            <select class="form-control" id="noteColour" name="noteColour" style="width: 80%;">
                                <option value="alert-primary" <?php if($colour == 'alert-primary' ){echo 'selected'; }?>>Blue</option>
                                <option value="alert-secondary" <?php if($colour == 'alert-secondary' ){echo 'selected'; }?>>Dark Grey</option>
                                <option value="alert-success" <?php if($colour == 'alert-success' ){echo 'selected'; }?>>Green</option>
                                <option value="alert-danger" <?php if($colour == 'alert-danger' ){echo 'selected'; }?>>Red</option>
                                <option value="alert-warning" <?php if($colour == 'alert-warning' ){echo 'selected'; }?>>Yellow</option>
                                <option value="alert-info" <?php if($colour == 'alert-info' ){echo 'selected'; }?>>Teal</option>
                                <option value="alert-light" <?php if($colour == 'alert-light' ){echo 'selected'; }?>>Light Grey</option>
                                <option value="alert-dark" <?php if($colour == 'alert-dark' ){echo 'selected'; }?>>Black</option>
                            </select>
                        </div>
                        <div class="col-md mb-4">
                            <label for="eexpiryDate">Expiry Date</label>
                            <input type="text" class="form-control" id="eexpiryDate" name="eexpiryDate" style="width: 80%;" value="<?php echo $expiry ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger" name="delete_note" onclick="return confirm('Are you sure you want to delete this note?')">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="edit_note">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>