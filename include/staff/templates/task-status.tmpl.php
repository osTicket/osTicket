<?php
global $cfg;

if (!$info[':title'])
    $info[':title'] = __('Change Tasks Status');

?>
<h3><?php echo $info[':title']; ?></h3>
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

$action = $info[':action'] ?: ('#tasks/mass/'. $action);
?>
<div style="display:block; margin:5px;">
    <form method="post" name="status" id="status"
        action="<?php echo $action; ?>"
        class="mass-action">
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
                <tr>
                    <td colspan=2>
                        <span>
                            <strong><?php echo __('Status') ?>:&nbsp;</strong>
                            <select name="status">
                            <?php
                            $statuses = array(
                                    'open' => __('Open'),
                                    'closed' => __('Closed'));

                            if (!$info['status'])
                                echo '<option value=""> '. __('Select One')
                                .' </option>';
                            foreach ($statuses as $k => $status) {
                                echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,
                                        ($info['status'] == $k)
                                         ? 'selected="selected"' : '',
                                        $status
                                        );
                            }
                            ?>
                            </select>
                            <font class="error">*&nbsp;<?php echo
                            $errors['status']; ?></font>
                        </span>
                    </td>
                </tr>
            </tbody>
            <tbody>
                <tr>
                    <td colspan="2">
                        <?php
                        $placeholder = $info[':placeholder'] ?: __('Optional reason for status change (internal note)');
                        ?>
                        <textarea name="comments" id="comments"
                            cols="50" rows="3" wrap="soft" style="width:100%"
                            class="<?php if ($cfg->isHtmlThreadEnabled()) echo 'richtext';
                            ?> no-bar"
                            placeholder="<?php echo $placeholder; ?>"><?php
                            echo $info['comments']; ?></textarea>
                    </td>
                </tr>
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
                echo $verb ?: __('Submit'); ?>">
            </span>
         </p>
    </form>
</div>
<div class="clear"></div>
