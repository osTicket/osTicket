<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('Merge settings.'); ?></em>
            </th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td width="160"><?php echo __('On merge'); ?>:</td>
            <td>
                <input type="checkbox" name="copy_recipients" <?php
echo $config['copy_recipients'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Copy owner and collaborators'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#copy_recipients"></i>
            </td>
        </tr>
        <tr>
            <td width="160" rowspan="2"><?php echo __('Combine thread'); ?>:</td>
            <td>
                <input type="checkbox" name="combine_thread_staff" <?php
echo $config['combine_thread_staff'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Staff page'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#combine_thread_staff"></i>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="combine_thread_client" <?php
echo $config['combine_thread_client'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Client page'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#combine_thread_client"></i>
                </div>
            </td>
        </tr>
        <tr>
            <td width="160"><?php echo __('Redirect'); ?>:</td>
            <td>
                <input type="checkbox" name="redirect_child_ticket" <?php
echo $config['redirect_child_ticket'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Child ticket'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#redirect_child_ticket"></i>
            </td>
        </tr>
        <tr>
            <td width="180" class="required">
                <?php echo __('Child status'); ?>:
            </td>
            <td>
                <span>
                <select name="default_status_child">
                <?php
                $criteria = array('states' => array('closed'));
                foreach (TicketStatusList::getStatuses($criteria) as $status) {
                    $name = $status->getName();
                    if (!($isenabled = $status->isEnabled()))
                        $name.=' '.__('(disabled)');

                    echo sprintf('<option value="%d" %s %s>%s</option>',
                            $status->getId(),
                            ($config['default_status_child'] ==
                             $status->getId() && $isenabled)
                             ? 'selected="selected"' : '',
                             $isenabled ? '' : 'disabled="disabled"',
                             $name
                            );
                }
                ?>
                </select>
                &nbsp;
                <span class="error">*&nbsp;<?php echo $errors['default_status_child']; ?></span>
                <i class="help-tip icon-question-sign" href="#default_status_child"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="160"><?php echo __('Permalock'); ?>:</td>
            <td>
                <input type="checkbox" name="permalock_child_ticket" <?php
echo $config['permalock_child_ticket'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Child ticket'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#permalock_child_ticket"></i>
            </td>
        </tr>
    <tr>
            <td width="160"><?php echo __('Duplicate button'); ?>:</td>
            <td>
                <input type="checkbox" name="duplicate_button" <?php
echo $config['duplicate_button'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Available'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#duplicate_button"></i>
            </td>
        </tr>
    </tbody>
</table>
