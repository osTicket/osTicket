<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <title><?php echo ($ost && ($title=$ost->getPageTitle()))?$title:'osTicket :: Staff Control Panel'; ?></title>
    <!--[if IE]>
    <style type="text/css">
        .tip_shadow { display:block !important; }
    </style>
    <![endif]-->
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-1.8.3.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
    <script type="text/javascript" src="../js/jquery.multifile.js"></script>
    <script type="text/javascript" src="./js/tips.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-fonts.js"></script>
    <script type="text/javascript" src="./js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="./js/scp.js"></script>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/thread.css" media="screen">
    <link rel="stylesheet" href="./css/scp.css" media="screen">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css" media="screen">
    <link rel="stylesheet" href="./css/typeahead.css" media="screen">
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css"
         rel="stylesheet" media="screen" />
    <link type="text/css" rel="stylesheet" href="../css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="./css/dropdown.css">
    <script type="text/javascript" src="./js/jquery.dropdown.js"></script>
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
</head>
<body>
<div id="container">
    <?php
    if($ost->getError())
        echo sprintf('<div id="error_bar">%s</div>', st($ost->getError()));
    elseif($ost->getWarning())
        echo sprintf('<div id="warning_bar">%s</div>', st($ost->getWarning()));
    elseif($ost->getNotice())
        echo sprintf('<div id="notice_bar">%s</div>', st($ost->getNotice()));
    ?>
    <div id="header">
        <a href="index.php" id="logo"><?php t("osTicket - Customer Support System"); ?></a>
        <p id="info"><?php t("Welcome, <strong>%s</strong>", $thisstaff->getFirstName()); ?>
           <?php
            if($thisstaff->isAdmin() && !defined('ADMINPAGE')) { ?>
            | <a href="admin.php"><?php t("Admin Panel"); ?></a>
            <?php }else{ ?>
            | <a href="index.php"><?php t("Staff Panel"); ?></a>
            <?php } ?>
            | <a href="profile.php"><?php t("My Preferences"); ?></a>
            | <a href="logout.php?auth=<?php echo $ost->getLinkToken(); ?>"><?php t("Log Out"); ?></a>
            <?php
                $languages = localizer::getInstance()->getLanguages();
                if (count($languages) > 1): ?>
                | <?php t("Language"); ?>:
                    <?php foreach ($languages as $lang):?>
                        <a href="<?php echo $_SERVER["URI"]."?lang=$lang"; ?>"><?php t($lang); ?></a>
                    <?php endforeach; ?>
            <?php endif; ?>
        </p>
    </div>
    <ul id="nav">
        <?php
        if(($tabs=$nav->getTabs()) && is_array($tabs)){
            foreach($tabs as $name =>$tab) {
                echo sprintf('<li class="%s"><a href="%s">%s</a>',$tab['active']?'active':'inactive',$tab['href'],st($tab['desc']));
                if(!$tab['active'] && ($subnav=$nav->getSubMenu($name))){
                    echo "<ul>\n";
                    foreach($subnav as $item) {
                        echo sprintf('<li><a class="%s" href="%s" title="%s" >%s</a></li>',
                                $item['iconclass'],$item['href'],$item['title'],st($item['desc']));
                    }
                    echo "\n</ul>\n";
                }
                echo "\n</li>\n";
            }
        } ?>
    </ul>
    <ul id="sub_nav">
        <?php
        if(($subnav=$nav->getSubMenu()) && is_array($subnav)){
            $activeMenu=$nav->getActiveMenu();
            if($activeMenu>0 && !isset($subnav[$activeMenu-1]))
                $activeMenu=0;
            foreach($subnav as $k=> $item) {
                if($item['droponly']) continue;
                $class=$item['iconclass'];
                if ($activeMenu && $k+1==$activeMenu
                        or (!$activeMenu
                            && (strpos(strtoupper($item['href']),strtoupper(basename($_SERVER['SCRIPT_NAME']))) !== false
                                or ($item['urls']
                                    && in_array(basename($_SERVER['SCRIPT_NAME']),$item['urls'])
                                    )
                                )))
                    $class="$class active";

                echo sprintf('<li><a class="%s" href="%s" title="%s" >%s</a></li>',$class,$item['href'],$item['title'],st($item['desc']));
            }
        }
        ?>
    </ul>
    <div id="content">
        <?php if($errors['err']) { ?>
            <div id="msg_error"><?php t($errors['err']); ?></div>
        <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php t($msg); ?></div>
        <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php t($warn); ?></div>
        <?php } ?>
