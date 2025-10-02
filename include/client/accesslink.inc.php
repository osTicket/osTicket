<?php
if (!defined('OSTCLIENTINC')) die('Access Denied');

$email = Format::input($_POST['lemail'] ? $_POST['lemail'] : $_GET['e']);
$ticketid = Format::input($_POST['lticket'] ? $_POST['lticket'] : $_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = __("Email Access Link");
else
    $button = __("View Ticket");
?>
<h1 class="alg-text-h1 alg-text-dark text-center"><?php echo __('Check Ticket Status'); ?></h1>
<p class="alg-text-p text-center"><?php
                                    echo __('Please provide your email address and a ticket number.');
                                    if ($cfg->isClientEmailVerificationRequired())
                                        echo ' ' . __('An access link will be emailed to you.');
                                    else
                                        echo ' ' . __('This will sign you in to view your ticket.');
                                    ?></p>
<form action="login.php" method="post" id="clientLogin" class="" style="
    
  margin-top: 30px;
  padding: 0px;
  border: 0px solid #ccc;
  border-radius: 0px;
  box-shadow: inset 0 0px 0px rgba(0,0,0,0.0);
  background: none;
 ">
    <?php csrf_token(); ?>
    <div class="container">
        <div class="row" style="box-shadow: 0 4px 30px 0 rgba(30, 30, 58, 0.25);border-radius: 40px;
">
            <div class="alg-ticket-status-left col-md-6 bg-white d-flex flex-column justify-content-center px-5">
                <div class="">
                    <div><strong><?php echo Format::htmlchars($errors['login']); ?></strong></div>
                    <div class="form-group mt-4">
                        <label for="exampleInputPassword1" class="text-start alg-text-p">Email Address</label>
                        <input id="email" placeholder="<?php echo __('e.g. john.doe@osticket.com'); ?>" type="text" name="lemail" value="<?php echo $email; ?>" class="form-control ticket-input" style="width:100%;border-radius:10px">
                    </div>



                    <div class="form-group">
                        <label for="exampleInputPassword1" class="text-start alg-text-p">Password</label>
                        <input id="ticketno" type="text" name="lticket" placeholder="<?php echo __('e.g. 051243'); ?>" value="<?php echo $ticketid; ?>" class="form-control ticket-input" style="width:100%;border-radius:10px"></label>
                    </div>
                    <p>
                        <input class="btn alg-text-h3 py-2 mb-3 text-white  " style="width: 100%;border-radius:10px;background-color:#4CA771" type="submit" value="<?php echo $button; ?>">
                        <!-- start -->
                    <div class="text-center">
                        <?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
                            <?php echo __('Have an account with us?'); ?>
                            <a href="login.php"><?php echo __('Sign In'); ?></a><br>
                            <?php
                            if ($cfg->isClientRegistrationEnabled()) { ?>
                        <?php echo sprintf(
                                    __('or %s register for an account %s to access all your tickets.'),
                                    '<a href="account.php?do=create">',
                                    '</a>'
                                );
                            }
                        } ?>
                    </div>
                    <!-- end -->
                    </p>
                </div>
            </div>
            <div class="alg-ticket-status-right col-md-6 alg-bg-background-90 text-end">
                <div class="text-center">
                    <!-- this is the place that have a have and account option -->
                    <img src="../../images/ticket.png" alt="" srcset="" class="status-img mx-3 d-md-inline d-none">


                </div>
            </div>

        </div>


    </div>
</form>
<br>
<p class="text-center">
    <?php
    if (
        $cfg->getClientRegistrationMode() != 'disabled'
        || !$cfg->isClientLoginRequired()
    ) {
        echo sprintf(
            __("If this is your first time contacting us or you've lost the ticket number, please %s open a new ticket %s"),
            '<a href="open.php">',
            '</a>'
        );
    } ?>
</p>