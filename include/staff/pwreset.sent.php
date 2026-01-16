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
	</a><br />
    <h3><?php echo __('A confirmation email has been sent'); ?></h3>
    <h3 style="color:black;"><em><?php echo __(
    'If the information provided is valid a password reset email will be sent to the email address you have on file. Follow the link in the email to reset your password.'
    ); ?>
    </em></h3>

    <form action="index.php" method="get">
        <input class="submit" type="submit" name="submit" value="Login"/>
    </form><br /><!--osta-->

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
