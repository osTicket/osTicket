<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
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
?>>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo Format::htmlchars($title); ?></title>
    <meta name="description" content="customer support platform">
    <meta name="keywords" content="osTicket, Customer support system, support ticket system">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/osticket.css" media="screen">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/theme.css" media="screen">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/print.css" media="print">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/typeahead.css"
         media="screen" />
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css"
        rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/thread.css" media="screen">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css" media="screen">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css">
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-1.11.2.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
    <script src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/fabric.min.js"></script>
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
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '?' .
            http_build_query($qs); ?>" hreflang="<?php echo $L; ?>" />
<?php   } ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>"
            hreflang="x-default" />
<?php
    }  ?>
</head>
<body>
<div id="header">
    <div class="container">
        <div class="col-md-3">
            <a id="logo" href="<?php echo ROOT_PATH; ?>index.php"
            title="<?php echo __('Support Center'); ?>">
                <span class="valign-helper"></span>
                <img src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php
                echo $ost->getConfig()->getTitle(); ?>">
            </a>
        </div>

        <?php if (($all_langs = Internationalization::getConfiguredSystemLanguages()) && (count($all_langs) > 1)) { ?>
            <div class="col-md-9">
                <div class="flush-right">
                <?php
                $qs = array();
                parse_str($_SERVER['QUERY_STRING'], $qs);
                foreach ($all_langs as $code=>$info) {
                    list($lang, $locale) = explode('_', $code);
                    $qs['lang'] = $code; ?>
                    <a class="flag flag-<?php echo strtolower($locale ?: $info['flag'] ?: $lang); ?>"
                        href="?<?php echo http_build_query($qs);
                        ?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a>
                <?php
                } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php if($nav){ ?>
<nav class="navbar navbar-inverse" id="navigation-bar">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo ROOT_PATH; ?>index.php">
                <i class="fa fa-ticket"></i>
                osTicket
            </a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <?php
                if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
                    foreach($navs as $name => $nav) {
                        echo sprintf('<li class="%s"><a class="%s" href="%s">%s</a></li>%s',$nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
                    }
                }
                ?>
            </ul>
            <ul class="navbar-form navbar-right">
                <?php
                if ($thisclient && is_object($thisclient) && $thisclient->isValid() && !$thisclient->isGuest()) { ?>
                    <?= Format::htmlchars($thisclient->getName()).'&nbsp;|'; ?>
                    <a class='btn btn-default' href="<?php echo ROOT_PATH; ?>profile.php">
                        <i class="fa fa-user"></i>
                        <?php echo __('Profile'); ?>
                    </a>
                    <a class='btn btn-primary' href="<?php echo ROOT_PATH; ?>tickets.php">
                        <i class="fa fa-ticket"></i>
                        <?php echo sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()); ?>
                    </a>
                    <a class='btn btn-danger' href="<?php echo $signout_url; ?>">
                        <i class="fa fa-sign-out"></i>
                        <?php echo __('Sign Out'); ?>
                    </a>
                <?php
                } elseif($nav) {
                      if ($cfg->getClientRegistrationMode() == 'public') { ?>
                        <a class='btn btn-default' disabled="disabled">
                            <i class="fa fa-user"></i>
                            <?php echo __('Guest User'); ?>
                        </a>
                <?php }
                      if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
                        <a class='btn btn-danger' href="<?php echo $signout_url; ?>">
                            <i class="fa fa-sign-out"></i>
                            <?php echo __('Sign Out'); ?>
                        </a>
                <?php } elseif ($cfg->getClientRegistrationMode() != 'disabled') { ?>
                        <a class='btn btn-success' href="<?php echo $signin_url; ?>">
                            <i class="fa fa-sign-in"></i>
                            <?php echo __('Sign In'); ?>
                        </a>
                <?php }
                } ?>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container-fluid -->
</nav>
<?php }else{ ?>
<hr>
<?php } ?>

<div id="content-container">
    <div class="container">
        <div id="content">

         <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
         <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
         <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
         <?php } ?>
