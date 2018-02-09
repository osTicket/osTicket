                    </div> <!-- end container -->
                </div><!-- end content -->
            </div>
            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
            <!-- Right Sidebar -->
            <div class="side-bar right-bar">
                <div class="">
                    <ul class="nav nav-tabs tabs-bordered nav-justified">
                        <li class="nav-item">
                            <a href="#home-2" class="nav-link active" data-toggle="tab" aria-expanded="false">
                                Activity
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#messages-2" class="nav-link" data-toggle="tab" aria-expanded="true">
                                Settings
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="home-2">
                        
                                         
                       </div>
                        <div class="tab-pane" id="messages-2">

                            <div class="row m-t-20">
                                <div class="col-8">
                                    <h5 class="m-0 font-15">Auto Refresh</h5>
                                    <p class="m-b-0 text-muted"><small>Keep up to date</small></p>
                                </div>
                                <div class="col-4 text-right">
                                    <input type="checkbox" checked data-plugin="switchery" data-color="#3bafda" data-size="small"/>
                                </div>
                            </div>

                            
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Right-bar -->

        </div> <!-- /Wrapper -->
        
    </div><!-- /pjax-container -->
<?php if (!isset($_SERVER['HTTP_X_PJAX'])) { ?>
    <div id="footer" style="display: none;">
        Copyright &copy; 2006-<?php echo date('Y'); ?>&nbsp;<?php echo (string) $ost->company ?: 'osTicket.com'; ?>&nbsp;All Rights Reserved.
    </div>
<?php
if(is_object($thisstaff) && $thisstaff->isStaff()) { ?>
    <div  style="background-color: #f5f5f5;">
        <!-- Do not remove <img src="autocron.php" alt="" width="1" height="1" border="0" /> or your auto cron will cease to function -->
        <img src="<?php echo ROOT_PATH; ?>scp/autocron.php" alt="" width="1" height="1" border="0"/>
        <!-- Do not remove <img src="autocron.php" alt="" width="1" height="1" border="0" /> or your auto cron will cease to function -->
    </div>
<?php
} ?>

<div id="overlay"></div>
<div id="loading">
    <i class="icon-spinner icon-spin icon-3x pull-left icon-light"></i>
    <h1><?php echo __('Loading ...');?></h1>
</div>
<div class="dialog draggable" style="display:none;" id="popup">
    <div id="popup-loading">
        <h1 style="margin-bottom: 20px;"><i class="icon-spinner icon-spin icon-large"></i>
        <?php echo __('Loading ...');?></h1>
    </div>
    <div class="body"></div>
</div>
<div style="display:none;" class="dialog" id="alert">
    <h3><i class="icon-warning-sign"></i> <span id="title"></span></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <div id="body" style="min-height: 20px;"></div>
    <hr style="margin-top:3em"/>
    <p class="full-width">
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('OK');?>" class="close btn btn-primary">
        </span>
     </p>
</div>
    <div class="clear"></div>

<script src="<?php echo ROOT_PATH; ?>scp/js/popper.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/scp.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.pjax.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-typeahead.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/bootstrap-datetimejs.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/tips.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js"></script>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/jquery.translatable.js"></script>
<!--<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/jquery.dropdown.js"></script>-->
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/fabric.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>scp/css/tooltip.css">
<script src="<?php echo ROOT_PATH; ?>scp/js/bootstrap.min.js"></script>

<script type="text/javascript" src="<?php echo ROOT_PATH; ?>scp/js/bootstrap-tooltip.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/detect.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/fastclick.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.slimscroll.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.blockUI.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.waypoints.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.counterup.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/waves.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/wow.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.nicescroll.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.scrollTo.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/switchery.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/notify.min.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/notify-metro.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.core.js"></script>
<script src="<?php echo ROOT_PATH; ?>scp/js/jquery.app.js"></script>

       
<script type="text/javascript">
    getConfig().resolve(<?php
        include INCLUDE_DIR . 'ajax.config.php';
        $api = new ConfigAjaxAPI();
        print $api->scp(false);
    ?>);

jQuery(document).ready(function($) {
                $('.counter').counterUp({
                    delay: 100,
                    time: 1200
                });
            });
</script>

</body>
</html>
<?php } # endif X_PJAX ?>
