<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
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
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>assets/bootstrap-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/osticket.css" media="screen">
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/theme.css" media="screen">
    <!--<link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/bootstrap-custom.css" media="screen">-->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/print.css" media="print">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/typeahead.css" media="screen" />
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" media="screen" />
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/thread.css" media="screen">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css" media="screen">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css"/>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.multiselect.min.js"></script>
    <script src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-fonts.js"></script>
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
</head>
<body>
 <div id="container" class="container-fluid well">
  <nav class="navbar">
    <div class="container-fluid">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand pull-left" href="<?php echo ROOT_PATH; ?>index.php" title="<?php echo __('Support Center'); ?>">
          <img id="logo" src="<?php echo ROOT_PATH; ?>logo.php" alt="<?php echo $ost->getConfig()->getTitle(); ?>">
        </a>
      </div>
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav">
          <?php
          if (($all_langs = Internationalization::availableLanguages()) && (count($all_langs) > 1)) {
            foreach ($all_langs as $code=>$info) {
              list($lang, $locale) = explode('_', $code);
          ?>
              <li><a class="flag flag-<?php echo strtolower($locale ?: $info['flag'] ?: $lang); ?>"
              href="?<?php echo urlencode($_GET['QUERY_STRING']); ?>&amp;lang=<?php echo $code; ?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a></li>
            <?php }
          } ?>
        </ul>
        <ul class="nav navbar-nav navbar-right">
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
            if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) { ?>
              <li><a href="<?php echo $signout_url; ?>"><?php echo __('Sign Out'); ?></a></li><?php
            }
            elseif ($cfg->getClientRegistrationMode() != 'disabled') {
              if ($cfg->getClientRegistrationMode() == 'public') { ?>
                <li><p class="navbar-text navbar-right">Guest User | <a href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a></p></li>
                <?php } else { ?>
                  <a href="<?php echo $signin_url; ?>"><?php echo __('Sign In'); ?></a>
                  <?php }
                } }?>
              </ul>
            </div>
          </div>
        </nav>
        <?php
        if($nav){ ?>
          <ul id="nav" class="nav nav-pills nav-justified">
            <?php
            if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
              foreach($navs as $name =>$nav) {
                echo sprintf('<li role="presentation" class="%s"><a class="%s" href="%s">%s</a></li>%s',$nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),$nav['desc'],"\n");
              }
            } ?>
          </ul>
          <?php } ?>
          <div id="content" class="container-fluid">
            
            <?php if($errors['err']) { ?>
              <div id="msg_error" class="alert alert-danger"><?php echo $errors['err']; ?></div>
              <?php }elseif($msig) { ?>
                <div id="msg_notice" class="alert alert-info"><?php echo $msg; ?></div>
                <?php }elseif($warn) { ?>
                  <div id="msg_warning" class="alert alert-warning"><?php echo $warn; ?></div>
                  <?php } ?>
