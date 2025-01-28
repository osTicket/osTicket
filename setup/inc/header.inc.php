<?php
if ($cfg)
    header("Content-Security-Policy: frame-ancestors ".$cfg->getAllowIframes()."; script-src 'self' 'unsafe-inline'; object-src 'none'");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
    "http://www.w3.org/TR/html4/loose.dtd">
<html <?php
if (($lang = Internationalization::getCurrentLanguage())
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo 'dir="rtl" class="rtl"';

// Dropped IE Support Warning
if (osTicket::is_ie())
    $warning = __('osTicket no longer supports Internet Explorer.');
?>>
<head>
    <title><?php echo $wizard['title']; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="css/wizard.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css">
</head>
<body>
    <div id="wizard">
        <?php if ($warning) echo sprintf('<div class="warning_bar">%s</div>', $warning); ?>
        <div id="header">
            <img id="logo" src="./images/<?php echo $wizard['logo'] ?: 'logo.png'; ?>" alt="osTicket">
            <div class="info"><?php echo $wizard['tagline']; ?></div>
            <br/>
            <ul class="links">
                <li>
                   <?php
                   foreach($wizard['menu'] as $k=>$v)
                    echo sprintf('<a target="_blank" href="%s">%s</a> &mdash; ',$v,$k);
                   ?>
                    <a target="_blank" href="https://osticket.com/contact-us"><?php echo __('Contact Us');?></a>
                </li>
            </ul>
            <div class="flags">
<?php
if (($all_langs = Internationalization::availableLanguages())
    && (count($all_langs) > 1)
) {
    foreach ($all_langs as $code=>$info) {
        list($lang, $locale) = explode('_', $code);
?>
        <a class="flag flag-<?php echo strtolower($locale ?: $info['flag'] ?: $lang); ?>"
            href="?<?php echo urlencode($_GET['QUERY_STRING']); ?>&amp;lang=<?php echo $code;
            ?>" title="<?php echo Internationalization::getLanguageDescription($code); ?>">&nbsp;</a>
<?php }
} ?>
            </div>
        </div>
        <div class="clear"></div>
        <div id="content">
