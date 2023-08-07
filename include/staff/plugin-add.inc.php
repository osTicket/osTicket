
<h2><?php echo __('Install a new plugin'); ?></h2>
<p><?php echo __(
'To add a plugin into the system, download and place the plugin into the <code>include/plugins</code> folder. Once in the plugin is in the <code>plugins/</code> folder, it will be shown in the list below.'
); ?>
</p>

<form method="post" action="?">
    <?php echo csrf_token(); ?>
    <input type="hidden" name="do" value="install"/>
<table class="list" width="100%"><tbody>
<?php

$installed = $ost->plugins->allInstalled();
foreach ($ost->plugins->allInfos() as $info) {
    // Ignore installed plugins
    if (isset($installed[$info['install_path']]))
        continue;

    $isCompatible = isset($info['ost_version']) ?
        PluginManager::isCompatible($info['ost_version']) : true;
    ?>
        <tr><td>
            <?php
            if ($isCompatible) {?>
            <button class="button action-button" type="submit" name="install_path"
            value="<?php echo $info['install_path'];
            ?>"><?php echo __('Install'); ?></button>
            <?php
            } ?>
            </td>
        <td>
        <div><strong><?php echo $info['name']; ?></strong><br/>
        <div><?php echo $info['description']; ?></div>
        <div class="faded"><em><?php echo __('Version'); ?>: <?php echo $info['version']; ?></em></div>
        <?php
        if (isset($info['ost_version'])) { ?>
        <div class="faded"><em><?php echo sprintf('%s %s %s', 'osTicket',
                __('Version'), __('Required')); ?>: <?php
        echo sprintf('<span style="color:%s;font-weight:bold;"> %s </span>',
                $isCompatible ? 'green' : 'red',
                $info['ost_version']); ?></em></div>
        <?php
        } ?>
        <div class="faded"><em><?php echo __('Author'); ?>: <?php echo $info['author']; ?></em></div>
    </div>
    </td></tr>
    <?php
}
?>
</tbody></table>
</form>
