<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html<?php
if (($lang = Internationalization::getCurrentLanguage())
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo ' dir="rtl" class="rtl"';
if ($lang) {
    $langs = array_unique(array($lang, $cfg->getPrimaryLanguage()));
    $langs = Internationalization::rfc1766($langs);
    echo ' lang="' . $lang . '"';
    header("Content-Language: ".implode(', ', $langs));
}
?>>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo Format::htmlchars($title); ?></title>
    <meta name="description" content="customer support platform">
    <meta name="keywords" content="osTicket, Customer support system, support ticket system">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!--Bootstrap loading via CDN until we can load assets during packaging-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/osticket.css" media="screen">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/bootstrap-theme.css" media="screen">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/print.css" media="print">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/typeahead.css" media="screen" />
    <!---Uncomment the following line to try another theme-->
    <!--<link rel="stylesheet" href="https://bootswatch.com/cyborg/bootstrap.min.css" media="screen">-->
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
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-fonts.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.full.min.js"></script>
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
            hreflang="x-default";
<?php
    }
    ?>
</head>
<body>
<div id="container" class="container well">
    <nav class="navbar">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xs-12 col-sm-4">
                    <a class="navbar-left" href="<?php echo ROOT_PATH; ?>index.php" title="<?php echo __('Support Center'); ?>">
                        <img class="img-responsive" src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php echo $ost->getConfig()->getTitle(); ?>">
                    </a>
                </div>
                <div class="col-xs-12 col-sm-8">
                    <ul class="nav navbar-nav navbar-right text-center">
                        <?php
                        if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
                            foreach($navs as $name =>$nav) {
                                echo sprintf('<li role="presentation" class="%s"><a class="%s" href="%s">%s</a></li>%s',
                                $nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
                            }
                        }
                        if ($thisclient && is_object($thisclient) && $thisclient->isValid()
                            && !$thisclient->isGuest()) { ?>
                            <p class="navbar-text"><?php echo Format::htmlchars($thisclient->getName()); ?></p>
                            <li><a href="<?php echo ROOT_PATH; ?>profile.php">
                                <?php echo __('Profile'); ?>
                            </a></li>
                            <li><a href="<?php echo ROOT_PATH; ?>tickets.php">
                                <?php echo sprintf(__('Tickets <span class="badge">%d</span>'), $thisclient->getNumTickets()); ?>
                            </a></li>
                            <li><a href="<?php echo $signout_url; ?>">
                                <?php echo __('Sign Out'); ?>
                            </a></li>
                        <?php } elseif($nav) {
                            if ($cfg->getClientRegistrationMode() == 'public') { ?>
                                <p class="navbar-text"><?php echo __('Guest User'); ?></p>
                            <?php }
                            if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
                                <li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li><?php
                            } elseif ($cfg->getClientRegistrationMode() != 'disabled') { ?>
                                <li><a class="lock" href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a></li>
                            <?php }
                        } ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <div class="row">
        <div class="col-xs-12" id="nav">
            <nav>
                <ul class="nav nav-pills nav-justified">
                    <?php
                    if (($all_langs = Internationalization::getConfiguredSystemLanguages()) && (count($all_langs) > 1)) { ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                Language<span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                <?php
                                $qs = array();
                                parse_str($_SERVER['QUERY_STRING'], $qs);
                                foreach ($all_langs as $code=>$info) {
                                    list($lang, $locale) = explode('_', $code);
                                    $qs['lang'] = $code; ?>
                                    <li>
                                        <a class="flag flag-<?php echo strtolower($locale ?: $info['flag'] ?: $lang); ?>"
                                            href="?<?php echo http_build_query($qs);?>"
                                            title="<?php echo Internationalization::getLanguageDescription($code); ?>">
                                            <p><?php echo $locale ?: $info['flag'] ?: $lang; ?></p>
                                        </a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        </div>
    </div>
    <div class="clearfix"><br/></div>
    <div id="content">
    <?php if($errors['err']) { ?>
        <div id="msg_error" class="alert alert-danger"><?php echo $errors['err']; ?></div>
    <?php }elseif($msg) { ?>
        <div id="msg_notice" class="alert alert-info"><?php echo $msg; ?></div>
    <?php }elseif($warn) { ?>
        <div id="msg_warning" class="alert alert-warning"><?php echo $warn; ?></div>
    <?php } ?>
