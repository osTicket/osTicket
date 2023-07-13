<?php
$info= Format::htmlchars(($errors && $_POST) ? $_POST : array(), true);
?>
<h2>&nbsp;<?php
    echo sprintf('<a href="plugins.php">%s</a> <small> &gt; <a
            href="plugins.php?id=%d">%s</a> ( %s )</small>',
        __('Plugins'),
        $plugin->getId(),
        $plugin->getName(),
        $plugin->getStatus()
        );?>
</h2>
<ul class="clean tabs" id="plugin-tabs">
    <li class="active"><a href="#info">
        <i class="icon-plus"></i> <?php echo __('Plugin'); ?></a></li>
    <li><a href="#instances">
        <i class="icon-list"></i> <?php echo sprintf(__('Instances (%d)'),
                $plugin->getNumInstances()); ?></a></li>
</ul>
<div id="plugin-tabs_container">
<div id="info" class="tab_content">
    <form action="" method="post" class="save">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="update">
        <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
        <input type="hidden" name="id" value="<?php echo $plugin->getId(); ?>">
        <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
        <thead>
            <tr>
                <th colspan="2">
                    <em><?php echo __('Plugin Information'); ?></em>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="180"><?php echo __('Name'); ?>:</td>
                <td> <?php echo $plugin->getName(); ?> </td>
            </tr>
            <tr>
                <td width="180"><?php echo __('Version'); ?>:</td>
                <td> <?php echo $plugin->getVersion();
                if (!$plugin->isCompatible())
                   echo sprintf('<span style="padding-left:20px;color:red;">(%s
                           v%s+ %s)</span>',
                           'osTicket', $plugin->getosTicketVersion(), __('Required'));
                ?>
                </td>
            </tr>
            <tr>
                <td width="180"><?php echo __('Installed'); ?>:</td>
                <td> <?php echo  Format::datetime($plugin->getInstallDate()); ?></td>
            </tr>
        </tbody>
        <thead>
            <tr>
                <th colspan="2">
                    <em><?php echo __('Plugin Settings'); ?></em>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td width="180" class="required"><?php echo __('Status'); ?>:</td>
                <td><select name="isactive">
                    <?php
                    foreach (array(1 => __('Active'), 0 => __('Disabled')) as $key => $desc) { ?>
                    <option value="<?php echo $key; ?>" <?php
                        if ($key == $plugin->get('isactive')) echo 'selected="selected"';
                        ?>><?php echo $desc; ?></option>
                    <?php } ?>
                    </select>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <th colspan="7">
                    <em><strong><?php echo __('Internal Notes'); ?>:</strong>
                    <?php echo __("Plugin description and instructions"); ?></em>
                </th>
            </tr>
            <tr>
                <td colspan="7"><textarea name="notes" class="richtext no-bar"
                    rows="6" cols="80"><?php
                    echo $info['notes'] ?: $plugin->getNotes(); ?></textarea>
                </td>
            </tr>
        </tbody>
        </table>
        <p class="centered">
        <input type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
        <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
        <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>"
            onclick='window.location.href="?"'>
        </p>
    </form>
</div>
<div id="instances" class="tab_content hidden">
<?php
    $pjax_container = '#instances';
    include STAFFINC_DIR . 'templates/plugin-instances.tmpl.php';
    ?>
</div>

<script type="text/javascript">
$(function() {
    $('#instances').on('click', 'a.instance-config', function(e) {
        e.preventDefault();
        var $id = $(this).attr('id');
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.dialog(url, [201], function (xhr, resp) {
          var json = $.parseJSON(resp);
          if (json && json.redirect)
              window.location.reload(true);
        },
        {size:'large'});
        return false;
    });
    $('#instances').on('click', 'a.instances-action', function(e) {
        e.preventDefault();
        form = $('form#plugin-instances-form');
        $.confirmAction($(this).attr('href').substr(1), form);
        return false;
    });
});
</script>
