<?php
header("Content-Type: text/html; charset=UTF-8");
header("Content-Security-Policy: frame-ancestors ".$cfg->getAllowIframes().";");

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
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/colorthemes/default-material-blue-amber.css" media="all"/>
	<link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/thread.css" media="all"/>
	<link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/scp.css" media="all"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css" media="screen"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/typeahead.css" media="screen"/>
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css"
         rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/jquery-ui-timepicker-addon.css" media="all"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css"/>
    <!--[if IE 7]>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome-ie7.min.css"/>
    <![endif]-->
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/dropdown.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/loadingbar.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/translatable.css"/>
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/oscar-favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/oscar-favicon-16x16.png" sizes="16x16" />

    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
</head>
<body>
<div id="container">
<div id="wrapper">
<div id="pjax-container" class="<?php if ($_POST) echo 'no-pjax'; ?>">
    <?php
    if($ost->getError())
        echo sprintf('<div id="error_bar">%s</div>', $ost->getError());
    elseif($ost->getWarning())
        echo sprintf('<div id="warning_bar">%s</div>', $ost->getWarning());
    elseif($ost->getNotice())
        echo sprintf('<div id="notice_bar">%s</div>', $ost->getNotice());
    ?>

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
<div id="header" class="noselect">
	<a href="<?php echo ROOT_PATH ?>scp/index.php" class="/*no-pjax*/" id="logo">
		<span class="valign-helper"></span>
		<img src="<?php echo ROOT_PATH ?>scp/logo.php?<?php echo strtotime($cfg->lastModified('staff_logo_id')); ?>" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/>
	</a>
	<ul id="nav">
		<?php include STAFFINC_DIR . "templates/navigation.tmpl.php"; ?>
	</ul>
	<div class="flex-spacer">
	</div>	
	<div id="overflow_nav">
		<a>
			Menu <i class="icon-angle-down pull-right"></i>
		</a>
	</div>
	<!-- SEARCH FORM START -->
	<div id='header_search'>
		<form action="tickets.php" method="get" onsubmit="javascript:
			  $.pjax({
				url:$(this).attr('action') + '?' + $(this).serialize(),
				container:'#pjax-container',
				timeout: 2000
			  });
			return false;">
			<input type="hidden" name="a" value="search">
			<input type="hidden" name="search-type" value=""/>
			<div class="attached input">
				<input type="text" class="basic-search" data-url="ajax.php/tickets/lookup" name="query"
				  size="30" value="<?php echo Format::htmlchars($_REQUEST['query'] ?? null, true); ?>"
				  placeholder="Search tickets" autocomplete="off" autocorrect="off" autocapitalize="off">
				<a href="#" class="button advanced" onclick="javascript:
					$.dialog('ajax.php/tickets/search', 201);">
					<?php echo __('<i class="icon-gear"></i>'); ?>
				</a>
				<button type="submit" class="attached button"><i class="icon-search"></i>
				</button>
			</div>
		</form>
	</div>
	<!-- SEARCH FORM END -->
	<div id="outer_info_avatar">
		<div id="info_avatar" class="avatar pull-right">
				<?php echo $thisstaff->getAvatar(); ?>
		</div>
	</div>
	<div id="info" class="/*no-pjax*/">
		<span><?php echo sprintf(__('Welcome %s'), '<strong>'.$thisstaff->getFirstName().'</strong>'); ?></span>
		<?php if($thisstaff->isAdmin() && !defined('ADMINPAGE')) { ?>
		<a href="<?php echo ROOT_PATH ?>scp/admin.php" class="no-pjax"><?php echo __('Admin Panel'); ?></a>
		<?php } else { ?>
		<a href="<?php echo ROOT_PATH ?>scp/index.php" class="no-pjax"><?php echo __('Agent Panel'); ?></a>
		<?php } ?>
		<a href="<?php echo ROOT_PATH ?>scp/profile.php"><?php echo __('Profile'); ?></a>
		<a href="<?php echo ROOT_PATH ?>scp/logout.php?auth=<?php echo $ost->getLinkToken(); ?>" class="no-pjax"><?php echo __('Log Out'); ?></a>
	</div>
</div>
<div id="flex_container" class="flex">
    <?php include STAFFINC_DIR . "templates/sub-navigation.tmpl.php"; ?>
	<div id="v_flex_container" class="v_flex flex-spacer">
        <div id="content">
        <?php if(isset($errors['err'])) { ?>
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
