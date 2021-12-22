<html>

<head>
    <style type="text/css">
@page {
    header: html_def;
    footer: html_def;
    margin: 15mm;
    margin-top: 30mm;
    margin-bottom: 22mm;
}
.logo {
  max-width: 220px;
  max-height: 71px;
  width: auto;
  height: auto;
  margin: 0;
}
#task_thread .message,
#task_thread .response,
#task_thread .note {
    margin-top:10px;
    border:1px solid #aaa;
    border-bottom:2px solid #aaa;
}
#task_thread .header {
    text-align:left;
    border-bottom:1px solid #aaa;
    padding:3px;
    width: 100%;
    table-layout: fixed;
}
#task_thread .message .header {
    background:#C3D9FF;
}
#task_thread .response .header {
    background:#FFE0B3;
}
#task_thread .note .header {
    background:#FFE;
}
#task_thread .info {
    padding:5px;
    background: snow;
    border-top: 0.3mm solid #ccc;
}

table.meta-data {
    width: 100%;
}
table.custom-data {
    margin-top: 10px;
}
table.custom-data th {
    width: 25%;
}
table.custom-data th,
table.meta-data th {
    text-align: right;
    background-color: #ddd;
    padding: 3px 8px;
}
table.meta-data td {
    padding: 3px 8px;
}
.faded {
    color:#666;
}
.pull-left {
    float: left;
}
.pull-right {
    float: right;
}
.flush-right {
    text-align: right;
}
.flush-left {
    text-align: left;
}
.ltr {
    direction: ltr;
    unicode-bidi: embed;
}
.headline {
    border-bottom: 2px solid black;
    font-weight: bold;
}
div.hr {
    border-top: 0.2mm solid #bbb;
    margin: 0.5mm 0;
    font-size: 0.0001em;
}
.thread-entry, .thread-body {
    page-break-inside: avoid;
}
<?php include ROOT_DIR . 'css/thread.css'; ?>
    </style>
</head>
<body>

<htmlpageheader name="def" style="display:none">
<?php if ($logo = $cfg->getClientLogo()) { ?>
    <img src="cid:<?php echo $logo->getKey(); ?>" class="logo"/>
<?php } else { ?>
    <img src="<?php echo INCLUDE_DIR . 'fpdf/print-logo.png'; ?>" class="logo"/>
<?php } ?>
    <div class="hr">&nbsp;</div>
    <table><tr>
        <td class="flush-left"><?php echo (string) $ost->company; ?></td>
        <td class="flush-right"><?php echo Format::daydatetime(Misc::gmtime()); ?></td>
    </tr></table>
</htmlpageheader>

<htmlpagefooter name="def" style="display:none">
    <div class="hr">&nbsp;</div>
    <table width="100%"><tr><td class="flush-left">
        Task #<?php echo $task->getNumber(); ?> printed by
        <?php echo $thisstaff->getUserName(); ?> on
        <?php echo Format::daydatetime(Misc::gmtime()); ?>
    </td>
    <td class="flush-right">
        Page {PAGENO}
    </td>
    </tr></table>
</htmlpagefooter>

<!-- Task metadata -->
<h1>Task #<?php echo $task->getNumber(); ?></h1>
<table class="meta-data" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <th><?php echo __('Status'); ?></th>
    <td><?php echo $task->getStatus(); ?></td>
    <th><?php echo __('Department'); ?></th>
    <td><?php echo $task->getDept(); ?></td>
</tr>
<tr>
    <th><?php echo __('Create Date'); ?></th>
    <td><?php echo Format::datetime($task->getCreateDate()); ?></td>
    <?php
    if ($task->isOpen()) { ?>
    <th><?php echo __('Assigned To'); ?></th>
    <td><?php echo $task->getAssigned(); ?></td>
    <?php
    } else { ?>
    <th><?php echo __('Closed By');?>:</th>
    <td>
        <?php
        if (($staff = $task->getStaff()))
            echo Format::htmlchars($staff->getName());
        else
            echo '<span class="faded">&mdash; '.__('Unknown').' &mdash;</span>';
    ?>
    </td>
    <?php
    } ?>
</tr>
<tr>
    <?php
    if ($task->isOpen()) {?>
    <th><?php echo __('Due Date'); ?></th>
    <td><?php echo Format::datetime($task->getDueDate()); ?></td>
    <?php
    } else { ?>
    <th><?php echo __('Close Date'); ?></th>
    <td><?php echo Format::datetime($task->getCloseDate()); ?></td>
    <?php
    } ?>
    <th><?php echo __('Collaborators'); ?></th>
    <td><?php echo $task->getParticipants(); ?></td>
</tr>
</tbody>
</table>
<!-- Custom Data -->
<?php
foreach (DynamicFormEntry::forTask($task->getId()) as $form) {
    // Skip core fields shown earlier on the view
    $answers = $form->getAnswers()->exclude(Q::any(array(
        'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
        'field__name__in' => array('title')
    )));
    if (count($answers) == 0)
        continue;
    ?>
        <table class="custom-data" cellspacing="0" cellpadding="4" width="100%" border="0">
        <tr><td colspan="2" class="headline flush-left"><?php echo $form->getTitle(); ?></th></tr>
        <?php foreach($answers as $a) {
            if (!($v = $a->display())) continue; ?>
            <tr>
                <th><?php
    echo $a->getField()->get('label');
                ?>:</th>
                <td><?php
    echo $v;
                ?></td>
            </tr>
            <?php } ?>
        </table>
    <?php
    $idx++;
} ?>

<!-- Task Thread -->
<h2><?php echo $task->getTitle(); ?></h2>
<div id="task_thread">
<?php
$types = array('M', 'R', 'N');
if ($entries = $task->getThreadEntries($types)) {
    $entryTypes=array('M'=>'message','R'=>'response', 'N'=>'note');
    foreach ($entries as $entry) { ?>
        <div class="thread-entry <?php echo $entryTypes[$entry->type]; ?>">
            <table class="header" style="width:100%"><tr><td>
                    <span><?php
                        echo Format::datetime($entry->created);?></span>
                    <span style="padding:0 1em" class="faded title"><?php
                        echo Format::truncate($entry->title, 100); ?></span>
                </td>
                <td class="flush-right faded title" style="white-space:no-wrap">
                    <?php
                        echo Format::htmlchars($entry->getName()); ?></span>
                </td>
            </tr></table>
            <div class="thread-body">
                <div><?php echo $entry->getBody()->display('pdf'); ?></div>
            <?php
            if ($entry->has_attachments
                    && ($files = $entry->attachments)) { ?>
                <div class="info">
<?php           foreach ($files as $A) { ?>
                    <div>
                        <span><?php echo Format::htmlchars($A->file->name); ?></span>
                        <span class="faded">(<?php echo Format::file_size($A->file->size); ?>)</span>
                    </div>
<?php           } ?>
                </div>
<?php       } ?>
            </div>
        </div>
<?php }
} ?>
</div>
</body>
</html>
