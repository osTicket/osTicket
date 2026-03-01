<?php
$title = __('Manage 2FA Options');
if ($auth)
    $title = sprintf('%s %s %s', $auth->getName(), '2FA', __('Setup'));
?>
<h3 class="drag-handle"><?php echo $title; ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warning']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warning']);
} elseif ($info['notice']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['notice']);
} ?>
<div id="backends" <?php if ($auth) echo 'class="hidden"'; ?>>
<table class="table">
    <tbody>
      <?php
      foreach (Staff2FABackend::allRegistered() ?: array() as $bk) {
          $configuration = $staff->get2FAConfig($bk->getId());
          $isVerified = $configuration['verified'];
          $vclass = $isVerified ? 'verified' : 'unverified'; ?>
      <tr id="<?php echo $bk->getId(); ?>" class="2fa-type <?php echo $vclass; ?>">
        <td nowrap width="10px">
          <i class="faded-more <?php echo sprintf('icon-check-%s',
          $isVerified ? 'sign' : 'empty'); ?>"></i>
          <span data-name="label"></span>
        </td>
        <td width="300px">
            <a class="config2fa"
                href="<?php echo sprintf('#staff/%d/2fa/configure/%s',
                $staff->getId(), urlencode($bk->getId())); ?>"> <?php echo $bk->getName(); ?>
              </a>
              <div align="left" class="faded"><?php
              echo Format::htmlchars($bk->getDescription()); ?></div>
        </td>
      </tr>
      <?php
      } ?>
    </tbody>
 </table>
<hr>
</div>
<div id="backend" <?php if (!$auth) echo 'class="hidden"'; ?>>
<?php
if ($auth && $form) {
    if ($state == 'verify')
        $instruction = __('Enter the token sent to you and click Verify');
    else
        $instruction = __('Complete the form below and then click Next to verify the setup');
    ?>
 <div><?php echo Format::htmlchars($instruction); ?></div>
 <br>
<form class="bk" method="post" action="<?php echo sprintf('#staff/%d/2fa/configure/%s',
    $staff->getId(), $auth->getId()); ?>">
    <input type="hidden" name="state" value="<?php
        echo $state ?: 'validate'; ?>" />
    <?php
    echo csrf_token();
    include STAFFINC_DIR . 'templates/dynamic-form-simple.tmpl.php';
    ?>
    <br>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="close" value="<?php echo __('Cancel'); ?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php echo ($state == 'verify') ?
            __('Verify') : __('Next'); ?>">
        </span>
    </p>
 </form>
 <?php
} ?>
</div>
<div class="clear"></div>
<script type="text/javascript">
$(function() {
    var ids = $('.verified').map(function() {
        id2fa = $(this).attr('id');
        document.getElementById(id2fa).disabled=false;
    });

    $('a.config2fa').click( function(e) {
        e.preventDefault();
        if ($(this).attr('href').length > 1) {
            var url = 'ajax.php/'+$(this).attr('href').substr(1);
            $.dialog(url, [201, 204], function (xhr) {
                window.location.href = window.location.href;
            }, {
                onshow: function() { $('#user-search').focus(); }
            });
        } else {
            $('div#backends').hide();
            $('div#backend').fadeIn();
        }
        return false;
     });

    $(document).on('click', 'input.close', function (e) {
        e.preventDefault();
        alert('Alert');
        $('div#backend').hide();
        $('div#backends').fadeIn();
        return false;
     });

});
</script>
