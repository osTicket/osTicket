</div>
</div>
<div id="footer">
    <p>Copyright &copy; <?= date('Y'); ?> <?= (string) $ost->company ? : 'osTicket.com'; ?> - All rights reserved.</p>
    <a id="poweredBy" href="http://osticket.com" target="_blank"><?= __('Helpdesk software - powered by osTicket'); ?></a>
</div>
<div id="overlay"></div>
<div id="loading">
    <h4><?= __('Please Wait!'); ?></h4>
    <p><?= __('Please wait... it will take a second!'); ?></p>
</div>
<?php if (($lang = Internationalization::getCurrentLanguage()) && $lang != 'en_US') : ?>
    <script type="text/javascript" src="ajax.php/i18n/<?= $lang; ?>/js"></script>
<?php endif; ?>
</body>
</html>
