<?php
include_once(INCLUDE_DIR.'staff/login.header.php');
// osta
$info = ($_POST && $errors)?Format::htmlchars($_POST):array();
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH . "/osta/php/functions.php"; 
$opt = get_config();
?>
<div id="brickwall"></div>
<div id="loginBox">
    <div id="blur">
        <div id="background"></div>
    </div>
<!--osta-->
	<a id="header-logo" href="<?php echo ROOT_PATH; ?>scp/">
	<div id="login-title">

		<div id="header-text">
			<div id="header-title">
				<?php				
				$file_name = ROOT_DIR ."osta/opt/text/title.txt";
				echo file_get_contents($file_name);
				?>     
			</div>
		</div>
		
		<div id="header-image">
			<img src="<?php echo get_logo( $opt, "staff" )?>?<?php echo strtotime($cfg->lastModified('staff_logo_id')); ?>" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/> 
		</div>	

		<div id="header-default">
			<?php				
			$file_name = ROOT_DIR ."osta/inc/default-logo.html";
			echo file_get_contents($file_name);
			?>		
		</div>			

	</div>
	</a>

    <h3><?php echo Format::htmlchars($msg); ?></h3>
    <form action="pwreset.php" method="post">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="sendmail">
        <fieldset>
            <input type="text" name="userid" id="name" value="<?php echo
            $info['userid']; ?>" placeholder="<?php echo __('Email or Username'); ?>" autocorrect="off"
                autocapitalize="off">
        </fieldset>
        <input class="submit" type="submit" name="submit" value="<?php echo __('Send Email'); ?>"/>
    </form>

    <div id="company">
        <div class="content">
            <?php echo __('Copyright'); ?> &copy; <?php echo Format::htmlchars($ost->company) ?: date('Y'); ?>
        </div>
    </div>
</div>
<div id="poweredBy"><?php echo __('Powered by'); ?>
    <a href="http://www.osticket.com" target="_blank">
        <img alt="osTicket" src="images/osticket-grey.png" class="osticket-logo">
    </a>
</div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (undefined === window.getComputedStyle(document.documentElement).backgroundBlendMode) {
            document.getElementById('loginBox').style.backgroundColor = 'white';
        }
    });
    </script>
</body>
</html>
