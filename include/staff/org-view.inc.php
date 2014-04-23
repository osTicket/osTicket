<?php
if(!defined('OSTSCPINC') || !$thisstaff || !is_object($org)) die('Invalid path');

?>
<table width="940" cellpadding="2" cellspacing="0" border="0">
    <tr>
        <td width="50%" class="has_bottom_border">
             <h2><a href="orgs.php?id=<?php echo $org->getId(); ?>"
             title="Reload"><i class="icon-refresh"></i> <?php echo $org->getName(); ?></a></h2>
        </td>
        <td width="50%" class="right_align has_bottom_border">
            <a id="org-delete" class="action-button org-action"
            href="#orgs/<?php echo $org->getId(); ?>/delete"><i class="icon-trash"></i> Delete Organization</a>
        </td>
    </tr>
</table>
<table class="ticket_info" cellspacing="0" cellpadding="0" width="940" border="0">
    <tr>
        <td width="50%">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th width="150">Name:</th>
                    <td><b><a href="#orgs/<?php echo $org->getId();
                    ?>/edit" class="org-action"><i
                    class="icon-edit"></i>&nbsp;<?php echo
                    $org->getName();
                    ?></a></td>
                </tr>
                <tr>
                    <th>Account Manager:</th>
                    <td><?php echo $org->getAccountManager(); ?>&nbsp;</td>
                </tr>
            </table>
        </td>
        <td width="50%" style="vertical-align:top">
            <table border="0" cellspacing="" cellpadding="4" width="100%">
                <tr>
                    <th width="150">Created:</th>
                    <td><?php echo Format::db_datetime($org->getCreateDate()); ?></td>
                </tr>
                <tr>
                    <th>Updated:</th>
                    <td><?php echo Format::db_datetime($org->getUpdateDate()); ?></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br>
<div class="clear"></div>
<ul class="tabs">
    <li><a class="active" id="users_tab" href="#users"><i
    class="icon-user"></i>&nbsp;Users</a></li>
    <li><a id="tickets_tab" href="#tickets"><i
    class="icon-list-alt"></i>&nbsp;Tickets</a></li>
</ul>
<div class="tab_content" id="users">
<?php
include STAFFINC_DIR . 'templates/users.tmpl.php';
?>
</div>
<div class="tab_content no-pjax" id="tickets"  style="display:none;">
<?php
include STAFFINC_DIR . 'templates/tickets.tmpl.php';
?>
</div>

<script type="text/javascript">
$(function() {
    $(document).on('click', 'a.org-action', function(e) {
        e.preventDefault();
        var url = 'ajax.php/'+$(this).attr('href').substr(1);
        $.dialog(url, [201, 204], function (xhr) {
            if (xhr.status == 204)
                window.location.href = 'orgs.php';
            else
                window.location.href = window.location.href;
         }, {
            onshow: function() { $('#org-search').focus(); }
         });
        return false;
    });
});
</script>
