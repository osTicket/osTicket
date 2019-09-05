<!DOCTYPE HTML>
<?php
require(INCLUDE_DIR.'class.dashboard.php');

header("Content-Type: text/html; charset=UTF-8");
$title = ($ost && ($title=$ost->getPageTitle()))
    ? $title : ('osTicket :: '.__('Staff Control Panel'));
if (!isset($_SERVER['HTTP_X_PJAX'])) { ?>

<html<?php
if (($lang = Internationalization::getCurrentLanguage())
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo ' dir="rtl" class="rtl"';
if ($lang) {
    echo ' lang="' . Internationalization::rfc1766($lang) . '"';
}
?>>
<script>
            var resizefunc = [];
</script>
<head>
    <meta name="viewport" content="width=device-width,initial-scale=1">
	<link rel="icon" href="<?php echo ROOT_PATH ?>images/favicon.ico" type="image/x-icon" />
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
    
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/bootstrap.min.css" media="all">
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-1.11.2.min.js"></script>
    
    <script src="<?php echo ROOT_PATH; ?>scp/js/tether.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/modernizr.min.js"></script>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/morris.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/footable.bootstrap.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/icons.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/styles.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/scp.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/notify-metro.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/thread.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/bootstrap-datepicker.min.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css" media="screen">
    
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" media="screen" />
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/dropdown.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/loadingbar.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css">
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/helptopic.css"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/loadingoverlay.min.css"/>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/loadingoverlay.min.js"></script>
    <link type="text/css" rel="stylesheet" href="./css/translatable.css"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/accordian.css" media="all">
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/typeahead.css" media="screen">
    <?php
    
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
</head>
<body class="fixed-left">


 
    <div id="pjax-container" class="<?php if ($_POST) echo 'no-pjax'; ?>">
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
    <title><?php echo ($ost && ($title=$ost->getPageTitle()))?$title:'osTicket :: '.__('Staff Control Panel'); ?></title>
    
    <?php
} # endif X_PJAX 
?>

 <?php
    if($ost->getError())
        echo sprintf('<div id="error_bar">%s</div>', $ost->getError());
    elseif($ost->getWarning())
        echo sprintf('<div id="warning_bar">%s</div>', $ost->getWarning());
    elseif($ost->getNotice())
        echo sprintf('<div id="notice_bar">%s</div>', $ost->getNotice());
    ?>

 <script type="text/javascript"> 
 $(document).ready(function(){ 
 
 <?php //if($errors['err']) {echo "$.Notification.notify('warning','top right', 'Warning', '".$errors['err']."');";
 //}else
if($msg) {echo "$.Notification.notify('success','top right', '', '".$msg."');";}
        //}elseif($warn) {echo "$.Notification.notify('warning','top right', 'Overdue', '".$warn."');";}
        foreach (Messages::getMessages() as $M) { 
            
                echo "$.Notification.notify('success','top right', '', '".(string) $M."');";
 } ?>
 
 });
</script> 
        <div id="wrapper">

            <!-- Top Bar Start -->
            <div class="topbar">

                <!-- LOGO -->
                <div class="topbar-left">
                     <div class="text-center">
                        <a href="#" class=" open-left waves-light waves-effect logo"><i class="mdi mdi-menu"></i> <span>NASG</span></a>
                    </div>
                 
                </div>
                <!-- Button mobile view to collapse sidebar menu -->
                <nav class="navbar-custom">
                    <ul class="hide-phone list-inline float-left mb-0 mr-0">
                        <li class="list-inline-item notification-list hide-phone  mr-0">
                            <span class="nav-link">IT Support System</span>
                        </li>
                    </ul>

                    <ul class="list-inline float-right mb-0 mr-2">

                        <li class="list-inline-item notification-list hide-phone  mr-0">
                            <a class="nav-link waves-light waves-effect" href="#" id="btn-fullscreen">
                                <i class="mdi mdi-crop-free noti-icon"></i>
                            </a>
                        </li>

                        <li class="list-inline-item notification-list mr-0 hidden">
                        
                            <a class="nav-link right-bar-toggle waves-light waves-effect" href="#">
                                
                                <span class="mdi mdi-dots-horizontal noti-icon"></span>
                   
                            </a>
                        </li>
                        

                        <li class="list-inline-item dropdown notification-list mr-0" style="max-width: 32px;">
                            <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false" title="<?php echo __('My Tickets'); ?>">
                                <i class="fa fa-user noti-icon"></i>
                                <span class="badge badge-warning noti-icon-badge"><?php echo $MyOpenTickets; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-arrow dropdown-menu-lg" aria-labelledby="Preview">
                                <!-- item-->
                                <div class="dropdown-item noti-title">
                                    <h5 class="font-16"><a href="tickets.php?queue=31&p=1&l=0&s=0"><span class="badge badge-warning float-right"><?php echo $MyOpenTickets; ?></span></a>My Tickets</h5>
                                </div>

                                
                                <?php if ($MyReplyTickets <> '0') { ?>
                                <a href="/scp/tickets.php?queue=31&p=1&l=0&s=7" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-danger"><?php echo $MyReplyTickets; ?></div>
                                    <p class="notify-details">My Action<small class="text-muted">Waiting on my action</small></p>
                                </a> 
								<?php }
								if ($MyTheirReplyTickets <> '0') { ?>
                                <a href="/scp/tickets.php?queue=31&p=1&l=0&s=6" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-success"><?php echo $MyTheirReplyTickets; ?></div>
                                    <p class="notify-details">Their Action<small class="text-muted">Waiting on their action</small></p>
                                </a>
                                <?php }
								if ($MyThridPartyTickets <> '0') { ?>
                                <a href="/scp/tickets.php?queue=31&p=1&l=0&s=9" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-purple"><?php echo $MyThridPartyTickets; ?></div>
                                    <p class="notify-details">3rd Party<small class="text-muted">Awaiting 3rd party</small></p>
                                </a>
                                <?php }
								if ($MyHeldTickets <> '0') { ?>
                                <a href="/scp/tickets.php?queue=31&p=1&l=0&s=8" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-warning"><?php echo $MyHeldTickets; ?></div>
                                    <p class="notify-details">Held<small class="text-muted">Tickets on Hold</small></p>
                                </a>
								<?php } ?>
                                <!-- All-->
                                <a href="tickets.php?queue=31&p=1&l=0&s=0" class="dropdown-item notify-item notify-all">
                                    View All
                                </a>

                            </div>
                        </li>
						<li class="list-inline-item dropdown notification-list mr-0">
                            <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="falIT" aria-expanded="falIT"  title="<?php echo __(' IT Backlog'); ?>"> 
                                <i class="fa fa-laptop noti-icon"></i>
                                <span class="badge badge-danger noti-icon-badge"><?php echo $BacklogITTotal; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-arrow dropdown-menu-lg" aria-labelledby="Preview">
                                <!-- item-->
                                <div class="dropdown-item noti-title">
                                    <h5 class="font-16"><a href="tickets.php?queue=245&p=1&l=0&s=0"><span class="badge badge-danger float-right"><?php echo $BacklogITTotal; ?></span></a> IT Backlog</h5>
                                </div>
								<?php if ($BacklogITCAN <> '0'){ ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=2&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-warning "><?php echo $BacklogITCAN; ?></div>
                                    <p class="notify-details">CAN<small class="text-muted">Canada's IT Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogITBRY <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=245&p=1&l=14&s=0" class="dropdown-item notify-item ">
                                    <div class="notify-icon bg-flatgreenalt3"><?php echo $BacklogITBRY; ?></div>
                                    <p class="notify-details">BRY<small class="text-muted">Bryan's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITEXT <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=10&s=0" class="dropdown-item notify-item ">
                                    <div class="notify-icon bg-flatbrown"><?php echo $BacklogITEXT; ?></div>
                                    <p class="notify-details">EXT<small class="text-muted">External's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITIND <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=8&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-primary"><?php echo $BacklogITIND; ?></div>
                                    <p class="notify-details">IND<small class="text-muted">Indiana's IT Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogITMEX <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=6&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-purple"><?php echo $BacklogITMEX; ?></div>
                                    <p class="notify-details">MEX<small class="text-muted">Mexico's IT Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogITNTC <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=5&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatorange"><?php echo $BacklogITNTC; ?></div>
                                    <p class="notify-details">NTC<small class="text-muted">Tech Center's IT Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogITOH <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=9&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatpurple"><?php echo $BacklogITOH; ?></div>
                                    <p class="notify-details">OH<small class="text-muted">Ohio's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITPAU <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=245&p=1&l=15&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatpurplealt1"><?php echo $BacklogITPAU; ?></div>
                                    <p class="notify-details">PAU<small class="text-muted">Paulding's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITNTA <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=16&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatorangealt1"><?php echo $BacklogITNTA; ?></div>
                                    <p class="notify-details">NTA<small class="text-muted">Ridge Tooling and Automation's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITRVC <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=245&p=1&l=13&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatbluealt1"><?php echo $BacklogITRVC; ?></div>
                                    <p class="notify-details">RVC<small class="text-muted">Ridgeville Corner's Baklog</small></p>
                                </a>
								<?php }
								if ($BacklogITSS <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=11&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatgrey"><?php echo $BacklogITSS; ?></div>
                                    <p class="notify-details">SS<small class="text-muted">Shared ITrvices's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITTNN1 <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=4&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatbluegreen"><?php echo $BacklogITTNN1; ?></div>
                                    <p class="notify-details">TNN1<small class="text-muted">TennesITe North's IT Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogITTNN2<> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=3&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatred"><?php echo $BacklogITTNN2; ?></div>
                                    <p class="notify-details">TNN2<small class="text-muted">TennesITe North 2's IT Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogITTNS <> '0') { ?>
                                <a href="/scp/tickets.php?queue=245&p=1&l=7&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatgreen"><?php echo $BacklogITTNS; ?></div>
                                    <p class="notify-details">TNS<small class="text-muted">TenneITe South's IT Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogITVIP <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=245&p=1&l=12&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-vipred"><?php echo $BacklogITVIP; ?></div>
                                    <p class="notify-details">VIP<small class="text-muted">VIP's IT Backlog</small></p>
                                </a>
								<?php } ?>
                                      
                                <!-- All-->
                                <a href="tickets.php?queue=245&p=1&l=0&s=0" class="dropdown-item notify-item notify-all">
                                    View All
                                </a>

                            </div>
                        </li>
						<li class="list-inline-item dropdown notification-list mr-0" style="max-width: 16px;">
                            <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false"  title="<?php echo __(' SE Backlog'); ?>"> 
                                <i class=" se noti-icon"></i>
                                <span class="badge badge-danger se-noti-icon-badge"><?php echo $BacklogSETotal; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-arrow dropdown-menu-lg" aria-labelledby="Preview">
                                <!-- item-->
                                <div class="dropdown-item noti-title">
                                    <h5 class="font-16"><a href="tickets.php?queue=246&p=1&l=0&s=0"><span class="badge badge-danger float-right"><?php echo $BacklogSETotal; ?></span></a> SE Backlog</h5>
                                </div>
								<?php if ($BacklogSECAN <> '0'){ ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=2&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-warning "><?php echo $BacklogSECAN; ?></div>
                                    <p class="notify-details">CAN<small class="text-muted">Canada's SE Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogSEBRY <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=246&p=1&l=14&s=0" class="dropdown-item notify-item ">
                                    <div class="notify-icon bg-flatgreenalt3"><?php echo $BacklogSEBRY; ?></div>
                                    <p class="notify-details">BRY<small class="text-muted">Bryan's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSEEXT <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=10&s=0" class="dropdown-item notify-item ">
                                    <div class="notify-icon bg-flatbrown"><?php echo $BacklogSEEXT; ?></div>
                                    <p class="notify-details">EXT<small class="text-muted">External's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSEIND <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=8&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-primary"><?php echo $BacklogSEIND; ?></div>
                                    <p class="notify-details">IND<small class="text-muted">Indiana's SE Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogSEMEX <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=6&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-purple"><?php echo $BacklogSEMEX; ?></div>
                                    <p class="notify-details">MEX<small class="text-muted">Mexico's SE Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogSENTC <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=5&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatorange"><?php echo $BacklogSENTC; ?></div>
                                    <p class="notify-details">NTC<small class="text-muted">Tech Center's SE Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogSEOH <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=9&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatpurple"><?php echo $BacklogSEOH; ?></div>
                                    <p class="notify-details">OH<small class="text-muted">Ohio's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSEPAU <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=246&p=1&l=15&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatpurplealt1"><?php echo $BacklogSEPAU; ?></div>
                                    <p class="notify-details">PAU<small class="text-muted">Paulding's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSENTA <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=16&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatorangealt1"><?php echo $BacklogSENTA; ?></div>
                                    <p class="notify-details">NTA<small class="text-muted">Ridge Tooling and Automation's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSERVC <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=246&p=1&l=13&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatbluealt1"><?php echo $BacklogSERVC; ?></div>
                                    <p class="notify-details">RVC<small class="text-muted">Ridgeville Corner's Baklog</small></p>
                                </a>
								<?php }
								if ($BacklogSESS <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=11&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatgrey"><?php echo $BacklogSESS; ?></div>
                                    <p class="notify-details">SS<small class="text-muted">Shared Services's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSETNN1 <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=4&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatbluegreen"><?php echo $BacklogSETNN1; ?></div>
                                    <p class="notify-details">TNN1<small class="text-muted">Tennessee North's SE Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogSETNN2<> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=3&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatred"><?php echo $BacklogSETNN2; ?></div>
                                    <p class="notify-details">TNN2<small class="text-muted">Tennessee North 2's SE Backlog</small></p>
                                </a>
								<?php } 
								if ($BacklogSETNS <> '0') { ?>
                                <a href="/scp/tickets.php?queue=246&p=1&l=7&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-flatgreen"><?php echo $BacklogSETNS; ?></div>
                                    <p class="notify-details">TNS<small class="text-muted">Tennesee South's SE Backlog</small></p>
                                </a>
								<?php }
								if ($BacklogSEVIP <> '0') { ?>
                                 <a href="/scp/tickets.php?queue=246&p=1&l=12&s=0" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-vipred"><?php echo $BacklogSEVIP; ?></div>
                                    <p class="notify-details">VIP<small class="text-muted">VIP's SE Backlog</small></p>
                                </a>
								<?php } ?>
                                
                                

                                <!-- All-->
                                <a href="tickets.php?queue=246&p=1&l=0&s=0" class="dropdown-item notify-item notify-all">
                                    View All
                                </a>

                            </div>
                        </li>
                        <li class="list-inline-item dropdown notification-list  mr-0">
                            <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false" title="<?php echo __('Unassigned Tickets'); ?>">
                                <i class="mdi mdi-ticket-account noti-icon"></i>
                                <span class="badge badge-primary noti-icon-badge"><?php echo $UnassignedTickets; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-arrow dropdown-menu-xlg" aria-labelledby="Preview">
                               
                               <!-- item-->
                                <div class="dropdown-item noti-title">
                                    <h5 class="font-16"><a href="tickets.php?queue=3&p=1&l=0&s=1"><span class="badge badge-primary float-right"><?php echo $UnassignedTickets; ?></span></a>Unassigned</h5>
                                </div>

                            <?php
                            $UnassignedTicket = Ticket::objects()
                                ->filter(array('status_id' => '1')) //Awaiting Submitter Reply
                                ->filter(array('topic_id__ne' => '12')) //open issue
                                ->filter(array('topic_id__ne' => '14')); //suggestion
                                
                                $r = 0;
                                foreach ($UnassignedTicket as $cUnassignedTicket) { 
                                    $entryTypes = ThreadEntry::getTypes();
                                    $entries = $cUnassignedTicket->getThread()->getEntries();
                                    $r++;
                                    if ($r == 9){break;}
                                    $i = 0;
                                    foreach ($entries as $entry) {
                                        if ($i == 1){break;}
                                        $ruser = $entry->getUser() ?: $entry->getStaff();
                                        $name = $ruser ? $ruser->getName() : $entry->poster;
                                        $i++;
                                    ?>

                                        <a href="tickets.php?id=<?php echo $cUnassignedTicket->ticket_id;?>" class="dropdown-item notify-item">
                                            <div class="notify-icon bg-primary"><i class="mdi mdi-comment-account"></i></div>
                                            <p class="notify-details"><?php echo $cUnassignedTicket->{user}->name;?></p> 
                                            <p class="notify-details"><?php echo Format::htmlchars($cUnassignedTicket->getSubject());?> 
                                            <small class="text-muted truncate"><?php //echo $entry->getBody()->toHtml(); ?>  </small>                                  
                                            <small class="text-muted"><?php echo Format::datetime($entry->created);?></small></p>
                                        </a>
                                        <?php }
                                } 
                                if ($r > 8) {
                                ?>                               
                                <!-- All-->
                                <a href="tickets.php?queue=3&p=1&l=0&s=1" class="dropdown-item notify-item notify-all">
                                    View All
                                </a>
                                 <?php } ?> 
                            </div>
                        </li>
                        <li class="list-inline-item notification-list mr-0 translation-link">
                            <a href="/" class="nav-link waves-light waves-effect english" id="english" data-lang="English" style="display:none;"><span class="flag flag-us" title="English" alt="English" class="notranslate" ></span></a>
                            <a href="/" class="nav-link waves-light waves-effect spanish" id="spanish" data-lang="Spanish"><span class="flag flag-mx" title="Spanish" alt="Spanish" class="notranslate" ></span></a>
                            <div id="google_translate_element"  style="display: none"></div>
                        </li>
                        
                        <li class="list-inline-item dropdown notification-list  mr-0">
                            <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false">
                                 <i class="fa fa-user-o" style="font-size: 16px;"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right profile-dropdown " aria-labelledby="Preview">
                               <!-- item-->
                               <?php
                                if($thisstaff->isAdmin() && !defined('ADMINPAGE')) { ?>
                               <a  class="dropdown-item notify-item" href="<?php echo ROOT_PATH ?>scp/admin.php"><i class="mdi mdi-settings"></i> <?php echo __('Admin Panel'); ?></a>
                                <?php }else{ 
                                 if ($thisstaff->isAdmin()) {?>
                                
                               <a  class="dropdown-item notify-item" href="<?php echo ROOT_PATH ?>scp/index.php"><i class="mdi mdi-account-box-outline"></i><?php echo __('Agent Panel'); ?></a>
                                 <?php }} ?>
                                <!-- item-->
                                <a  class="dropdown-item notify-item" href="<?php echo ROOT_PATH ?>scp/profile.php"> <i class="mdi mdi-account-star-variant"></i> <?php echo __('Profile'); ?></a>
                                
                                <!-- item-->    
                                <a  class="dropdown-item notify-item" href="<?php echo ROOT_PATH ?>scp/logout.php?auth=<?php echo $ost->getLinkToken(); ?>"> <i class="mdi mdi-logout"></i> Log Out</a>
                                
                            </div>
                        </li>

    
</ul>
<script type="text/javascript">
  function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en', includedLanguages: 'en,es', layout: google.translate.TranslateElement.InlineLayout.SIMPLE, autoDisplay: false, multilanguagePage: true}, 'google_translate_element'); }
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" type="text/javascript"></script><!-- Flag click handler -->
<script type="text/javascript">
    $('.translation-link a').click(function() {
      var lang = $(this).data('lang');
      var $frame = $('.goog-te-menu-frame:first');
      switch (lang){
          case "English": 
            
            document.getElementById('spanish').style.display = "inherit";
            document.getElementById('english').style.display = "none";
            break;
           case "Spanish": 
           
            document.getElementById('spanish').style.display = "none";
            document.getElementById('english').style.display = "inherit";
            break;
          
      }

      if (!$frame.size()) {
        alert("Error: Could not find Google translate frame.");
        return false;
      }
      $('.goog-te-menu-frame:first').contents().find('.goog-te-menu2-item span.text').each(function(){ if( $(this).html() == lang ) $(this).click(); });
      return false;
      
      
  
    });
</script>                   
                    <ul class="list-inline menu-left mb-0">
                                             <li class="float-left">
                            
                            </button>
                        </li>
                        <li class="hide-phone app-search">
                            
                        </li>
                    </ul>
     
                </nav>
                
            </div>
            <!-- Top Bar End -->


            <!-- ========== Left Sidebar Start ========== -->

            <div class="left side-menu">
                <div class="sidebar-inner slimscrollleft">
                    <!--- Divider -->
                    <div id="sidebar-menu">
                        <ul>
                        <?php include STAFFINC_DIR . "sidebar.inc.php"; ?>
                        
                        </ul>

                        <div class="clearfix"></div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <!-- Left Sidebar End -->
            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->                      
            <div class="content-page">
                <!-- Start content -->
                <div class="content">
                    <div class="container">
					
					<?php if ($UnassignedTickets > 0 ) { ?>
					
					<div class="alert alert-secondary m-b-30" role="alert">
                                        <i class="fa fa-info-circle" aria-hidden="true"></i> There are currently <span class="badge badge-primary"><?php echo $UnassignedTickets; ?></span>  unassigned tickets.
                    </div>
					
					<?php }
					
					if ($BacklogTotal >= 40 && $BacklogTotal <= 50) { ?>
					
					<div class="alert alert-warning m-b-30" role="alert">
                                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i> Backlog is currently <span class="badge badge-warning"><?php echo $BacklogTotal; ?></span>  within 5 of the established target of <span class="badge badge-success">45</span>.
                    </div>
					
					<?php }
					
					if ($BacklogTotal > 50) { ?>
					
					<div class="alert alert-danger m-b-30" role="alert">
                                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Backlog is currently <span class="badge badge-danger"><?php echo $BacklogTotal; ?></span> is greater than 5 above the established target of <span class="badge badge-success">45</span>.
                    </div>
					
					<?php }

					if ($BacklogTotal < 40) { ?>
					
					<div class="alert alert-success m-b-30" role="alert">
                                        <i class="fa fa-check-square" aria-hidden="true"></i> Backlog is currently <span class="badge badge-success"><?php echo $BacklogTotal; ?></span> is greater than 5 below the established target of <span class="badge badge-success">45
                    </div>
			
					<?php } ?>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/moment.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/footable.js"></script>
<script>
    $.busyLoadFull("show",  { 
    text: "LOADING ...",
    textColor: "#c82333",
    color: "#c82333",
    background: "rgba(0, 0, 0, 0.3)"
});
 </script>
               