<?php
global $cfg;

$entryTypes = array(
        'M' => array('color' => '#0088cc'),
        'R' => array('color' => '#e65524'),
        );

AttachmentFile::objects()->filter(array(
            'attachments__thread_entry__thread__id' => $thread->getId(),
            'attachments__thread_entry__type__in' => array_keys($entryTypes)
            ))->all();

$entries = $thread->getEntries();
$entries->filter(array('type__in' => array_keys($entryTypes)))->order_by("{$order}id");
?>
<style type="text/css">
    div {font-family: sans-serif;}
</style>
<div style="width: 100%; margin: 0; padding: 0;">
    <div style="padding:10px;">
    <p style="font-family: sans-serif; font-size:12px; color:#999;">&nbsp;</p>
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tbody>
            <tr>
                <td></td>
            </tr>
            <?php
            foreach ($entries as $entry) {
                $user = $entry->getUser() ?: $entry->getStaff();
                $name = $user ? $user->getName() : $entry->poster;
                $color = $entryTypes[$entry->type]['color'];
                ?>
                <tr>
                    <td style=" border-top: 1px dashed #999;">
                        <div style="background-color:#f7f7f7; padding:10px 20px;">
                            <p style="font-family: sans-serif; padding:0; margin:0; color:<?php echo $color; ?>;">
                                <strong><?php echo $name; ?></strong>
                                <span style="color:#888; font-size:12px; padding-left: 20px;"><?php
                                    echo $entry->title;
                                ?>
                                </span>
                            </p>
                            <p style="font-family: sans-serif; padding:0; margin:0; color:#888; font-size:12px;">
                            <?php
                            echo Format::daydatetime($entry->created);
                            ?>
                            </p>
                        </div>
                        <div style="padding:2px 20px;">
                            <p style="font-family: sans-serif; font-size:14px; color:#555;">
                                <?php
                                echo $entry->getBody()->display('email');
                                ?>
                            </p>
                            <?php
                            if ($entry->has_attachments) { ?>
                            <p style="font-family: sans-serif; font-size:12px; line-height:20px; color:#888;">
                                <?php echo __('Attachments'); ?>
                                <br />
                                <?php
                                foreach ($entry->attachments as $a) {
                                    if ($a->inline) continue;
                                    $size = '';
                                    if ($a->file->size)
                                        $size = sprintf('<small style="color:#ccc;">&nbsp;(%s)</small>',
                                                Format::file_size($a->file->size));

                                    $filename = Format::htmlchars($a->getFilename());
                                    echo sprintf('<a href="%s" download="%s"
                                            style="font-size:11px; color:#0088cc;"
                                            target="_blank">%s</a>&nbsp;&nbsp;&nbsp;%s<br/>',
                                            $a->file->getExternalDownloadUrl(),
                                            $filename,
                                            $filename,
                                            $size);
                                }
                            ?>
                            </p>
                            <?php
                            } ?>
                        </div>
                    </td>
                </tr>
                <tr><td>&nbsp;</td></tr>
            <?php
            } ?>
        </tbody>
    </table>
    <div style="font-family: sans-serif; margin: 2px 0 14px 0; padding: 10px ; border-top: 1px solid #999; font-size:12px; color:#888;">
        &nbsp;
    </div>
</div>
