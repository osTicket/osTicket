<div class="faq-title flush-left"><?php echo $faq->getLocalQuestion() ?>
</div>

<div class="faded"><?php echo __('Last Updated');?>
    <?php echo Format::daydatetime($faq->getUpdateDate()); ?>
</div>

<br/>

<div class="thread-body bleed">
<?php echo $faq->getLocalAnswer(); ?>
</div>
