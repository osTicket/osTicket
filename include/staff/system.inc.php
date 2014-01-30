<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

?>
<h2>About this osTicket Installation</h2>
<br/>
<table class="list" width="100%";>
<thead>
    <tr><th colspan="2">Server Information</th></tr>
</thead>
<tbody>
    <tr><td>osTicket Version</td>
        <td><?php echo THIS_VERSION; ?></td></tr>
    <tr><td>Server Software</td>
        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td></tr>
    <tr><td>PHP Version</td>
        <td><?php echo phpversion(); ?></td></tr>
    <tr><td>MySQL Version</td>
        <td><?php echo db_version(); ?></td></tr>

    <tr><td>PHP Extensions</td>
        <td><table><tbody>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('gd')?'check':'warning-sign'; ?>"></i></td>
                <td>gdlib</td>
                <td>Used for image manipulation and PDF printing</td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('imap')?'check':'warning-sign'; ?>"></i></td>
                <td>imap</td>
                <td>Used for email fetching</td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('xml')?'check':'warning-sign'; ?>"></i></td>
                <td>xml</td>
                <td>XML API</td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('dom')?'check':'warning-sign'; ?>"></i></td>
                <td>xml-dom</td>
                <td>Used for HTML email processing</td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('json')?'check':'warning-sign'; ?>"></i></td>
                <td>json</td>
                <td>Improves performance creating and processing JSON</td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('gettext')?'check':'warning-sign'; ?>"></i></td>
                <td>gettext</td>
                <td>Improves performance for non US-English configurations</td></tr>
            <tr><td><i class="icon icon-<?php
                    echo extension_loaded('mbstring')?'check':'warning-sign'; ?>"></i></td>
                <td>mbstring</td>
                <td>Highly recommended for non western european language content</td></tr>
        </tbody></table></td></tr>
</tbody>
<thead>
    <tr><th colspan="2">Database Usage</th></tr>
</thead>
<tbody>
    <tr><td>Database Space Used</td>
        <td><?php
        $sql = 'SELECT sum( data_length + index_length ) / 1048576 total_size
            FROM information_schema.TABLES WHERE table_schema = '
            .db_input(DBNAME);
        $space = db_result(db_query($sql));
        echo sprintf('%.2f MiB', $space); ?></td>
    <tr><td>Database Space for Attachments</td>
        <td><?php
        $sql = 'SELECT SUM(LENGTH(filedata)) / 1048576 FROM '.FILE_CHUNK_TABLE;
        $space = db_result(db_query($sql));
        echo sprintf('%.2f MiB', $space); ?></td>
</tbody>
</table>
