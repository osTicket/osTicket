<h3><?php echo __('Edit Thread Entry'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>

<form method="post" action="<?php echo $this->getAjaxUrl(true); ?>">

<input type="text" style="width:100%;font-size:14px" placeholder="<?php
    echo __('Title'); ?>" name="title" value="<?php
    echo Format::htmlchars($this->entry->title); ?>"/>
<hr style="height:0"/>
<textarea style="display: block; width: 100%; height: auto; min-height: 150px;"
<?php if ($this->entry->type == 'R') {
    $signature = '';
    if (($T = $this->entry->getThread()->getObject()) instanceof Ticket)
        $dept = $T->getDept();
    switch ($thisstaff->getDefaultSignatureType()) {
    case 'dept':
        if ($dept && $dept->canAppendSignature())
           $signature = $dept->getSignature();
       break;
    case 'mine':
        $signature = $thisstaff->getSignature();
        break;
    } ?>
    data-dept-id="<?php echo $dept->getId(); ?>"
    data-signature-field="signature"
    data-signature="<?php echo Format::viewableImages($signature); ?>"
<?php } ?>
    name="body"
    class="large <?php
        if ($cfg->isHtmlThreadEnabled() && $this->entry->format == 'html')
            echo 'richtext';
    ?>"><?php echo Format::viewableImages($this->entry->body);
?></textarea>

<?php if ($this->entry->type == 'R') { ?>
<div style="margin:10px 0;"><?php echo __('Signature'); ?>:
    <label><input type="radio" name="signature" value="none" checked="checked"> <?php echo __('None');?></label>
    <?php
    if ($thisstaff->getSignature()) {?>
    <label><input type="radio" name="signature" value="mine"
        <?php echo ($info['signature']=='mine')?'checked="checked"':''; ?>> <?php echo __('My Signature');?></label>
    <?php
    } ?>
    <?php
    if ($dept && $dept->canAppendSignature()) { ?>
    <label><input type="radio" name="signature" value="dept"
        <?php echo ($info['signature']=='dept')?'checked="checked"':''; ?>>
        <?php echo sprintf(__('Department Signature (%s)'), Format::htmlchars($dept->getName())); ?></label>
    <?php
    } ?>
</div>
<?php } # end of type == 'R' ?>

<hr>
<div class="full-width">
    <span class="buttons pull-left">
        <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
    </span>
    <span class="buttons pull-right">
        <button type="submit" name="commit" value="save" class="button"
            ><?php echo __('Save'); ?></button>
<?php if ($this->entry->type == 'R') { ?>
        <button type="submit" name="commit" value="resend" class="button"
            ><?php echo __('Save and Resend'); ?></button>
<?php } ?>
    </span>
</div>

</form>
