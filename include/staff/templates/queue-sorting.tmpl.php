<div class="tab-desc">
    <p><b>Manage Custom Sorting</b>
    <br>Add, and remove the fields in this list using the options below. Sorting is priortized in ascending order.</p>
</div>
<table class="table">
    <tbody>
        <tr>
            <td colspan="3" style="border-bottom:1px">
                <input type="text" name="name" value="" style="width:100%" placeholder="<?php echo __('Sort Criteria Title');?>" />
            </td>
        </tr>
    </tbody>
    <tbody class="sortable-rows ui-sortable">
        <tr style="display: table-row;">
            <td>
                <i class="faded-more icon-sort"></i>
                <span><?php echo __('Sort field 0'); ?></span>
            </td>
            <td>
                <select>
                    <option value="0">
                        <?php echo __('Ascending');?>
                    </option>
                    <option value="1">
                        <?php echo __('Descending');?>
                    </option>
                </select>
            </td>
            <td>
                <a href="#" class="pull-right drop-column" title="Delete"><i class="icon-trash"></i></a>
            </td>
        </tr>
        <tr style="display: table-row;">
            <td>
                <i class="faded-more icon-sort"></i>
                <span><?php echo __('Sort field 1'); ?></span>
            </td>
            <td>
                <select>
                    <option value="0">
                        <?php echo __('Ascending');?>
                    </option>
                    <option value="1">
                        <?php echo __('Descending');?>
                    </option>
                </select>
            </td>
            <td>
                <a href="#" class="pull-right drop-column" title="Delete"><i class="icon-trash"></i></a>
            </td>
        </tr>
    </tbody>
    <tbody>
        <tr class="header">
            <td colspan="3"></td>
        </tr>
        <tr>
            <td colspan="3" id="append-sort">
                <i class="icon-plus-sign"></i>
                <select id="add-sort">
                    <option value="">— Add Field —</option>
                </select>
                <button type="button" class="green button">Add</button>
            </td>
        </tr>
    </tbody>
</table>