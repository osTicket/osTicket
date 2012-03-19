<!DOCTYPE html>
<head>
    <title><?php echo $wizard['title']; ?></title>
    <link rel="stylesheet" href="css/wizard.css">
    <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
    <script type="text/javascript" src="js/tips.js"></script>
    <script type="text/javascript" src="js/setup.js"></script>
</head>
<body>
    <div id="wizard">
        <div id="header">
            <img id="logo" src="./images/<?php echo $wizard['logo']?$wizard['logo']:'logo.png'; ?>" width="280" height="72" alt="osTicket">
            <ul>
                <li class="info"><?php echo $wizard['tagline']; ?></li>
                <li>
                   <?php
                   foreach($wizard['menu'] as $k=>$v)
                    echo sprintf('<a target="_blank" href="%s">%s</a> &mdash; ',$v,$k);
                   ?>
                    <a target="_blank" href="http://osticket.com/support/contact.php">Contact Us</a>
                </li>
            </ul>
        </div>
        <div id="content">
