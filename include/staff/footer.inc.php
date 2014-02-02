    </div>
    <div id="footer">
        Copyright &copy; 2006-<?php echo date('Y'); ?>&nbsp;osTicket.com. &nbsp;All Rights Reserved.
    </div>
<?php
if(is_object($thisstaff) && $thisstaff->isStaff()) { ?>
    <div>
        <!-- Do not remove <img src="autocron.php" alt="" width="1" height="1" border="0" /> or your auto cron will cease to function -->
        <img src="autocron.php" alt="" width="1" height="1" border="0" />
        <!-- Do not remove <img src="autocron.php" alt="" width="1" height="1" border="0" /> or your auto cron will cease to function -->
    </div>
<?php
} ?>
</div>
<div id="overlay"></div>
<div id="loading">
    <h4>Please Wait!</h4>
    <p>Please wait... it will take a second!</p>
</div>
<div class="dialog" style="display:none;width:650px;" id="popup">
    <div class="body"></div>
</div>
    <script type="text/javascript" src="../js/jquery.multifile.js"></script>
    <script type="text/javascript" src="./js/tips.js"></script>
    <script type="text/javascript" src="./js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-fonts.js"></script>
<?php
if ($thisstaff && $thisstaff->getLanguage() != 'en_US') { ?>
    <script type="text/javascript" src="ajax.php/i18n/<?php
        echo $thisstaff->getLanguage(); ?>/redactor"></script>
<?php } ?>
</body>
</html>
