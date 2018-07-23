<div class="row">
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading"><?php echo __('Forgot My Password'); ?></div>
            <div class="panel-body">
                <p>
                    <?php echo __(
                    'Enter your username or email address in the form below and press the <strong>Send Email</strong> button to have a password reset link sent to your email account on file.');
                    ?>
                </p>

                <form action="pwreset.php" method="post" id="clientLogin">
                    <div style="width:50%;display:inline-block">
                        <?php echo __(
                            'We have sent you a reset email to the email address you have on file for your account. If you do not receive the email or cannot reset your password, please submit a ticket to have your account unlocked.'
                        ); ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>