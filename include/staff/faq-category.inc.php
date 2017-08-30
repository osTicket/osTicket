 <?php
if(!defined('OSTSTAFFINC') || !$category || !$thisstaff) die('Access Denied');


$view = $category->isPublic()?__('Public'):__('Internal');
?>
<div class="subnav">

    <div class="float-left subnavtitle">
                          
    <?php echo __('Frequently Asked Questions') .' / <strong>'. $category->getName().'</strong> ('.$view.') - '.__('Last Updated').' '. Format::datetime($category->getUpdateDate());; ?>                      
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
   <?php if ($thisstaff->hasPerm(FAQ::PERM_MANAGE)) {  ?>
        <a href="faq.php?cid=<?php echo $category->getId();?>&a=add" class="btn btn-icon waves-effect waves-light btn-success">
                    <i class="fa fa-plus-square" data-placement="bottom"
        data-toggle="tooltip" title=" <?php echo __( 'Add New Faq');?>"></i></a>
      
        <div class="btn-group btn-group-sm" role="group">
            <button id="btnGroupDrop1" type="button" class="btn btn-light  waves-effect  btn-nbg dropdown-toggle" 
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-cog" data-placement="bottom" data-toggle="tooltip" 
             title="<?php echo __('More'); ?>"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right " aria-labelledby="btnGroupDrop1" id="action-dropdown-change-priority">
            
                <a class="dropdown-item user-action" href="categories.php?id=<?php echo $category->getId();?>"><i class="icon-pencil icon-fixed-width"></i> <?php echo __('Edit Category');?></a>
                
           
      
            </div>
        </div>
       <?php } else {
        ?>
        &nbsp;
        <?php
        } ?>
        
        <a href="categories.php" class="btn btn-icon waves-effect waves-light btn-light">
                    <i class="fa fa-list-alt" data-placement="bottom"
        data-toggle="tooltip" title=" <?php echo __( 'Categories');?>"></i></a>
        
      </div>   
   <div class="clearfix"></div> 
</div> 


<div class="card-box">

<div class="row">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

<h4 class="m-t-0 header-title"><?php echo Format::display($category->getDescription()); ?></h4>

<?php


$faqs = $category->faqs
    ->constrain(array('attachments__inline' => 0))
    ->annotate(array('attachments' => SqlAggregate::COUNT('attachments')));
if ($faqs->exists(true)) {
    echo '<div id="faq">
            <ol>';
    foreach ($faqs as $faq) {
        echo sprintf('
            <li><strong><a href="faq.php?id=%d" class="previewfaq">%s <span>- %s</span></a> %s</strong></li>',
            $faq->getId(),$faq->getQuestion(),$faq->isPublished() ? __('Published'):__('Internal'),
            $faq->attachments ? '<i class="icon-paperclip"></i>' : ''
        );
    }
    echo '  </ol>
         </div>';
}else {
    echo '<strong>'.__('Category does not have FAQs').'</strong>';
}
?>
</div>
</div>
</div>
</div>