<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$title = __('Two Factor Authentication');
$body = __('Please enter the verification code to verify your identity.');
?>
<h1><?php echo Format::display($title); ?></h1>
<p><?php echo Format::display($body); ?></p>
<form action="login.php" method="post" id="clientLogin">
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="2fa">
<div style="display:table-row">
    <div class="login-box">
    <strong><?php echo Format::htmlchars($errors['err']); ?></strong>
    <div>
        <?php
        if ($thisclient && ($acct = $thisclient->getAccount()) && ($auth = $acct->get2FABackend())) {
            $form = $auth->getInputForm($_POST);
        foreach ($form->getFields() as $field) {
                ?>
                <div>
                    <?php echo $field->render(array('placeholder' => $field->get('label'))); ?>
                </div>
                <?php
            }
        }
        ?>
    </div>
    <p>
        <input class="btn" type="submit" value="<?php echo __('Verify'); ?>">
    </p>
    </div>
</div>
</form>
