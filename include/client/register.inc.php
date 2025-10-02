<?php
$info = $_POST;
if (!isset($info['timezone']))
    $info += array(
        'backend' => null,
    );
if (isset($user) && $user instanceof ClientCreateRequest) {
    $bk = $user->getBackend();
    $info = array_merge($info, array(
        'backend' => $bk->getBkId(),
        'username' => $user->getUsername(),
    ));
}
$info = Format::htmlchars(($errors && $_POST) ? $_POST : $info);

?>
<h1 class="alg-text-h1 text-dark text-center"><?php echo __('Account Registration'); ?></h1>
<p class="alg-text-p text-center"><?php echo __(
                                        'Use the forms below to create or update the information we have on file for your account'
                                    ); ?>
</p>
<div class="alg-container">
    <form action="account.php" method="post" class="d-flex flex-column justify-content-center align-items-center">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="<?php echo Format::htmlchars($_REQUEST['do']
                                                    ?: ($info['backend'] ? 'import' : 'create')); ?>" />
        <div class=" alg-rounded-small alg-bg-background-100 px-2 px-md-5 py-2 pb-3 d-flex flex-column justify-content-center align-items-center" style="box-shadow: 0 4px 30px 0 rgba(30, 30, 58, 0.25); ">

            <table class="registerTable">
                <tbody>
                    <?php
                    $cf = $user_form ?: UserForm::getInstance();
                    $cf->render(array('staff' => false, 'mode' => 'create'));
                    ?>
                    <tr>

                        <td>

                            <div>
                                <h3 class="mt-4"><?php echo __('Preferences'); ?></h3>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php echo __('Time Zone'); ?>:
                        </td>
                    </tr>
                    <tr>
                        <td class="selectStyling">
                            <?php
                            $TZ_NAME = 'timezone';
                            $TZ_TIMEZONE = $info['timezone'];
                            include INCLUDE_DIR . 'staff/templates/timezone.tmpl.php'; ?>
                            <div class="error"><?php echo $errors['timezone']; ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div>
                                <hr style="border:1px solid black">
                                <h3 class="mb-4"><?php echo __('Access Credentials'); ?></h3>
                            </div>
                        </td>
                    </tr>
                    <?php if ($info['backend']) { ?>
                        <tr>
                            <td>
                                <?php echo __('Login With'); ?>:
                            </td>
                            <td>
                                <input type="hidden" class="form-control" name="backend" value="<?php echo $info['backend']; ?>" />
                                <input type="hidden" name="username" value="<?php echo $info['username']; ?>" />
                                <?php foreach (UserAuthenticationBackend::allRegistered() as $bk) {
                                    if ($bk->getBkId() == $info['backend']) {
                                        echo $bk->getName();
                                        break;
                                    }
                                } ?>
                            </td>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <td style="width: 400px;" class="">
                                <span class=""> <?php echo __('Create a Password'); ?>:</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="password" class="inputRegister mt-3 w-100" name="passwd1" maxlength="128" value="<?php echo $info['passwd1']; ?>">
                                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd1']; ?></span>
                            </td>
                        </tr>
                        <tr colspan="2"></tr>
                        <tr>
                            <td class="d-flex ">
                                <span class="mt-3"><?php echo __('Confirm New Password'); ?>:</span>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="password" class="inputRegister mt-3 w-100" name="passwd2" maxlength="128" value="<?php echo $info['passwd2']; ?>">
                                &nbsp;<span class="error">&nbsp;<?php echo $errors['passwd2']; ?></span>
                            </td>
                        </tr>
                        <tr>

                        </tr>
                    <?php } ?>

                </tbody>

            </table>
            <p style="text-align: center;" class="mt-3">
                <input type="submit" class="px-5 py-2 alg-bg-secondary-50 text-white" style="border-radius: 20px;" value="<?php echo __('Register'); ?>" />
                <input type="button" class="px-5 py-2 mt-sm-0 mt-2 text-white alg-bg-secondary-100" style="border-radius: 20px;" value="<?php echo __('Cancel'); ?>" onclick="javascript:
        window.location.href='index.php';" />
            </p>
        </div>


    </form>
</div>
<?php if (!isset($info['timezone'])) { ?>
    <!-- Auto detect client's timezone where possible -->
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jstz.min.js?0375576"></script>
    <script type="text/javascript">
        $(function() {
            var zone = jstz.determine();
            $('#timezone-dropdown').val(zone.name()).trigger('change');
        });
    </script>
<?php }
