<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <title>osTicket Staff Control Panel</title>
    <!--[if IE]>
    <style type="text/css">
        .tip_shadow { display:block !important; }
    </style>
    <![endif]-->
    <script type="text/javascript" src="../js/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui-1.8.18.custom.min.js"></script>
    <script type="text/javascript" src="../js/jquery.multifile.js"></script>
    <script type="text/javascript" src="./js/tips.js"></script>
    <script type="text/javascript" src="./js/nicEdit.js"></script>
    <script type="text/javascript" src="./js/bootstrap-typeahead.js"></script>
    <script type="text/javascript" src="./js/scp.js"></script>
    <link rel="stylesheet" href="./css/scp.css" media="screen">
    <link rel="stylesheet" href="./css/typeahead.css" media="screen">
    <link type="text/css" href="../css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />   
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
</head>
<body onunload="">
<div id="container">
    <div id="header">
        <a href="index.php" id="logo">osTicket - Customer Support System</a>
        <p id="info">Howdy, <strong><?php echo $thisstaff->getUserName(); ?></strong>
           <?php
            if($thisstaff->isAdmin() && !defined('ADMINPAGE')) { ?>
            | <a href="admin.php">Admin Panel</a>
            <?php }else{ ?>
            | <a href="index.php">Staff Panel</a>
            <?php } ?>
            | <a href="profile.php">My Preferences</a> 
            | <a href="logout.php?auth=<?php echo md5($ost->getCSRFToken().SECRET_SALT.session_id()); ?>">Log Out</a>
        </p>
    </div>
    <ul id="nav">
        <?php
        if(($tabs=$nav->getTabs()) && is_array($tabs)){
            foreach($tabs as $name =>$tab) {
                echo sprintf('<li class="%s"><a href="%s">%s</a>',$tab['active']?'active':'inactive',$tab['href'],$tab['desc']);
                if(!$tab['active'] && ($subnav=$nav->getSubMenu($name))){
                    echo "<ul>\n";
                    foreach($subnav as $item) {
                        echo sprintf('<li><a class="%s" href="%s" title="%s" >%s</a></li>',
                                $item['iconclass'],$item['href'],$item['title'],$item['desc']);
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

                echo sprintf('<li><a class="%s" href="%s" title="%s" >%s</a></li>',$class,$item['href'],$item['title'],$item['desc']);
            }
        }
        ?>
    </ul>
    <div id="content">
        <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
        <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
        <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
        <?php } ?>

