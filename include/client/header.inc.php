<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
header("Content-Security-Policy: frame-ancestors ".$cfg->getAllowIframes()."; script-src 'self' 'unsafe-inline'; object-src 'none'");

if (($lang = Internationalization::getCurrentLanguage())) {
    $langs = array_unique(array($lang, $cfg->getPrimaryLanguage()));
    $langs = Internationalization::rfc1766($langs);
    header("Content-Language: ".implode(', ', $langs));
}
// osta
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH . "/osta/php/functions.php"; 
$opt = get_config();
?>
<!DOCTYPE html>
<html<?php
if ($lang
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo ' dir="rtl" class="rtl"';
if ($lang) {
    echo ' lang="' . $lang . '"';
}

// Dropped IE Support Warning
if (osTicket::is_ie())
    $ost->setWarning(__('osTicket no longer supports Internet Explorer.'));
?>>
<head>
    <!-- Powered by osTicket -->
    <!-- Supercharged by osTicket Awesome -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo Format::htmlchars($title); ?></title>
    <meta name="description" content="customer support platform">
    <meta name="keywords" content="osTicket, Customer support system, support ticket system">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/osticket.css?0375576" media="screen"/>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/theme.css?0375576" media="screen"/>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/print.css?0375576" media="print"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/typeahead.css?0375576"
         media="screen" />
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.13.2.custom.min.css?0375576"
        rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/jquery-ui-timepicker-addon.css?0375576" media="all"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/thread.css?0375576" media="screen"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css?0375576" media="screen"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css?0375576"/>
	<!--osta-->
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-3.7.0.min.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.13.2.custom.min.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-timepicker-addon.js?0375576"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js?0375576"></script>
    <script src="<?php echo ROOT_PATH; ?>js/bootstrap-typeahead.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js?0375576"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js?0375576"></script>
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }

    // Offer alternate links for search engines
    // @see https://support.google.com/webmasters/answer/189077?hl=en
    if (($all_langs = Internationalization::getConfiguredSystemLanguages())
        && (count($all_langs) > 1)
    ) {
        $langs = Internationalization::rfc1766(array_keys($all_langs));
        $qs = array();
        parse_str($_SERVER['QUERY_STRING'], $qs);
        foreach ($langs as $L) {
            $qs['lang'] = $L; ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI']); ?>?<?php
            echo http_build_query($qs); ?>" hreflang="<?php echo $L; ?>" />
<?php
        } ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI']); ?>"
            hreflang="x-default" />
<?php
    }
    ?>
<!--osta-->
<?php include ROOT_DIR . 'osta/inc/client-head.html'; ?>    
</head>
<!--osta-->
<body class="client-side <?php $phpSelf = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_URL); echo basename(substr($phpSelf, 0, strpos($phpSelf, '.php')));   ?>-page">
    <div id="container">
        <?php
        if($ost->getError())
            echo sprintf('<div class="error_bar">%s</div>', $ost->getError());
        elseif($ost->getWarning())
            echo sprintf('<div class="warning_bar">%s</div>', $ost->getWarning());
        elseif($ost->getNotice())
            echo sprintf('<div class="notice_bar">%s</div>', $ost->getNotice());
        ?>
        <div id="header">
                <!--osta-->
        	<div id="header-inner">

				<div class="pull-right flush-right">
				<p>
				 <?php
					if ($thisclient && is_object($thisclient) && $thisclient->isValid()
                    && !$thisclient->isGuest()) {
                 echo Format::htmlchars($thisclient->getName()).'&nbsp;|';
					 ?>
                <a href="<?php echo ROOT_PATH; ?>profile.php"><?php echo __('Profile'); ?></a> |
                <a href="<?php echo ROOT_PATH; ?>tickets.php"><?php echo sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()); ?></a> -
					<a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a>
				<?php
				} elseif($nav) {
					if ($cfg->getClientRegistrationMode() == 'public') { ?>
                                                <!--osta-->
						<?php echo __('Sign In').','; ?>
						<?php echo __('Guest User'); ?> <?php
					}
					if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
						<a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a><?php
					}
					elseif ($cfg->getClientRegistrationMode() != 'disabled') { ?>
						<a href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a>
	<?php
					}
				} ?>
				</p>
<!--osta-->
				</div>
       
		<a id="header-logo" href="<?php echo ROOT_PATH; ?>">
        <div id="left-logo">
		
		<?php 
			require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH . "/osta/php/functions.php"; 

			$custom = get_config() ;
		?>		
		
            <div id="header-text">
				<div id="header-title">
					<?php echo $custom["title"]; ?>   
				</div>
				<div id="header-subtitle">
					<?php echo $custom["subtitle"]; ?>      
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
				
				
				<div id="right-menu" href="#right-menu">
				<!--osta-->
					<button href="#right-menu" class="c-hamburger c-hamburger--htx" style="">
						<span>toggle menu</span>
					</button>
					<script>
						$(document).ready(function() {

							"use strict";

							var toggles = document.querySelectorAll(".c-hamburger");

							for (var i = toggles.length - 1; i >= 0; i--) {
								var toggle = toggles[i];
								toggleHandler(toggle);
							};

							function toggleHandler(toggle) {
								toggle.addEventListener( "click", function(e) {
									e.preventDefault();
									(this.classList.contains("is-active") === true) ? this.classList.remove("is-active") : this.classList.add("is-active");
								});
								toggle.addEventListener( "touchstart", function(e) {
									e.preventDefault();
									(this.classList.contains("is-active") === true) ? this.classList.remove("is-active") : this.classList.add("is-active");
								});	  
							}

							$('.c-hamburger').sidr({
								name: 'sidr-right',
								side: 'right',
								body: '#content',
								displace: false
							});
						});
					</script>
				</div>
			</div>		
			<div id="sidr-right" class="sidr right">
				<div class="sidr-inner">
				<!--osta-->

					<ul id="nav-mobile" class="flush-left">
						<li><a href="<?php echo ROOT_PATH; ?>"><?php echo __('Support Center Home'); ?></a></li>
				 <?php
						if($cfg && $cfg->isKnowledgebaseEnabled())  { ?>
						<li><a class="active kb" href="<?php echo ROOT_PATH; ?>kb/index.php"><?php echo __('Knowledgebase') ?></a></li>
				 <?php } ?>
						<li><a href="<?php echo ROOT_PATH; ?>open.php"><?php echo __('Open a New Ticket'); ?></a></li>
						<li><a href="<?php echo ROOT_PATH; ?>view.php"><?php echo __('Check Ticket Status'); ?></a></li>	
				 <?php
						if ($thisclient && is_object($thisclient) && $thisclient->isValid()
							&& !$thisclient->isGuest()) {
						echo '<div id="welcome"><svg style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>&nbsp;'.Format::htmlchars($thisclient->getName()).'</div>';
						 ?>
						<li><a href="<?php echo ROOT_PATH; ?>profile.php"><?php echo __('Profile'); ?></a></li>
						<li><a href="<?php echo ROOT_PATH; ?>tickets.php"><?php echo sprintf(__('Tickets (%d)'), $thisclient->getNumTickets()); ?></a></li>
						<li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li>
				<?php
				} elseif($nav) {
					if ($cfg->getClientRegistrationMode() == 'public') { ?>
						<div id="welcome"><svg style="width:18px;height:18px" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>&nbsp;<?php echo __('Guest User'); ?></div>  <?php
					}
					if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
						<li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li><?php
					}
					elseif ($cfg->getClientRegistrationMode() != 'disabled') { ?>
						<li><a href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a></li>
						
						
				<div id="flags-mobile">
					<?php
					if (($all_langs = Internationalization::getConfiguredSystemLanguages())
						&& (count($all_langs) > 1)
) {
						$qs = array();
						parse_str($_SERVER['QUERY_STRING'], $qs);
						foreach ($all_langs as $code=>$info) {
							list($lang, $locale) = explode('_', $code);
							$qs['lang'] = $code;
?>
						<a class="flag flag-<?php echo strtolower($info['flag'] ?: $locale ?: $lang); ?>"
												href="?<?php echo http_build_query($qs);
												?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a>
<!--osta-->
					<?php }
					} ?>
				</div>					
							
						
				<?php
					}
				} ?>
						<li id="contact-id">
							<a href="
								<?php echo $custom["mobile-link"]; ?> 	
							">
								<?php echo $custom["mobile-text"]; ?>   
							</a>
						</li>
					</ul>

				</div>
			</div>				
				

			</div>    
        </div>
        <div class="clear"></div>
        <?php
        if($nav){ ?>
<!--osta-->
       <div id="nav-wrapper">
			<div id="nav-inner">

				<ul id="nav" class="flush-left">
					<?php
					if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
						foreach($navs as $name =>$nav) {
							echo sprintf('<li><a class="%s %s" href="%s">%s</a></li>%s',$nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
						}
					} ?>
				</ul>
<!--osta-->		
<div id="lang-wrapper">			
<div class="button-container">
    <div class="button-text-container shrink" style="width:?">
				<div id="flags">
					<?php
					if (($all_langs = Internationalization::getConfiguredSystemLanguages())
						&& (count($all_langs) > 1)
					) {
						$qs = array();
						parse_str($_SERVER['QUERY_STRING'], $qs);
						foreach ($all_langs as $code=>$info) {
							list($lang, $locale) = explode('_', $code);
							$qs['lang'] = $code;
					?>
							<a class="flag flag-<?php echo strtolower($locale ?: $info['flag'] ?: $lang); ?>"
								href="?<?php echo http_build_query($qs);
								?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a>
					<?php }
					} ?>
				</div> 
    </div>
    <div class="button-icon-container" id="myButton">
        <svg style="width:24px;height:24px" viewBox="0 0 24 24">
            <path fill="#ffffff" d="M5.59,7.41L7,6L13,12L7,18L5.59,16.59L10.17,12L5.59,7.41M11.59,7.41L13,6L19,12L13,18L11.59,16.59L16.17,12L11.59,7.41Z" />
        </svg>
    </div>
</div>
</div>	
				
				
				<div id="language-select">
				</div>
			</div>
		</div>
        <?php
        }else{ ?>
         <hr>
        <?php
        } ?>
        <div id="content">

         <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
         <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
         <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
         <?php } ?>
