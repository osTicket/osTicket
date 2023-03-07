<?php
$token = null;
if (($cred = $account->getCredentials($auth)))
    $token = $cred->getAccessToken($account->getConfigSignature());
// Warnings
 if ($account->getAuthBk() && strcmp($auth, $account->getAuthBk()))
    $form->setNotice(
            __('Changing Oauth2 provider will overwrite existing credentials'));
elseif ($account->getAuthBk())
    $form->setNotice(__('Any changes will require re-authorization'));

$info = $account->getOAuth2ConfigInfo();
$info = Format::htmlchars(($errors && $_POST)
        ? array_merge($info, $_POST) : $info, true);
$action = sprintf('#email/%d/auth/config/%s/%s',
        $email->getId(), $type, $auth);
$addr = $account->getEmail()->email;
?>
<h3><?php echo __('OAuth2 Authorization'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<hr/>
<?php
if (isset($errors['err'])) {
    echo sprintf('<p id="msg_error">%s</p>', $errors['err']);
} elseif (isset($sys['warning'])) {
    echo sprintf('<p id="msg_warning">%s</p>', $sys['warning']);
} elseif (isset($sys['msg'])) {
    echo sprintf('<p id="msg_notice">%s</p>', $sys['msg']);
} ?>
<form method="post" action="<?php echo $action; ?>">
<ul class="clean tabs" id="oauth-tabs">
    <li class="active"><a href="#info">
        <i class="icon-info-sign"></i> <?php echo __('Info'); ?></a></li>
    <li><a href="#idp">
        <i class="icon-cog"></i> <?php echo sprintf('%s %s',
                'IdP', __('Config')); ?></a></li>
    <?php
    if ($token) { ?>
    <li><a href="#token">
        <i class="icon-key"></i> <?php echo __('Token'); ?></a></li>
    <?php
    } ?>
</ul>
<div id="oauth-tabs_container">
    <div id="info" class="tab_content">
      <?php
      if (!$info['isactive'])
        echo sprintf('<p id="msg_warning">%s</p>',
                __('OAuth2 Instance is Disabled'));
        ?>
        <table class="form_table" width="100%" border="0" cellspacing="0" cellpadding="2">
        <thead>
            <tr>
                <th colspan="2">
                    <em><?php echo __('General Settings'); ?></em>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="180" class="required"><?php echo __('Status'); ?>:</td>
                <td><select name="isactive">
                    <?php
                    foreach (array(1 => __('Enabled'), 0 => __('Disabled')) as $key => $desc) { ?>
                    <option value="<?php echo $key; ?>" <?php
                        if ($key == $info['isactive']) echo 'selected="selected"';
                        ?>><?php echo $desc; ?></option>
                    <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td width="180" class="required"><?php echo __('Name'); ?>:</td>
                <td>
                    <input size="50" type="text" autofocus
                        name="name"
                        value="<?php echo $info['name']; ?>"/>
                    <span class="error">*<br/> <?php echo  $errors['name']; ?></span>
                </td>
            </tr>
            <tr>
                <td width="180"><b><?php echo __('Email Address'); ?>:</b></td>
                <td>
                    <input size="35" type="text" autofocus
                        name="name"
                        disabled="disabled"
                        value="<?php echo $addr; ?>"/>&nbsp;
                    <input type="checkbox" name="strict_matching"
                        <?php if ($info['strict_matching']) echo 'checked="checked"'; ?>>
                    &nbsp;<?php echo __('Strict Matching'); ?>
                    <i class="help-tip icon-question-sign" href="#strict_matching"></i>
                    <span class="error"><br/> <?php echo $errors['name']; ?></span>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="7">
                    <em><strong><?php echo __('Internal Notes'); ?>:</strong>
                    <?php echo __("Instance description and notes"); ?></em>
                </th>
            </tr>
            <tr>
                <td colspan="7"><textarea name="notes" class="richtext no-bar"
                    rows="6" cols="80"><?php
                    echo $info['notes']; ?></textarea>
                </td>
            </tr>
        </tbody>
        </table>
    </div>
    <div id="idp" class="tab_content hidden">
    <?php
    echo $form->asTable();
    ?>
    </div>
    <?php
    if ($token) { ?>
    <div id="token" class="tab_content hidden" style="margin:2px;" >
        <?php
        if ($token->hasExpired()) {
             echo sprintf('<p id="msg_warning">%s</p>',
                     __('Expired Access Token gets auto-refreshed on use'));
        }
        ?>
        <a class="red button action-button pull-right" style="margin-bottom:8px;"
            id="token-delete" data-toggle="tooltip"
            href="<?php echo sprintf('#email/%d/auth/config/%s/delete', $email->getId(), $type); ?>"
            title="Delete" data-original-title="Delete">
                <i class="icon-trash"></i>&nbsp;<?php echo __('Delete Token'); ?>
        </a>
        <div class="clear"></div>
        <table class="form_table" width="100%" border="0" cellspacing="0" cellpadding="2">
        <thead>
            <tr>
                <th colspan="2">
                    <em><?php echo __('Token Information'); ?></em>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="180"><?php echo __('Access Token'); ?>:</td>
                <td><?php echo Format::shroud($token->getAccessToken(), 2,
                        40); ?></td>
            </tr>
            <tr>
                <td><?php echo __('Expires'); ?>:</td>
                <td style="color:<?php echo $token->isExpired()
                ? 'red' : 'green'; ?>;"><?php echo
                Format::datetime($token->getExpires(), false); ?></td>
            </tr>
            <tr>
                <td><?php echo __('Refresh Token'); ?>:</td>
                <td><?php echo  Format::shroud($token->getRefreshToken(), 2,
                        40); ?></td>
            </tr>
            <tr>
                <td><?php echo __('Resource Owner'); ?>:</td>
                <td><?php echo $token->getResourceOwner(); ?></td>
            </tr>
            <tr>
                <td><?php echo __('Config Signature'); ?>:</td>
                <td><?php echo  $token->getConfigSignature(); ?></td>
            </tr>
        </tbody>
        </table>
     </div>
     <?php
     } ?>
</div>
<hr/>
<p class="full-width">
    <span class="buttons" style="float:left">
        <input type="button" name="cancel" class="close" value="<?php echo
        __('Cancel'); ?>">
    </span>
    <span class="buttons" style="float:right">
        <input type="submit" value="<?php echo __('Submit'); ?>">
    </span>
</p>
</form>
<script type="text/javascript">
$(function() {
    $('#oauth-tabs_container').on('click', 'a#token-delete', function(e) {
        e.preventDefault();
        if (confirm(__('Are you sure?'))) {
            $.ajax({
              url: 'ajax.php/' + $(this).attr('href').substr(1),
              type: 'POST',
              success: function(json) {
                  // Remove the Token tab completely
                  $('#popup a[href="#token"]').parent().remove();
                  $('#popup div#token').remove();
                  // Add success banner
                  $('#popup form').before('<div id="msg_notice">'+json+'</div>');
                  // Load the IdP tab
                  $('#popup a[href="#idp"]').click();
              },
              error: function(json) {
                  // Add error banner
                  $('#popup form').before('<div id="msg_error">'+json.responseText+'</div>');
              }
            });
        }
        return false;
    });
});
</script>
