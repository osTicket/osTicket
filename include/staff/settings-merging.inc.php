<table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><?php echo __('Merging settings.'); ?></em>
            </th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td width="160"><?php echo __('On merge'); ?>:</td>
            <td>
                <input type="checkbox" name="merging_bring_owners" <?php
echo $config['merging_bring_owners'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Copy owner and collaborators'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#on_merge"></i>
            </td>
        </tr>
        <tr>
            <td width="160" rowspan="2"><?php echo __('Combine thread'); ?>:</td>
            <td>
                <input type="checkbox" name="merging_combine_thread_staff" <?php
echo $config['merging_combine_thread_staff'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Staff page'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#combine_view_staff"></i>
            </td>
        </tr>
		<tr>
            <td>
                <input type="checkbox" name="merging_combine_thread_client" <?php
echo $config['merging_combine_thread_client'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Client page'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#combine_view_client"></i>
                </div>
            </td>
        </tr>
		<tr>
            <td width="160"><?php echo __('Redirect'); ?>:</td>
            <td>
                <input type="checkbox" name="merging_redirect" <?php
echo $config['merging_redirect'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Child ticket'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#merging_redirect"></i>
            </td>
        </tr>
		<tr>
            <td width="180" class="required">
                <?php echo __('Child status'); ?>:
            </td>
            <td>
                <span>
                <select name="merging_child_status">
                <?php
                $criteria = array('states' => array('closed'));
                foreach (TicketStatusList::getStatuses($criteria) as $status) {
                    $name = $status->getName();
                    if (!($isenabled = $status->isEnabled()))
                        $name.=' '.__('(disabled)');

                    echo sprintf('<option value="%d" %s %s>%s</option>',
                            $status->getId(),
                            ($config['merging_child_status'] ==
                             $status->getId() && $isenabled)
                             ? 'selected="selected"' : '',
                             $isenabled ? '' : 'disabled="disabled"',
                             $name
                            );
                }
                ?>
                </select>
                &nbsp;
                <span class="error">*&nbsp;<?php echo $errors['merging_child_status']; ?></span>
                <i class="help-tip icon-question-sign" href="#merging_child_status"></i>
                </span>
            </td>
        </tr>
        <tr>
            <td width="160"><?php echo __('Permalock'); ?>:</td>
            <td>
                <input type="checkbox" name="merging_permalock" <?php
echo $config['merging_permalock'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Child ticket'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#merging_permalock"></i>
            </td>
        </tr>
	<tr>
            <td width="160"><?php echo __('Duplicate button'); ?>:</td>
            <td>
                <input type="checkbox" name="merging_duplicate_button" <?php
echo $config['merging_duplicate_button'] ? 'checked="checked"' : ''; ?>/>
                <?php echo __('Available'); ?>&nbsp;
                <i class="help-tip icon-question-sign" href="#merging_duplicate_button"></i>
            </td>
        </tr>
    </tbody>
</table>
