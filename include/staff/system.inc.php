<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$commit = GIT_VERSION != '$git' ? GIT_VERSION : (
    @shell_exec('git rev-parse HEAD | cut -b 1-8') ?: '?');

$extensions = array(
        'gd' => array(
            'name' => 'gdlib',
            'desc' => __('Used for image manipulation and PDF printing')
            ),
        'imap' => array(
            'name' => 'imap',
            'desc' => __('Used for email fetching')
            ),
        'xml' => array(
            'name' => 'xml',
            'desc' => __('XML API')
            ),
        'dom' => array(
            'name' => 'xml-dom',
            'desc' => __('Used for HTML email processing')
            ),
        'json' => array(
            'name' => 'json',
            'desc' => __('Improves performance creating and processing JSON')
            ),
        'mbstring' => array(
            'name' => 'mbstring',
            'desc' => __('Highly recommended for non western european language content')
            ),
        'phar' => array(
            'name' => 'phar',
            'desc' => __('Highly recommended for plugins and language packs')
            ),
        );

?>
<h2><?php echo __('About this osTicket Installation'); ?></h2>
<br/>
<table class="list" width="100%";>
<thead>
    <tr><th colspan="2"><?php echo __('Server Information'); ?></th></tr>
</thead>
<tbody>
    <tr><td><?php echo __('osTicket Version'); ?></td>
        <td><span class="ltr"><?php
        echo sprintf("%s (%s)", THIS_VERSION, trim($commit)); ?></span></td></tr>
    <tr><td><?php echo __('Web Server Software'); ?></td>
        <td><span class="ltr"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span></td></tr>
    <tr><td><?php echo __('MySQL Version'); ?></td>
        <td><span class="ltr"><?php echo db_version(); ?></span></td></tr>
    <tr><td><?php echo __('PHP Version'); ?></td>
        <td><span class="ltr"><?php echo phpversion(); ?></span></td></tr>
</tbody>
<thead>
    <tr><th colspan="2"><?php echo __('PHP Extensions'); ?></th></tr>
</thead>
<tbody>
    <?php
    foreach($extensions as $ext => $info) { ?>
    <tr><td><?php echo $info['name']; ?></td>
        <td><?php
            echo sprintf('<i class="icon icon-%s"></i> %s',
                    extension_loaded($ext) ? 'check' : 'warning-sign',
                    $info['desc']);
            ?>
        </td>
    </tr>
    <?php
    } ?>
</tbody>
<thead>
    <tr><th colspan="2"><?php echo __('PHP Settings'); ?></th></tr>
</thead>
<tbody>
    <tr>
        <td><span class="ltr"><code>cgi.fix_pathinfo</code></span></td>
        <td><i class="icon icon-<?php
                echo ini_get('cgi.fix_pathinfo') == 1 ? 'check' : 'warning-sign'; ?>"></i>
                <span class="faded"><?php echo __('"1" is recommended if AJAX is not working'); ?></span>
        </td>
    </tr>
    <tr>
        <td><span class="ltr"><code>date.timezone</code></span></td>
        <td><i class="icon icon-<?php
                echo ini_get('date.timezone') ? 'check' : 'warning-sign'; ?>"></i>
                <span class="faded"><?php
                    echo ini_get('date.timezone')
                    ?: __('Setting default timezone is highly recommended');
                    ?></span>
        </td>
    </tr>
</tbody>
<thead>
    <tr><th colspan="2"><?php echo __('Database Information and Usage'); ?></th></tr>
</thead>
<tbody>
    <tr><td><?php echo __('Schema'); ?></td>
        <td><?php echo sprintf('<span class="ltr">%s (%s)</span>', DBNAME, DBHOST); ?> </td>
    </tr>
    <tr><td><?php echo __('Schema Signature'); ?></td>
        <td><?php echo $cfg->getSchemaSignature(); ?> </td>
    </tr>
    <tr><td><?php echo __('Space Used'); ?></td>
        <td><?php
        $sql = 'SELECT sum( data_length + index_length ) / 1048576 total_size
            FROM information_schema.TABLES WHERE table_schema = '
            .db_input(DBNAME);
        $space = db_result(db_query($sql));
        echo sprintf('%.2f MiB', $space); ?></td>
    <tr><td><?php echo __('Space for Attachments'); ?></td>
        <td><?php
        $sql = 'SELECT SUM(LENGTH(filedata)) / 1048576 FROM '.FILE_CHUNK_TABLE;
        $space = db_result(db_query($sql));
        echo sprintf('%.2f MiB', $space); ?></td>
</tbody>
</table>
<br/>
<h2><?php echo __('Installed Language Packs'); ?></h2>
<div style="margin: 0 20px">
<?php
    foreach (Internationalization::availableLanguages() as $info) {
        $p = $info['path'];
        if ($info['phar']) $p = 'phar://' . $p;
        if (file_exists($p . '/MANIFEST.php')) {
            $manifest = (include $p . '/MANIFEST.php'); ?>
    <h3><strong><?php echo Internationalization::getLanguageDescription($info['code']); ?></strong>
        &mdash; <?php echo $manifest['Language']; ?>
<?php       if ($info['phar'])
                Plugin::showVerificationBadge($info['path']);
            ?>
        </h3>
        <div><?php echo __('Version'); ?>: <?php echo $manifest['Version']; ?>,
            <?php echo __('Built'); ?>: <?php echo $manifest['Build-Date']; ?>
        </div>
<?php }
    } ?>
</div>
