<html>

<head>
    <style type="text/css">
@page {
    header: html_def;
    footer: html_def;
    margin: 15mm;
    margin-top: 30mm;
    margin-bottom: 22mm;
        <!--osta-->
	font-family: "Open Sans", "Segoe UI", Tahoma, sans-serif;
}
a, h1, h2, h3, h4, h5, h6, th, td, .header, .thread-event {
	font-family: "Open Sans", "Segoe UI", Tahoma, sans-serif;
}
a {
	color: #2a6496;
}	
h1 {
    font-size: 26px !important;
	font-weight: normal;
    color: #000;
	margin: 0px;
}
h2 {
    font-size: 22px !important;
	font-weight: normal;
    color: #666;
	margin-top: 0px;
}
th {
	font-weight: normal;
	color: #000;
}
td {
	color: #666;
}
.logo {
	max-width: 220px;
	max-height: 71px;
	width: auto;
	height: auto;
	margin: 16px 0 0 0;
}
#ticket_thread {
	margin: 20px 0 0 0;
}
#ticket_thread .message,
#ticket_thread .response,
#ticket_thread .note {
    margin-top:0px;
    border:1px solid #aaa;
    border-bottom:2px solid #aaa;
	border-radius: 6px;
}
#ticket_thread .header {
    text-align:left;
    border-bottom:1px solid #aaa;
    padding:3px;
    width: 100%;
    table-layout: fixed;
}
#ticket_thread .message {
    border:1px solid #CFA173;
    border-bottom:2px solid #CFA173;
}
#ticket_thread .message .header {
    background:#FFDDBA;
    color: #4c5156;
}
#ticket_thread .response {
    border:1px solid #76B9C3;
    border-bottom:2px solid #76B9C3;
}
#ticket_thread .response .header {
    background:#B2E9F1;
    color: #4c5156;	
}
#ticket_thread .note {
    border:1px solid #9BBFC3;
    border-bottom:2px solid #9BBFC3;
}
#ticket_thread .note .header {
    background:#DAE9EB;
	color: #DAE9EB;
}
.thread-event {
    margin: 6px 10px 24px 10px;
    padding: 14px;
	font-size: 14px;
	border-radius: 6px;
    background-color: #F4F4F4;
	border: 1px solid #D6D6D6;
}
.thread-event b,
.thread-event strong {
	font-weight: normal;
	color: #2E2E2E;
}
#ticket_thread .info {
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
    text-align: left;
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
<!--osta-->
.headline {
    border-bottom: 0.2mm solid #ddd;
    font-size: 18px !important;
    font-weight: normal;
    color: #666;
}
div.hr {
    border-top: 0.2mm solid #ddd;
    margin: 0.5mm 0;
    font-size: 0.0001em;
    display: none;
}
.thread-entry, .thread-body {
    page-break-inside: avoid;
}
img.avatar {
    vertical-align: middle;
    padding-right: 2px;
    max-height: 20px;
    width: auto;
}
#print-footer td {
    font-size: 12px;
}
<?php include ROOT_DIR . 'css/thread.css'; ?>
    </style>
</head>
<body>

<htmlpageheader name="def" style="display:none">
<!--osta-->
<?php
 require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH . "/osta/php/functions.php";
 $custom_logo = pdf_logo(get_config());
 if ( !is_null($custom_logo)) echo $custom_logo;
 else if ($logo = $cfg->getClientLogo()) { ?>
    <img src="cid:<?php echo $logo->getKey(); ?>" class="logo"/>
<?php } else { ?>
    <img src="<?php echo INCLUDE_DIR . 'fpdf/print-logo.png'; ?>" class="logo"/>
<?php } ?>
    <div class="hr">&nbsp;</div>
    <!--<table><tr>
        <td class="flush-left"><?php echo (string) $ost->company; ?></td>
        <td class="flush-right"><?php echo Format::daydatetime(Misc::gmtime()); ?></td>
    </tr></table>-->
</htmlpageheader>

<htmlpagefooter name="def" style="display:none">
    <div class="hr">&nbsp;</div>
    <table id="print-footer" width="100%"><tr><td class="flush-left">
        Ticket #<?php echo $ticket->getNumber(); ?> printed by
        <?php echo $thisclient->getName()->getFirst(); ?> on
        <?php echo Format::daydatetime(Misc::gmtime()); ?>
    </td>
    <td class="flush-right">
        Page {PAGENO}
    </td>
    </tr></table>
</htmlpagefooter>

<!-- Ticket metadata -->
<h1>Ticket #<?php echo $ticket->getNumber(); ?></h1>
<h2><?php echo $ticket->getSubject(); ?></h2>
<!--osta-->
<table class="meta-data" cellpadding="0" cellspacing="0">
<tbody>
<tr>
    <th><?php echo __('Status'); ?></th>
    <td><?php echo $ticket->getStatus(); ?></td>
    <th><?php echo __('Name'); ?></th>
    <td><?php echo $ticket->getOwner()->getName(); ?></td>
</tr>
<tr>
    <th><?php echo __('Priority'); ?></th>
    <td><?php echo $ticket->getPriority(); ?></td>
    <th><?php echo __('Email'); ?></th>
    <td><?php echo $ticket->getEmail(); ?></td>
</tr>
<tr>
    <th><?php echo __('Department'); ?></th>
    <td><?php echo $ticket->getDept(); ?></td>
    <th><?php echo __('Phone'); ?></th>
    <td><?php echo $ticket->getPhoneNumber(); ?></td>
</tr>
<tr>
    <th><?php echo __('Create Date'); ?></th>
    <td><?php echo Format::datetime($ticket->getCreateDate()); ?></td>
    <th><?php echo __('Source'); ?></th>
    <td><?php echo $ticket->getSource(); ?></td>
</tr>
</tbody>
</table>

<!-- Custom Data -->
<?php
foreach (DynamicFormEntry::forTicket($ticket->getId()) as $form) {
    // Skip core fields shown earlier in the ticket view
    $answers = $form->getAnswers()->exclude(Q::any(array(
        'field__flags__hasbit' => DynamicFormField::FLAG_EXT_STORED,
        Q::not(array('field__flags__hasbit' => DynamicFormField::FLAG_CLIENT_VIEW)),
        'field__name__in' => array('subject', 'priority'),
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

<!-- Ticket Thread -->
<!--osta-->
<div id="ticket_thread">
<?php
$types = array('M', 'R');

if ($thread = $ticket->getThreadEntries($types)) {
    $thread = ThreadEntry::sortEntries($thread, $ticket);
    $threadTypes=array('M'=>'message','R'=>'response', 'N'=>'note');
    // Check for Agent Identity Masking
    $agentmasking = $cfg->hideStaffName();
    foreach ($thread as $entry) { ?>
        <div class="thread-entry <?php echo $threadTypes[$entry->type]; ?>">
            <table class="header"><tr><td>
                    <span><?php
                        echo Format::datetime($entry->created);?></span>
                    <span style="padding:0 1em" class="faded title"><?php
                        echo Format::truncate($entry->title, 100); ?></span>
                </td>
                <td class="flush-right faded title" style="white-space:no-wrap">
                    <?php
                        // If Identity Masking is Enabled hide Agent's name
                        echo ($entry->staff_id && $agentmasking)
                            ? __('Staff') : Format::htmlchars($entry->getName()); ?></span>
                </td>
            </tr></table>
            <div class="thread-body">
                <div><?php echo $entry->getBody()->display('pdf'); ?></div>
            </div>
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
        </div><br /><!--osta-->
<?php }
} ?>
</div>
</body>
</html>