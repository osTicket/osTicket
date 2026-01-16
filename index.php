<?php
/*********************************************************************
    index.php

    Helpdesk landing page. Please customize it to fit your needs.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('client.inc.php');

require_once INCLUDE_DIR . 'class.page.php';

$section = 'home';
require(CLIENTINC_DIR.'header.inc.php');
?>
<div id="landing_page">
<!-- osta -->
    <div class="clear"></div>


<?php
    if($cfg && ($page = $cfg->getLandingPage()))
        echo $page->getBodyWithImages();
    else
        echo  '<h1>'.__('Welcome to the Support Center').'</h1>';
    ?>
<!-- osta -->
</div>
<div class="clear"></div>
	
<div id="landing-search">	
	<div id="landing-search-inner">
		<div class="searchArea">
			<form method="get" action="kb/faq.php">
				<button type="submit" tabindex="2">
					<div class="client-choice-icon">
						<svg style="width:24px;height:24px" viewBox="0 0 24 24">
							<path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" />
						</svg>
					</div>				
				<?php echo __('Search'); ?></button>
				<div class="inputDiv">
					<input type="hidden" name="a" value="search"/>
					<input type="text" name="q" class="search" placeholder="<?php echo __('Search our knowledge base'); ?>"/>
				</div>
			</form>
		</div>				
	</div>
</div>
		
<div class="clear"></div>
		
<div id="open-or-check">
   
<div id="open-new" class="client-choice">
   
<div id="open-title">
   <?php echo __('Open a New Ticket');?>
</div><div id="open-text">
   <?php echo __('Please provide as much detail as possible so we can best assist you. To update a previously submitted ticket, please login.');?>
</div><a href="open.php"><div id="sign-in-button"><div class="client-choice-icon"><svg style="width:24px;height:24px" viewBox="0 0 24 24">
    <path d="M19.79,15.41C20.74,13.24 20.74,10.75 19.79,8.59L17.05,9.83C17.65,11.21 17.65,12.78 17.06,14.17L19.79,15.41M15.42,4.21C13.25,3.26 10.76,3.26 8.59,4.21L9.83,6.94C11.22,6.35 12.79,6.35 14.18,6.95L15.42,4.21M4.21,8.58C3.26,10.76 3.26,13.24 4.21,15.42L6.95,14.17C6.35,12.79 6.35,11.21 6.95,9.82L4.21,8.58M8.59,19.79C10.76,20.74 13.25,20.74 15.42,19.78L14.18,17.05C12.8,17.65 11.22,17.65 9.84,17.06L8.59,19.79M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8Z"></path>
</svg></div>
   <?php echo __('Open a New Ticket');?>
</div></a></div><div id="check-status" class="client-choice">
   
<div id="open-title"><?php echo __('Check Ticket Status');?>
</div><div id="open-text">
   <?php echo __('We provide archives and history of all your current and past support requests complete with responses.');?>
</div><a href="view.php"><div id="sign-in-button" href="view.php"><div class="client-choice-icon"><svg style="width:24px;height:24px" viewBox="0 0 24 24">
    <path d="M10.54,14.53L8.41,12.4L7.35,13.46L10.53,16.64L16.53,10.64L15.47,9.58L10.54,14.53M12,20A7,7 0 0,1 5,13A7,7 0 0,1 12,6A7,7 0 0,1 19,13A7,7 0 0,1 12,20M12,4A9,9 0 0,0 3,13A9,9 0 0,0 12,22A9,9 0 0,0 21,13A9,9 0 0,0 12,4M7.88,3.39L6.6,1.86L2,5.71L3.29,7.24L7.88,3.39M22,5.72L17.4,1.86L16.11,3.39L20.71,7.25L22,5.72Z"></path>
</svg></div><?php echo __('Check Ticket Status');?>
</div></a></div></div>
		
<div class="clear"></div>	
	
<!-- osta -->
	<div id="more-options">
		<div id="featured">
			<?php
				if($cfg && $cfg->isKnowledgebaseEnabled()){
					//FIXME: provide ability to feature or select random FAQs ??
				?>
			<!-- osta -->
			<?php
				$cats = Category::getFeatured();
				if ($cats->all()) { ?>
			<h1><?php echo __('Featured Knowledge Base Articles'); ?></h1>
			<?php
				}
					
					foreach ($cats as $C) { ?>
			<div class="featured-category front-page">
				<i class="icon-folder-open icon-2x"></i>
				<div class="category-name">
					<?php echo $C->getName(); ?>
				</div>
				<!-- osta --><div class="featured-articles">
				<?php foreach ($C->getTopArticles() as $F) { ?>

						<div class="article-title"><a href="<?php echo ROOT_PATH;
							?>kb/faq.php?id=<?php echo $F->getId(); ?>"><?php
							echo $F->getQuestion(); ?></a>
						<div class="article-teaser"><?php echo $F->getTeaser(); ?></div>
					</div>
				<?php } ?>
				</div>	
			<!-- osta --></div>
			<?php
				}
				}
				?>
		</div>
		<style>
			#featured-questions-columnized,
			#other-resources-columnized { }
			/*
				notice the div /inside/ the columnized columns
				that we'll use for padding
			*/
			.column div { padding-right: 20px; }
			.wide, .thin { clear:both; }
		</style>		
		<div class="clear"></div>
                <!-- osta -->		
<div id="information">

	<?php
		$faqs = FAQ::getFeatured()->select_related('category')->limit(3);
		if ($faqs->all()) { ?>
    <?php
        }
        $resources = Page::getActivePages()->filter(array('type'=>'other'));
        if ($resources->all()) { ?>
	<div id="other" class="info">
		<div class="info other-resources"><?php echo __('Other Resources'); ?></div>	
			<div id="other-resources-columnized">
				<section>

					<?php   foreach ($resources as $page) { ?>
					<div class="resource">
						<a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug();
							?>"><?php echo $page->getLocalName(); ?></a>
					</div>
					<!-- osta -->				
					<?php   } ?>
				</section>
		</div>
    <?php
        }
        	?>
	</div>
</div>

<script>
	function myFunction(x) {
	  if (x.matches) { // If media query matches
		$('selector').uncolumnize();
	  } else {
		$('#featured-questions-columnized').columnize({
			columns : 2,
			accuracy : 1,
			buildOnce : true
		})
		$('#other-resources-columnized').columnize({
			columns : 2,
			accuracy : 1,
			buildOnce : true
		})		
	  }
	}
	var x = window.matchMedia("(max-width: 600px)")
	myFunction(x) // Call listener function at run time
	x.addListener(myFunction) // Attach listener function on state changes	
	function isEmpty( el ){
	  return !$.trim(el.html())
	}
	if (isEmpty($('#other'))) {
	  $( "#information" ).hide();
	}
</script>  

	</div>
	<div class="clear"></div>
</div>
		
<div class="clear"></div>

<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>
