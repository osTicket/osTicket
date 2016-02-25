        </div>
    </div>
    <div id="footer">
        <p><a href="<?php 
            $dFooterURLStr = db_fetch_array(db_query('SELECT value FROM '.FORM_ANSWER_TABLE.' WHERE field_id=24'))["value"] ;
            if (empty(parse_url($dFooterURLStr)['scheme'])) {
                $dFooterURLStr = 'http://' . ltrim($dFooterURLStr, '/');
            }
            echo $dFooterURLStr ; 
            ?>" target="_blank">Copyright &copy; <?php echo date('Y'); ?> <?php echo (string) $ost->company ?: 'osTicket.com'; ?> - All rights reserved.</a></p>
        <a href="http://osticket.com" target="_blank"><?php echo __('Helpdesk software - powered by osTicket'); ?></a>
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
</body>
</html>
