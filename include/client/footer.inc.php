        </div>
    </div>
    <div id="footer"> 
        <div id=company>
			<div class="content"> 
				Copyright &copy; <?php echo date('Y'); ?> <?php echo (string) $ost->company ?: 'osTicket.com'; ?> - All rights reserved.
				<!--<a id="poweredBy" href="http://osticket.com" target="_blank"><?php echo __('Helpdesk software - powered by osTicket'); ?></a>-->
			</div>	
		</div> 
    </div>
	
	<div id="poweredBy"><?php echo __('Powered by'); ?>
    <a href='http://www.osticket.com' target="_blank"><img alt="osTicket" src="/scp/images/osticket-grey.png"
        class="osticket-logo"></a>
</div>
<div id="overlay"></div>
<div id="loading">
    <h4><?php echo __('Please Wait!');?></h4>
    <p><?php echo __('Please wait... it will take a second!');?></p>
</div>
<?php
if (($lang = Internationalization::getCurrentLanguage()) && $lang != 'en_US') { ?>
    <script type="text/javascript" src="ajax.php/i18n/<?php
        echo $lang; ?>/js"></script>
<?php } ?>
<script type="text/javascript">
    getConfig().resolve(<?php
        include INCLUDE_DIR . 'ajax.config.php';
        $api = new ConfigAjaxAPI();
        print $api->client(false);
    ?>);
</script>
<!--Bootstrap loading via CDN until we can load assets during packaging-->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
</body>
</html>
