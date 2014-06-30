<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$commit = GIT_VERSION != '$git' ? GIT_VERSION : (
    @shell_exec('git rev-parse HEAD') ?: '?');

?>
<h2><?php echo __('About this osTicket Installation'); ?></h2>
<br/>
<table class="list" width="100%";>
<thead>
    <tr><th colspan="2"><?php echo __('Server Information'); ?></th></tr>
</thead>
<tbody>
    <tr><td><?php echo __('osTicket Version'); ?></td>
        <td><?php echo sprintf("%s (%s)", THIS_VERSION, $commit); ?></td></tr>
    <tr><td><?php echo __('Server Software'); ?></td>
        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td></tr>
    <tr><td><?php echo __('PHP Version'); ?></td>
        <td><?php echo phpversion(); ?></td></tr>
    <tr><td><?php echo __('MySQL Version'); ?></td>
        <td><?php echo db_version(); ?></td></tr>

    <tr><td><?php echo __('PHP Extensions'); ?></td>
        <td><table><tbody>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('gd')?'check':'warning-sign'; ?>"></i></td>
                <td>gdlib</td>
                <td><?php echo __('Used for image manipulation and PDF printing'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('imap')?'check':'warning-sign'; ?>"></i></td>
                <td>imap</td>
                <td><?php echo __('Used for email fetching'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('xml')?'check':'warning-sign'; ?>"></i></td>
                <td>xml</td>
                <td><?php echo __('XML API'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('dom')?'check':'warning-sign'; ?>"></i></td>
                <td>xml-dom</td>
                <td><?php echo __('Used for HTML email processing'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('json')?'check':'warning-sign'; ?>"></i></td>
                <td>json</td>
                <td><?php echo __('Improves performance creating and processing JSON'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('gettext')?'check':'warning-sign'; ?>"></i></td>
                <td>gettext</td>
                <td><?php echo __('Improves performance for non US-English configurations'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('mbstring')?'check':'warning-sign'; ?>"></i></td>
                <td>mbstring</td>
                <td><?php echo __('Highly recommended for non western european language content'); ?></td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('phar')?'check':'warning-sign'; ?>"></i></td>
                <td>phar</td>
                <td><?php echo __('Highly recommended for plugins and language packs'); ?></td></tr>
        </tbody></table></td></tr>
    <tr><td><?php echo __('PHP Settings'); ?></td>
        <td><table><tbody>
        <tr><td><i class="icon icon-<?php
                echo extension_loaded('mbstring')?'check':'warning-sign'; ?>"></i>
            </td><td>
            <code>cgi.fix_pathinfo</code> =
                <?php echo ini_get('cgi.fix_pathinfo'); ?>
            </td><td>
            <span class="faded"><?php echo __('"1" is recommended if AJAX is not working'); ?></span>
        </td></tr>
        </tbody></table></td></tr>
</tbody>
<thead>
    <tr><th colspan="2"><?php echo __('Database Usage'); ?></th></tr>
</thead>
<tbody>
    <tr><td><?php echo __('Database Space Used'); ?></td>
        <td><?php
        $sql = 'SELECT sum( data_length + index_length ) / 1048576 total_size
            FROM information_schema.TABLES WHERE table_schema = '
            .db_input(DBNAME);
        $space = db_result(db_query($sql));
        echo sprintf('%.2f MiB', $space); ?></td>
    <tr><td><?php echo __('Database Space for Attachments'); ?></td>
        <td><?php
        $sql = 'SELECT SUM(LENGTH(filedata)) / 1048576 FROM '.FILE_CHUNK_TABLE;
        $space = db_result(db_query($sql));
        echo sprintf('%.2f MiB', $space); ?></td>
</tbody>
</table>
