<?php if (!isset($_SERVER['HTTP_X_PJAX'])) { ?>
    </div>
    <div id="footer">
        Copyright &copy; 2006-<?php echo date('Y'); ?>&nbsp;<?php echo (string) $ost->company ?: 'osTicket.com'; ?>&nbsp;All Rights Reserved.
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
</div>
<div id="overlay"></div>
<div id="loading">
    <i class="icon-spinner icon-spin icon-3x pull-left icon-light"></i>
    <h1>Loading ...</h1>
</div>
<div class="dialog" style="display:none;width:650px;" id="popup">
    <div class="body"></div>
</div>
<script type="text/javascript">
if ($.support.pjax) {
  $(document).on('click', 'a', function(event) {
    if (!$(this).hasClass('no-pjax')
        && !$(this).closest('.no-pjax').length
        && $(this).attr('href')[0] != '#')
      $.pjax.click(event, {container: $('#pjax-container'), timeout: 2000});
  })
}
</script>
</body>
</html>
<?php } # endif X_PJAX ?>

