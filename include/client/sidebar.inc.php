<?php if ($thisclient && $thisclient->isGuest()
    && $cfg->isClientRegistrationEnabled()) { ?>

<div id="msg_info">
    <div class="row">
    
    <div class="col">
    <i class="icon-compass icon-2x pull-left"></i>
    <strong><?php echo __('Looking for your other tickets?'); ?></strong> <a href="<?php echo ROOT_PATH; ?>login.php?e=<?php
        echo urlencode($thisclient->getEmail());
    ?>" style="text-decoration:underline"><?php echo __('Sign In'); ?></a></div>
    </div>
    <div class="row">
    <div class="col"></br>
    
    <?php echo sprintf(__('%s Register for an account %s for the best experience on our support system.'),
        '<a href="account.php?do=create" style="text-decoration:underline">','</a>'); ?>
    </div></div>
</div>
<?php } ?>

<?php if ($thisclient && !$thisclient->isGuest()
    && $cfg->isClientRegistrationEnabled()) { ?>

<li ><a class="waves-effect waves-primary" href="/tickets.php?a=search&status=open" ><i class=" ti-ticket"></i> My Open Tickets 

<span class="badge badge-primary badge-pill pull-right"><?php echo $thisclient->getNumOpenTickets();?></span>

</a> </li>
<li ><a class="waves-effect waves-primary" href="/tickets.php?a=search&status=closed" ><i class=" ti-ticket"></i> My Closed Tickets 
<span class="badge badge-primary badge-pill pull-right"><?php echo $thisclient->getNumClosedTickets();?></span>
</a> </li>
    <!--<li class=" has_sub ">
        <a class="waves-effect waves-primary" href="javascript:void(0);" ><i class="ti-user"></i>  <span class="menu-arrow"></span> Users </a> 
        <ul class="list-unstyled">
            <li><a href="/scp/directory.php" title="" id="nav1">Agent Directory</a></li>
            <li><a href="/scp/users.php" title="" id="nav0">User Directory</a></li>
            <li><a href="/scp/orgs.php" title="" id="nav1">Organizations</a></li>
        </ul>
    </li> -->
    
    <?php } ?>
    
   <li>
   <a class="waves-effect waves-primary" id="tickets-helptopic" data-placement="bottom" data-toggle="tooltip" title="" href="open.php" data-original-title="New Ticket" style="background-color: #28a745;color: #FFF;"><i class="fa fa-plus-square-o"></i> Open a New Ticket</a>
   </li>