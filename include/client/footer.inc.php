        </div>
    </div>
    <!-- osta -->
    <div class="clear"></div>
    
<?php
	if ($cfg && $cfg->isKnowledgebaseEnabled()) { ?>
	<div id="pre-footer">
		<div id="pre-footer-inner" class="toggle">
			<div class="searchArea">
				<form id="footer-kb-search" method="get" action="kb/faq.php">
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
				<form id="footer-kb-kb-search" method="get" action="faq.php" style="display: none;">
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
		<div class="clear"></div>
	</div> 		
<?php
	}
		else
			echo  '';
		?>

	<div class="clear"></div>
	
	<?php show_consent_message($custom)?>
	
    <div id="footer"><!-- osta -->
		<?php include ROOT_DIR . 'osta/inc/client-foot.html'; ?>   
    </div>
	<!--osta-->
	<div id="overlay"></div>
	<div id="loading">
		<i class="icon-spinner icon-spin icon-3x pull-left icon-light"></i>
		<h1><?php echo __('Loading ...');?></h1>
	</div>
	<?php
	if (($lang = Internationalization::getCurrentLanguage()) && $lang != 'en_US') { ?>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>ajax.php/i18n/<?php
			echo $lang; ?>/js"></script>
	<?php } ?>
	<script type="text/javascript">
		getConfig().resolve(<?php
			include INCLUDE_DIR . 'ajax.config.php';
			$api = new ConfigAjaxAPI();
			print $api->client(false);
		?>);
	</script>
	<script type="text/javascript">
	  $(document).ready(function() {

		
		if (window.location.href.indexOf("/kb/") > -1) {
		  $( "form#footer-kb-search" ).hide();
		  $( "form#footer-kb-kb-search" ).show();
		} else {
		  $( "form#footer-kb-search" ).show();
		  $( "form#footer-kb-kb-search" ).hide();
		}		
		
		
	  });
	</script>
<?php include ROOT_DIR . 'osta/inc/back-button.html'; ?> 
<?php include ROOT_DIR . 'osta/inc/database-reset-warning.html'; ?>
<?php include ROOT_DIR . 'osta/inc/theme-button.html'; ?>    
</body>
</html>
