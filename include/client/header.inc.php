<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
header("Content-Security-Policy: frame-ancestors ".$cfg->getAllowIframes().";");

if (($lang = Internationalization::getCurrentLanguage())) {
    $langs = array_unique(array($lang, $cfg->getPrimaryLanguage()));
    $langs = Internationalization::rfc1766($langs);
    header("Content-Language: ".implode(', ', $langs));
}
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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo Format::htmlchars($title); ?></title>
    <meta name="description" content="customer support platform">
    <meta name="keywords" content="osTicket, Customer support system, support ticket system">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/osticket.css" media="screen"> 
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/print.css" media="print">
   <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/typeahead.css"
         media="screen" /> 
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css"
        rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/jquery-ui-timepicker-addon.css" media="all">

    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css" media="screen"> 
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>assets/fontawesome/css/fa-mesys.css">
   <!-- <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css"> -->
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css">
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/MESYS-ICON.svg" sizes="32x32 16x16" />
     <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-3.4.0.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.12.1.custom.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-timepicker-addon.js"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
    <script src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js"></script>
      <link href="<?php echo ROOT_PATH; ?>assets/bootstrap/css/bootstrap.css" rel="stylesheet">
<!--      
       <script src="https://kit.fontawesome.com/4cf4e10041.js" crossorigin="anonymous"></script>
-->
<style>
.html,
 .body{
 	font-family: avenir;
 }
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
     
    </style>        
    
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
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>?<?php
            echo http_build_query($qs); ?>" hreflang="<?php echo $L; ?>" />
<?php
        } ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"
            hreflang="x-default" />
<?php
    }
    ?>
</head>

      <?php
        if($ost->getError())
            echo sprintf('<div class="error_bar">%s</div>', $ost->getError());
        elseif($ost->getWarning())
            echo sprintf('<div class="warning_bar">%s</div>', $ost->getWarning());
        elseif($ost->getNotice())
            echo sprintf('<div class="notice_bar">%s</div>', $ost->getNotice());
       ?>
    
     	<body >
   <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/theme.css" media="screen">  
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/thread.css" media="screen"> 	
   	
    <div id="container">         		
   	         		
	<div id="header">
	<nav class="navbar fixed-top navbar-expand-sm navbar-light bg-white rounded" >
 		<a class="navbar-brand " id="logo" href="<?php echo ROOT_PATH; ?>index.php"
            title="<?php echo __('Support Center'); ?>">
         	<span class="valign-helper"></span>
            <img style="height:46px; width:auto;" src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php
            echo $ost->getConfig()->getTitle(); ?>">
    	</a>
    	<button class="navbar-toggler" type="button" data-toggle="collapse" 
    		data-target="#navbarsExampleDefault" 
  			aria-controls="navbarsExampleDefault" 
  			aria-expanded="false" 
  			aria-label="Toggle navigation">
    		<span class="navbar-toggler-icon"></span>
  		</button>	
		<div class="collapse navbar-collapse" id="navbarsExampleDefault">
	   	<?php
   	   if($nav){ ?>
   			<ul class="navbar-nav mr-auto">
  		   		<?php
         			if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
            			foreach($navs as $name =>$nav) {
								if( $name == 'home') {	
										echo sprintf('');
								} else {
										$formato = '<li class="nav-item %1$s ">
               								<a class="nav-link %1$s %2$s" 
 	            	  							href="%3$s">%4$s</a></li>%5$s';	
               					echo sprintf( $formato ,$nav['active']?'active':'',
               									$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
								}         	
               		}
            		} ?>
        		</ul>
      	<?php
			}else{ ?>
   	   <hr>
   	   <?php
   	   } ?>
  			<ul class="navbar-nav  my-2 my-md-0">
  				<li class="nav-item dropdown">			
					<?php
                	if ($thisclient && is_object($thisclient) && $thisclient->isValid()
                	&& !$thisclient->isGuest()) { ?>
                	<a class="nav-link dropdown-toggle" href="#" id="dropdown01" 
      	  				data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								  <i class="fa fa-user-md " style="color: #10bbe8; font-size: 1.2rem;"></i>
								<?php 
                 				echo Format::htmlchars($thisclient->getName()).'&nbsp;';
                			?>
                	</a>
               	<div class="dropdown-menu" aria-labelled by="userinfo">
                		<a class="dropdown-item" href="<?php echo ROOT_PATH; ?>profile.php">
                			<i class="fa fa-id-card " style="color: #10bbe8; font-size: 1.2rem;"></i> 
                		 <?php echo __('Profile'); ?></a> |
                		<a class="dropdown-item" href="<?php echo ROOT_PATH; ?>tickets.php">
                			<i class="fa fa-list " style="color: #10bbe8; font-size: 1.2rem;"></i> <?php echo sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()); ?></a> -
                		<a class="dropdown-item" href="<?php echo $signout_url; ?>">
                			<i class="fa fa-sign-out-alt " style="color: #10bbe8; font-size: 1.2rem;"></i> <?php echo __('Sign Out'); ?></a>
						</div>            	
            	<?php
            		} elseif($nav) {
                		if ($cfg->getClientRegistrationMode() == 'public') { 
                			?>
	                		<a class="nav-link dropdown-toggle" href="#" id="dropdown01" 
   	   	  				data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      	              	     <i class="fa fa-user " style="color: #10bbe8; font-size: 1.2rem;"></i>	<?php echo __('Guest User'); ?> 
      	              	</a> 
      	              <?php	
      	              if ($cfg->getClientRegistrationMode() != 'disabled') { ?>
      	              		<div class="dropdown-menu" aria-labelled by="userinfo">
                    				<a class="dropdown-item" href="<?php echo $signin_url; ?>">
                    				<i class="fa fa-sign-in-alt " style="color: #10bbe8; font-size: 1.2rem;"></i> <?php echo __('Sign In'); ?> </a>
                    				<a class="dropdown-item" href="<?php echo ROOT_PATH; ?>scp/">
                    				<i class="fa fa-wrench " style="color: #10bbe8; font-size: 1.2rem;"></i><?php echo __("I'm an agent"); ?></a>
                    			</div>
								<?php
                			} 
                			if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { 
	                			?>
	                			<div class="dropdown-menu" aria-labelled by="userinfo">
                    			<a class="dropdown-item" href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a>
                    			</div>
                    		<?php
                			}
						?>	
    										
    									                   	
                   <?php 	}      
            		} ?>
      	  	</li>
					  		
				<?php
					if (($all_langs = Internationalization::getConfiguredSystemLanguages())
    				&& (count($all_langs) > 1)
					) {?>
						<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" id="dropdown01" 
      	  				data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">							      	  			
      	  			<i class="fa fa-globe " style="color: #10bbe8; font-size: 1.2rem;"></i> 
      	  			<i class="fa fa-language " style="color: #10bbe8; font-size: 1.2rem;"></i>	</a>
     	   			<div class="dropdown-menu" aria-labelled by="Locale Intl">
						<?php 
    					$qs = array();
    					parse_str($_SERVER['QUERY_STRING'], $qs);
    					foreach ($all_langs as $code=>$info) {
        					list($lang, $locale) = explode('_', $code);
        						$qs['lang'] = $code;
						?>
							<a class="dropdown-item "
            				href="?<?php echo http_build_query($qs); ?>" 
            				title="<?php echo Internationalization::getLanguageDescription($code); ?>">
            					<?php echo strtolower($info['flag'] ?: $locale ?: $lang); ?>
            			</a>
						<?php } ?>
						</div>
						 	</li>
					<?php } ?>
      	 
    		</ul>
  		</div>
	</nav>
	</div>
      
        <div class="clear"></div>
      
        <div id="content">

         <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
         <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
         <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
         <?php } ?>
