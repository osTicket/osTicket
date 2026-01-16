<?php
header("Content-Type: text/html; charset=UTF-8");
header("Content-Security-Policy: frame-ancestors ".$cfg->getAllowIframes()."; script-src 'self' 'unsafe-inline' 'unsafe-eval'; object-src 'none'");

$title = ($ost && ($title=$ost->getPageTitle()))
    ? $title : ('osTicket :: '.__('Staff Control Panel'));

if (!isset($_SERVER['HTTP_X_PJAX'])) { ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html<?php
if (($lang = Internationalization::getCurrentLanguage())
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo ' dir="rtl" class="rtl"';
if ($lang) {
    echo ' lang="' . Internationalization::rfc1766($lang) . '"';
}

// Dropped IE Support Warning
if (osTicket::is_ie())
    $ost->setWarning(__('osTicket no longer supports Internet Explorer.'));
?>>
<head>
    <!-- Powered by osTicket -->
    <!-- Supercharged by osTicket Awesome -->	
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="x-pjax-version" content="<?php echo GIT_VERSION; ?>">
    <title><?php echo Format::htmlchars($title); ?></title>
    <!--[if IE]>
    <style type="text/css">
        .tip_shadow { display:block !important; }
    </style>
    <![endif]-->
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-3.7.0.min.js?0375576"></script>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/thread.css?0375576" media="all"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/scp.css?0375576" media="all"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css?0375576" media="screen"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/typeahead.css?0375576" media="screen"/>
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.13.2.custom.min.css?0375576"
         rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/jquery-ui-timepicker-addon.css?0375576" media="all"/>
     <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css?0375576"/>
    <!--[if IE 7]>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome-ie7.min.css?0375576"/>
    <![endif]-->
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/dropdown.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/loadingbar.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css?0375576"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/translatable.css?0375576"/>
<!--osta-->
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
	<?php include ROOT_DIR . '/osta/inc/staff-head.html'; ?>
    <?php
    if ( $thisstaff->getExtraAttr('dark_mode') == "on"  ) { 
        ?>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>osta/css/dark-mode.css"/>
        <?php
    }
    ?>
	<!--osta-->
</head>
<!--osta-->
<body class="staff-side <?php echo $thisstaff->getExtraAttr('dark_mode') == "on" ? "dark-mode " : ""?><?php $phpSelf = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_URL); echo basename(substr($phpSelf, 0, strpos($phpSelf, '.php')));  ?>-page">
<div id="container">
    <?php if($ost->getError()) echo sprintf('
    <div id="error_bar">%s</div>', $ost->getError()); elseif($ost->getWarning()) echo sprintf('
    <div id="warning_bar"><div id="warning-inner">%s</div></div>', $ost->getWarning()); elseif($ost->getNotice()) echo sprintf('
    <div id="notice_bar">%s</div>', $ost->getNotice()); ?>
    
    
    
    <div id="header">
    <div id="header-constrain">
        <div id="nav" class="pull-right pjax">
            <!--<?php echo sprintf(__('Welcome, %s.'), '<strong>'.$thisstaff->getFirstName().'</strong>'); ?>-->
			
			<a id="home-link" href="<?php echo ROOT_PATH; ?>scp/">
				<svg style="width:24px;height:24px" viewBox="0 0 24 24">
					<path d="M12 5.69L17 10.19V18H15V12H9V18H7V10.19L12 5.69M12 3L2 12H5V20H11V14H13V20H19V12H22" />
				</svg>
			</a>			

            <?php include STAFFINC_DIR . "templates/navigation.tmpl.php"; ?>

            <?php if($thisstaff->isAdmin() && !defined('ADMINPAGE')) { ?> 
            <a id="admin-panel" href="<?php echo ROOT_PATH ?>scp/admin.php" class="no-pjax">
                <?php echo __( 'Admin Panel'); ?>
            </a>
            <?php }else{ ?> 
            <a id="agent-panel" href="<?php echo ROOT_PATH ?>scp/index.php" class="no-pjax">
                <?php echo __( 'Agent Panel'); ?>
            </a>
            <?php } ?> 

			
			<a id="dark-mode-link" href="<?php echo ROOT_PATH ?>scp/profile.php#dark-mode-tab" class="no-pjax">
				<svg class="no-select" viewBox="0 0 16 16" version="1.1" width="16" height="16">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M4.52208 7.71754C7.5782 7.71754 10.0557 5.24006 10.0557 2.18394C10.0557 1.93498 10.0392 1.68986 10.0074 1.44961C9.95801 1.07727 10.3495 0.771159 10.6474 0.99992C12.1153 2.12716 13.0615 3.89999 13.0615 5.89383C13.0615 9.29958 10.3006 12.0605 6.89485 12.0605C3.95334 12.0605 1.49286 10.001 0.876728 7.24527C0.794841 6.87902 1.23668 6.65289 1.55321 6.85451C2.41106 7.40095 3.4296 7.71754 4.52208 7.71754Z">
					</path>
				</svg>
			</a>
			
            <a id="profile-link" href="<?php echo ROOT_PATH ?>scp/profile.php">
				<svg style="width:24px;height:24px" viewBox="0 0 24 24">
					<path fill="currentColor" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M7.07,18.28C7.5,17.38 10.12,16.5 12,16.5C13.88,16.5 16.5,17.38 16.93,18.28C15.57,19.36 13.86,20 12,20C10.14,20 8.43,19.36 7.07,18.28M18.36,16.83C16.93,15.09 13.46,14.5 12,14.5C10.54,14.5 7.07,15.09 5.64,16.83C4.62,15.5 4,13.82 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,13.82 19.38,15.5 18.36,16.83M12,6C10.06,6 8.5,7.56 8.5,9.5C8.5,11.44 10.06,13 12,13C13.94,13 15.5,11.44 15.5,9.5C15.5,7.56 13.94,6 12,6M12,11A1.5,1.5 0 0,1 10.5,9.5A1.5,1.5 0 0,1 12,8A1.5,1.5 0 0,1 13.5,9.5A1.5,1.5 0 0,1 12,11Z" />
				</svg>
            </a>			

            <span data-placement="bottom" data-toggle="tooltip" title="" data-original-title="logout">          
            <a id="logout" href="<?php echo ROOT_PATH ?>scp/logout.php?auth=<?php echo $ost->getLinkToken(); ?>" class="no-pjax">
			<svg id="log-out-svg" style="width:24px;height:24px" viewBox="0 0 24 24">
				<path fill="currentColor" d="M14.08,15.59L16.67,13H7V11H16.67L14.08,8.41L15.5,7L20.5,12L15.5,17L14.08,15.59M19,3A2,2 0 0,1 21,5V9.67L19,7.67V5H5V19H19V16.33L21,14.33V19A2,2 0 0,1 19,21H5C3.89,21 3,20.1 3,19V5C3,3.89 3.89,3 5,3H19Z" />
			</svg>
       		</a>
       	    </span>
        </div>

		<a id="header-logo" href="<?php echo ROOT_PATH; ?>scp/">
        <div id="left-logo">
			<div id="header-text">
				<div id="header-title">
					<?php echo $custom["title"]; ?>   
				</div>
				<div id="header-subtitle">
					<?php echo $custom["subtitle"]; ?>      
				</div>
			</div>		
			
            <div id="header-image">
				<img src="<?php echo get_logo( $custom, "staff" )?>?<?php echo strtotime($cfg->lastModified('staff_logo_id')); ?>" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/> 
            </div>	

            <div id="header-default">
				<?php				
				$file_name = ROOT_DIR ."osta/inc/default-logo.html";
				echo file_get_contents($file_name);
				?>
            </div>			
			
        </div>
		</a>
		
            <div id="right-buttons">
                <a class="mobile-nav" href="<?php echo ROOT_PATH; ?>scp/tickets.php?status=open">
                    <svg style="width:24px;height:24px; padding: 18px;float:right;margin-right:1px;" viewBox="0 0 24 24">
                        <path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z"></path>
                    </svg>
                </a>
                <a class="mobile-nav" href="<?php echo ROOT_PATH; ?>scp/users.php">
                    <svg style="width:24px;height:24px; padding: 18px;float:right;margin-right:1px;" viewBox="0 0 24 24">
                        <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"></path>
                    </svg>
                </a>
                <a class="mobile-nav" href="<?php echo ROOT_PATH; ?>scp/tickets.php?a=open">
                    <svg style="width: 30px; height: 30px; padding: 15px 20px 15px 12px; float: right;" viewBox="0 0 24 24">
                        <path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"></path>
                    </svg>
                </a>
            </div>
    	</div>
		</div>
            <div id="right-menu" href="#right-menu">
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
                            toggle.addEventListener("click", function(e) { 
                                e.preventDefault(); 
                                (this.classList.contains("is-active") === true) ? this.classList.remove("is-active"): this.classList.add("is-active"); 
                            }); 
                            toggle.addEventListener("touchstart", function(e) { 
                                e.preventDefault(); 
                                (this.classList.contains("is-active") === true) ? this.classList.remove("is-active"): this.classList.add("is-active"); 
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

        <div id="sidr-right" class="sidr right">
            <?php include ROOT_DIR . 'osta/inc/staff-mobile-menu.html'; ?>
        </div>    	
    <!-- END Header -->



    <div id="pjax-container" class="<?php if ($_POST) echo 'no-pjax'; ?>">
<?php } else {
    header('X-PJAX-Version: ' . GIT_VERSION);
    if ($pjax = $ost->getExtraPjax()) { ?>
    <script type="text/javascript">
    <?php foreach (array_filter($pjax) as $s) echo $s.";"; ?>
    </script>
    <?php }
    foreach ($ost->getExtraHeaders() as $h) {
        if (strpos($h, '<script ') !== false)
            echo $h;
    } ?>
    <title><?php echo ($ost && ($title=$ost->getPageTitle()))?$title:'osTicket :: '.__('Staff Control Panel'); ?></title><?php
} # endif X_PJAX ?>
<!--osta-->
   
<!--    <ul id="nav">
<?php include STAFFINC_DIR . "templates/navigation.tmpl.php"; ?>
    </ul>-->
   
    <div id="sub_nav-wrap">
		<ul id="sub_nav">
			<?php include STAFFINC_DIR . "templates/sub-navigation.tmpl.php"; ?>
		</ul>
	</div>
    <div id="content" class="<?php echo basename($_SERVER['PHP_SELF'], '.php');  ?>">
        <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
        <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
        <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
        <?php }
        foreach (Messages::getMessages() as $M) { ?>
            <div class="<?php echo strtolower($M->getLevel()); ?>-banner"><?php
                echo (string) $M; ?></div>
<?php   } ?>
