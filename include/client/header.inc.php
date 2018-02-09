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
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/typeahead.css" media="screen">
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
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.easyui.min.js"></script>
    <link type="text/css" rel="stylesheet" href="./css/translatable.css"/>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>scp/css/accordian.css" media="all">
    
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
                                
                                <span class="mdi mdi-dots-horizontal noti-icon" title="Full Screen" alt="Full Screen"></span>
                   
                            </a>
                        </li>
                        
                 
<?php 
if (!$thisclient){
   ?> <li class="list-inline-item notification-list mr-0 translation-link">
                            <a href="/" class="nav-link waves-light waves-effect english" id="english" data-lang="English" style="display:none;"><span class="flag flag-us" title="English" alt="English" class="notranslate" ></span></a>
                            <a href="/" class="nav-link waves-light waves-effect spanish" id="spanish" data-lang="Spanish"><span class="flag flag-mx" title="Spanish" alt="Spanish" class="notranslate" ></span></a>
                            <div id="google_translate_element"  style="display: none"></div>
                        </li>
   <li class="list-inline-item notification-list hide-phone  mr-0">
                            <a class="nav-link waves-light waves-effect" href="login.php" >
                                <i class="mdi mdi-login noti-icon" title="Login" alt="Login"></i>
                            </a>
                        </li>
    
<?php } 
if ($thisclient && !$thisclient->isGuest()
    && $cfg->isClientRegistrationEnabled()) {
$ClientMyReplyTicket = Ticket::objects()
        ->filter(array('user_id' => $thisclient->getId())) //this staff
        ->filter(array('status_id' => '6')) //Awaiting Submitter Reply
        ->filter(array('topic_id__ne' => '12')) //open issue
        ->filter(array('topic_id__ne' => '14')) //suggestion
        ->aggregate(array('count' => SqlAggregate::COUNT('ticket_id')));
         
         foreach ($ClientMyReplyTicket as $cClientMyReplyTicket) { 
            $ClientMyReplyTickets = $cClientMyReplyTicket["count"];
} 
?>
                   <li class="list-inline-item dropdown notification-list  mr-0">
                            <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown" href="#" role="button"
                               aria-haspopup="false" aria-expanded="false" title="<?php echo __('Recent Replies'); ?>">
                               
                                <i class="mdi mdi-email noti-icon"></i>
                                <span class="badge badge-pink noti-icon-badge"><?php echo $ClientMyReplyTickets; ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-arrow dropdown-menu-xlg" aria-labelledby="Preview">
                               
                               <!-- item-->
                                <div class="dropdown-item noti-title">
                                    <h5 class="font-16"><span class="badge badge-danger float-right"><?php echo $ClientMyReplyTickets; ?></span>Recent Replies</h5>
                                </div>

                                 <?php
                                $MyTheirReplyTicket = Ticket::objects()
                                        ->filter(array('user_id' => $thisclient->getId())) //this staff
                                        ->filter(array('status_id' => '6')) //Awaiting Submitter Reply
                                        ->filter(array('topic_id__ne' => '12')) //open issue
                                        ->filter(array('topic_id__ne' => '14')); //suggestion
                                       
                                        
                                         foreach ($MyTheirReplyTicket as $cMyTheirReplyTicket) { 
                                            $entryTypes = ThreadEntry::getTypes();
                                            $entries = $cMyTheirReplyTicket->getThread()->getEntries();
                                            $r++;
                                            if ($r == 9){break;}
                                            $i = 0;
                                            foreach ($entries as $entry) {
                                                if ($i == 1){break;}
                                                $i = 0;
                                                $ruser = $entry->getUser() ?: $entry->getStaff();
                                                $name = $ruser ? $ruser->getName() : $entry->poster;
                                                $i++;
                                                ?>

                                                <a href="tickets.php?id=<?php echo $cMyTheirReplyTicket->ticket_id;?>#reply" class="dropdown-item notify-item">
                                    <div class="notify-icon bg-pink"><i class="mdi mdi-comment-account"></i></div>
                                    <p class="notify-details"><?php echo $cMyTheirReplyTicket->{user}->name;?></p> <p class="notify-details "><?php echo Format::htmlchars($cMyTheirReplyTicket->getSubject());?> 
                                
                                    <small class="text-muted"><?php echo Format::datetime($entry->created);?></small></p>
                                </a>
                                    <?php }
                                               
                                } 
                                
                                if ($r > 8) {
                                ?>                               
                                <!-- All-->
                                <a href="tickets.php?queue=37&p=1" class="dropdown-item notify-item notify-all">
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
                              
                               
                                <!-- item-->
                                <a  class="dropdown-item notify-item" href="<?php echo ROOT_PATH ?>profile.php"> <i class="mdi mdi-account-star-variant"></i> <?php echo __('Profile'); ?></a>
                                
                                <!-- item-->    
                                <a  class="dropdown-item notify-item" href="<?php echo ROOT_PATH ?>logout.php?auth=<?php echo $ost->getLinkToken(); ?>"> <i class="mdi mdi-logout"></i> Log Out</a>
                                
                            </div>
                        </li>
<?php } ?>  

    
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
                        <?php 
                        if ($thisclient){
                            include CLIENTINC_DIR."sidebar.inc.php"; 
                        } ?>
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
                
                 
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/moment.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/footable.js"></script>

               