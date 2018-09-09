        </div>
    </div>
    <div id="footer">
        <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo (string) $ost->company ?: 'osTicket.com'; ?> - All rights reserved.</p>
        <a id="poweredBy" href="http://osticket.com" target="_blank"><?php echo __('Helpdesk software - powered by osTicket'); ?></a>
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
<script type="text/javascript" data-group="client" src="//code.jquery.com/ui/1.10.4/jquery-ui.min.js"></script>
<script data-group="client" src="<?php echo ROOT_PATH; ?>js/osticket.js"></script>
<script type="text/javascript" data-group="client" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
<script data-group="client" src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
<script type="text/javascript" data-group="client" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
<script type="text/javascript" data-group="client" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js"></script>
<script type="text/javascript" data-group="client" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
<script type="text/javascript" data-group="client" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
<script type="text/javascript" data-group="client" src="//cdnjs.cloudflare.com/ajax/libs/fabric.js/1.7.2/fabric.min.js"></script>
<!-- {#} JS -->

<script type="text/javascript">
    getConfig().resolve(<?php
        include INCLUDE_DIR . 'ajax.config.php';
        $api = new ConfigAjaxAPI();
        print $api->client(false);
    ?>);
</script>
</body>
</html>
