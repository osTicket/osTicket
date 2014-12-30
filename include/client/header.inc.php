<?php
$title = ($cfg && is_object($cfg) && $cfg->getTitle()) ? $cfg->getTitle() : 'osTicket :: ' . __('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
        . ($thisclient ? "?e=" . urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=" . $ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8\r\n");
?>
<!DOCTYPE html>
<html <?php
if (($lang = Internationalization::getCurrentLanguage()) && ($info = Internationalization::getLanguageInfo($lang)) && (@$info['direction'] == 'rtl')) {
    echo 'dir="rtl" class="rtl"';
}
?>>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title><?= Format::htmlchars($title); ?></title>
        <meta name="description" content="customer support platform">
        <meta name="keywords" content="osTicket, Customer support system, support ticket system">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <link rel="stylesheet" href="<?= ROOT_PATH; ?>css/osticket.css" media="screen">
        <link rel="stylesheet" href="<?= ASSETS_PATH; ?>css/theme.css" media="screen">
        <link rel="stylesheet" href="<?= ASSETS_PATH; ?>css/print.css" media="print">
        <link rel="stylesheet" href="<?= ROOT_PATH; ?>scp/css/typeahead.css"
              media="screen" />
        <link type="text/css" href="<?= ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css"
              rel="stylesheet" media="screen" />
        <link rel="stylesheet" href="<?= ROOT_PATH; ?>css/thread.css" media="screen">
        <link rel="stylesheet" href="<?= ROOT_PATH; ?>css/redactor.css" media="screen">
        <link type="text/css" rel="stylesheet" href="<?= ROOT_PATH; ?>css/font-awesome.min.css">
        <link type="text/css" rel="stylesheet" href="<?= ROOT_PATH; ?>css/flags.css">
        <link type="text/css" rel="stylesheet" href="<?= ROOT_PATH; ?>css/rtl.css"/>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/jquery-1.8.3.min.js"></script>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
        <script src="<?= ROOT_PATH; ?>js/osticket.js"></script>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/filedrop.field.js"></script>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/jquery.multiselect.min.js"></script>
        <script src="<?= ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/redactor.min.js"></script>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/redactor-osticket.js"></script>
        <script type="text/javascript" src="<?= ROOT_PATH; ?>js/redactor-fonts.js"></script>
        <?php
        if ($ost && ($headers = $ost->getExtraHeaders())) {
            echo "\n\t" . implode("\n\t", $headers) . "\n";
        }
        ?>
    </head>
    <body>
        <div id="container">
            <div id="header">
                <a class="pull-left" id="logo" href="<?= ROOT_PATH; ?>index.php"
                   title="<?= __('Support Center'); ?>"><img src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php echo $ost->getConfig()->getTitle(); ?>"
                                                          style="height: 5em"></a>
                <div class="pull-right flush-right">
                    <p>
                        <?php
                        if ($thisclient && is_object($thisclient) && $thisclient->isValid() && !$thisclient->isGuest()) :
                            echo Format::htmlchars($thisclient->getName()) . '&nbsp;|';
                            ?>
                            <a href="<?= ROOT_PATH; ?>profile.php"><?= __('Profile'); ?></a> |
                            <a href="<?= ROOT_PATH; ?>tickets.php"><?= sprintf(__('Tickets <b>(%d)</b>'), $thisclient->getNumTickets()); ?></a> -
                            <a href="<?= $signout_url; ?>"><?= __('Sign Out'); ?></a>
                            <?php
                        elseif ($nav) :
                            if ($cfg->getClientRegistrationMode() == 'public') :
                                echo __('Guest User') . ' | ';
                            endif;
                            if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) :
                                ?>
                                <a href="<?= $signout_url; ?>"><?= __('Sign Out'); ?></a>
                            <?php elseif ($cfg->getClientRegistrationMode() != 'disabled') : ?>
                                <a href="<?= $signin_url; ?>"><?= __('Sign In'); ?></a>
                                <?php
                            endif;
                        endif;
                        ?>
                    </p>
                    <p>
                        <?php
                        if (($all_langs = Internationalization::availableLanguages()) && (count($all_langs) > 1)) :
                            foreach ($all_langs as $code => $info) :
                                list($lang, $locale) = explode('_', $code);
                                ?>
                                <a class="flag flag-<?= strtolower($locale ? : $info['flag'] ? : $lang); ?>" href="?<?= urlencode($_GET['QUERY_STRING']); ?>&amp;lang=<?= $code; ?>" title="<?= Internationalization::getLanguageDescription($code); ?>">&nbsp;</a>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </p>
                </div>
            </div>
            <div class="clear"></div>
            <?php if ($nav) : ?>
                <ul id="nav" class="flush-left">
                    <?php
                    if ($nav && ($navs = $nav->getNavLinks()) && is_array($navs)) {
                        foreach ($navs as $name => $nav) {
                            echo sprintf('<li><a class="%s %s" href="%s">%s</a></li>%s', $nav['active'] ? 'active' : '', $name, (ROOT_PATH . $nav['href']), $nav['desc'], "\n");
                        }
                    }
                    ?>
                </ul>
            <?php else : ?>
                <hr>
            <?php endif; ?>
            <div id="content">

                <?php if ($errors['err']) : ?>
                    <div id="msg_error"><?= $errors['err']; ?></div>
                <?php elseif ($msg) : ?>
                    <div id="msg_notice"><?= $msg; ?></div>
                <?php elseif ($warn) : ?>
                    <div id="msg_warning"><?= $warn; ?></div>
                <?php endif; ?>
