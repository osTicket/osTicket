<?php
global $cfg;

if (!$thread) return;

$form = $form ?: ReferralForm::instantiate($info);

?>
<h3 class="drag-handle"><?php echo $info[':title'] ?:  __('Refer'); ?></h3>
<b><a class="close" href="#"><i class="icon-remove-circle"></i></a></b>
<div class="clear"></div>
<hr/>
<?php
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} elseif ($info['notice']) {
   echo sprintf('<p id="msg_info"><i class="icon-info-sign"></i> %s</p>',
           $info['notice']);
}

$action = $info[':action'] ?: ('#');
$manage = (!$target);
?>
<ul class="tabs" id="referral">
    <li <?php echo $manage ? 'class="active"' : ''; ?>><a href="#referrals"
        ><i class="icon-list"></i>&nbsp;<?php
        echo sprintf('%s (%d)', __('Referrals'), $thread->getNumReferrals()); ?></a></li>
    <li <?php echo !$manage ? 'class="active"' : ''; ?>><a href="#refer"
        ><i class="icon-exchange"></i>&nbsp;<?php echo __('Refer'); ?></a></li>
</ul>
<div id="referral_container">
   <div class="tab_content <?php echo $manage ? 'hidden' : ''; ?>" id="refer" style="margin:5px;">
    <form class="mass-action" method="post"
        name="assign"
        id="<?php echo $form->getFormId(); ?>"
        action="<?php echo $action; ?>">
      <input type='hidden' name='do' value='refer'>
    <table width="100%">
        <?php
        if ($info[':extra']) {
            ?>
        <tbody>
            <tr><td colspan="2"><strong><?php echo $info[':extra'];
            ?></strong></td> </tr>
        </tbody>
        <?php
        }
       ?>
        <tbody>
            <tr><td colspan=2>
             <?php
             $options = array('template' => 'simple', 'form_id' => 'refer');
             $form->render($options);
             ?>
            </td> </tr>
        </tbody>
    </table>
    <hr>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="reset" value="<?php echo __('Reset'); ?>">
            <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php
            echo __('Refer'); ?>">
        </span>
     </p>
    </form>
    </div>
   <div class="tab_content <?php echo !$manage ? 'hidden' : ''; ?>" id="referrals" style="margin:5px;">
   <form class="mass-action" method="post"
    name="referrals"
    id="rf"
    action="<?php echo sprintf('#tickets/%d/referrals', $ticket->getId()); ?>">
     <input type='hidden' name='do' value='manage'>
    <table width="100%">
        <tbody>
           <?php
           if ($thread->referrals->count()) {
            foreach ($thread->referrals as $r) {
            ?>
            <tr>
                <td style="border-top: 1px solid #ddd;"> <?php echo  $r->display(); ?></td>
                <td style="border-top: 1px solid #ddd;">
                    <div style="position:relative">
                    <input type="hidden" name="referrals[]" value="<?php echo $r->getId(); ?>"/>
                    <div class="pull-right" style="right:2px;">
                        <a href="#" title="<?php echo __('clear'); ?>" onclick="javascript:
                            if (!confirm(__('You sure?')))
                            return false;
                            $(this).closest('td').find('input[name=\'referrals[]\']')
                                .val(function(i,v) { return '-'+ v; });
                            $(this).closest('tr').fadeOut(400, function() { $(this).hide(); });
                            $('input[type=submit], button[type=submit]',
                                    $(this).closest('form')).addClass('save pending');
                            return false;"><i class="icon-trash"></i></a>
                        </div>
                    </div>
                </td>
            </tr>
            <?php }
            } ?>
        </tbody>
    </table>
    <hr>
    <?php
    if ($thread->getNumReferrals()) {?>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" name="cancel" class="close"
            value="<?php echo __('Cancel'); ?>">
        </span>
        <span class="buttons pull-right">
            <input type="submit" value="<?php
            echo __('Save Changes'); ?>">
        </span>
     </p>
     <?php
    } ?>
    </form>
  </div>
</div>
<div class="clear"></div>
