<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8\r\n");
?>
<!DOCTYPE html>
<html <?php
if (($lang = Internationalization::getCurrentLanguage())
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo 'dir="rtl" class="rtl"';
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
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/theme.css" media="screen">
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
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/chosen.min.css">
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
    <script src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-fonts.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/chosen.jquery.min.js"></script>
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
</head>
<body>
<div id="container" class="container well">
  <nav class="navbar">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#nav">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" id="logo" href="<?php echo ROOT_PATH; ?>index.php" title="<?php echo __('Support Center'); ?>">
          <img src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php echo $ost->getConfig()->getTitle(); ?>">
        </a>
      </div>
      <div class="collapse navbar-collapse" id="nav">
        <?php
        if (($all_langs = Internationalization::getConfiguredSystemLanguages())
            && (count($all_langs) > 1)
        ) {
          ?>
          <ul class="nav navbar-nav">
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Language <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
          <?php
            $qs = array();
            parse_str($_SERVER['QUERY_STRING'], $qs);
            foreach ($all_langs as $code=>$info) {
                list($lang, $locale) = explode('_', $code);
                $qs['lang'] = $code;
        ?>
                <li><a class="flag flag-<?php echo strtolower($locale ?: $info['flag'] ?: $lang); ?>"
                    href="?<?php echo http_build_query($qs);
                    ?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a></li>
        <?php } ?>
        </ul>
        </li>
        </ul>
        <?php } ?>
        <ul class="nav navbar-nav navbar-right">
          <?php
          if($nav){ ?>
            <?php
            if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
              foreach($navs as $name =>$nav) {
                echo sprintf('<li><a class="%s %s" href="%s">%s</a></li>%s',$nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
              }
            }
          } ?>
          <?php
          if ($thisclient && is_object($thisclient) && $thisclient->isValid()
            && !$thisclient->isGuest()) { ?>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                  <?php echo Format::htmlchars($thisclient->getName()); ?> <span class="caret"></span></a>
              <ul class="dropdown-menu" role="menu">
                <li><a href="<?php echo ROOT_PATH; ?>open.php"><?php echo __('Open a New Ticket');?></a></li>
                <li><a href="<?php echo ROOT_PATH; ?>profile.php"><?php echo __('Profile'); ?></a></li>
                <li><a href="<?php echo ROOT_PATH; ?>tickets.php"><?php echo sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()); ?></a></li>
                <li class="divider"></li>
                <li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li>
              </ul>
            </li>
          <?php } elseif($nav) {
             if ($cfg->getClientRegistrationMode() == 'public') { ?>
               <li class="dropdown">
                 <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                   <?php echo __('Guest User'); ?><span class="caret"></span></a>
                 <ul class="dropdown-menu" role="menu"><?php
             }
             if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
               <li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li></ul><?php
             }
             elseif ($cfg->getClientRegistrationMode() != 'disabled') { ?>
               <li><a class="status" href="../view.php"><?php echo __('Check Ticket Status'); ?></a></li>
               <li><a href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a></li></ul>
             <?php }
          } ?>
        </ul>
      </div>
  </nav>
  <div class="clearfix"><br/></div>
  <div id="content">
  <?php if($errors['err']) { ?>
    <div id="msg_error" class="alert alert-danger"><?php echo $errors['err']; ?></div>
  <?php }elseif($msg) { ?>
    <div id="msg_notice" class="alert alert-info"><?php echo $msg; ?></div>
  <?php }elseif($warn) { ?>
    <div id="msg_warning" class="alert alert-warning"><?php echo $warn; ?></div>
  <?php } ?>
