        </div>
    </div>
    <div id="footer">
        <p>Copyright &copy; <?php echo date('Y'); ?> <a href="http://osticket.com" target="_blank" title="osTicket">osTicket.com</a> - All rights reserved.</p>
        <a id="poweredBy" href="http://osticket.com" target="_blank">Powered by osTicket</a>
    </div>
<div id="overlay"></div>
<div id="loading">
    <h4>Please Wait!</h4>
    <p>Please wait... it will take a second!</p>
</div>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
<?php
if ($cfg && $cfg->getSystemLanguage() != 'en_US') { ?>
    <script type="text/javascript" src="ajax.php/i18n/<?php
        echo $cfg->getSystemLanguage(); ?>/redactor"></script>
<?php } ?>
</body>
</html>
