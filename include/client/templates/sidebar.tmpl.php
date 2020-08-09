<?php
$BUTTONS = isset($BUTTONS) ? $BUTTONS : true;
?>
    <div class="container">
		<?php if ($BUTTONS) { ?>
      <div class="row align-items-start">
      <!-- "front-page-button flush-right" -->
			
			<?php
    		if ($cfg->getClientRegistrationMode() != 'disabled'
      	  || !$cfg->isClientLoginRequired()) { ?>
      	<div class="col-md-6" style="padding-top: 1rem; padding-bottom: 1rem;">
            <a href="open.php" style="display:block" class="btn btn-md btn-outline-info">
				<i class="fa fa-plus " style=" font-size: 1rem;"></i>            
            <?php
                echo __('Open a New Ticket');?></a>
			</div>
			<?php } ?>
			<div class="col-md-6" style="padding-top: 1rem; padding-bottom: 1rem;">
         	<a href="view.php" style="display:block" class="btn btn-md btn-outline-success">
         	<i class="fa fa-search " style=" font-size: 1rem;"></i>  
         	<?php
            	echo __('Check Ticket Status');?></a>
			</div>
		</div>
		<?php } ?>
      <div class="content"><?php
    		if ($cfg->isKnowledgebaseEnabled()
        	&& ($faqs = FAQ::getFeatured()->select_related('category')->limit(5))
        	&& $faqs->all()) { ?>
         	<section><div class="header"><?php echo __('Featured Questions'); ?></div>
					<?php   foreach ($faqs as $F) { ?>
            		<div><a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php
               	 	echo urlencode($F->getId());
               	 	?>"><?php echo $F->getLocalQuestion(); ?></a></div>
					<?php   } ?>
            </section>
				<?php
    		}
    		$resources = Page::getActivePages()->filter(array('type'=>'other'));
    		if ($resources->all()) { ?>
         	<section><div class="header"><?php echo __('Other Resources'); ?></div>
					<?php   foreach ($resources as $page) { ?>
            		<div>
            			<a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug();
            			?>"><?php echo $page->getLocalName(); ?></a>
            		</div>
					<?php   } ?>
            </section>
			<?php
    		}
        ?>
		</div>
    </div>

