</div>
</div>
<?php if (!isset($_SERVER['HTTP_X_PJAX'])) { ?>
    <div id="footer">
        Copyright &copy; 2006-<?php echo date('Y'); ?>&nbsp;<?php echo (string) $ost->company ?: 'osTicket.com'; ?>&nbsp;All Rights Reserved.
    </div>
<?php
if(is_object($thisstaff) && $thisstaff->isStaff()) { ?>
    <div>
        <!-- Do not remove <img src="autocron.php" alt="" width="1" height="1" border="0" /> or your auto cron will cease to function -->
        <img src="<?php echo ROOT_PATH; ?>scp/autocron.php" alt="" width="1" height="1" border="0" />
        <!-- Do not remove <img src="autocron.php" alt="" width="1" height="1" border="0" /> or your auto cron will cease to function -->
    </div>
<?php
} ?>
</div>
<div id="overlay"></div>
<div id="loading">
    <i class="icon-spinner icon-spin icon-3x pull-left icon-light"></i>
    <h1><?php echo __('Loading ...');?></h1>
</div>
<div class="dialog draggable" style="display:none;" id="popup">
    <div id="popup-loading">
        <h1 style="margin-bottom: 20px;"><i class="icon-spinner icon-spin icon-large"></i>
        <?php echo __('Loading ...');?></h1>
    </div>
    <div class="body"></div>
</div>
<div style="display:none;" class="dialog" id="alert">
    <h3><i class="icon-warning-sign"></i> <span id="title"></span></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <div id="body" style="min-height: 20px;"></div>
    <hr style="margin-top:3em"/>
    <p class="full-width">
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('OK');?>" class="close ok">
        </span>
     </p>
    <div class="clear"></div>
</div>

<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.pjax.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/scp.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/tips.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/jquery.translatable.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/jquery.dropdown.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-tooltip.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/fabric.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/tooltip.css">
<script type="text/javascript">
    getConfig().resolve(<?php
        include INCLUDE_DIR . 'ajax.config.php';
        $api = new ConfigAjaxAPI();
        print $api->scp(false);
    ?>);
</script>
<?php
if ($thisstaff
        && ($lang = $thisstaff->getLanguage())
        && 0 !== strcasecmp($lang, 'en_US')) { ?>
    <script type="text/javascript" src="ajax.php/i18n/<?php
        echo $thisstaff->getLanguage(); ?>/js"></script>
<?php } ?>
</body>
</html>
<?php } # endif X_PJAX ?>
