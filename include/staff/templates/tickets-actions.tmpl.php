<?php
// Tickets mass actions based on logged in agent

if ($agent->canManageTickets())
    echo TicketStatus::status_options();

$actions = array();
if ($agent->hasPerm(Ticket::PERM_ASSIGN, false)) {
    $actions += array(
            'assign' => array(
                'icon' => 'icon-user',
                'action' => __('Assign')
            ));
}

if ($agent->hasPerm(Ticket::PERM_TRANSFER, false)) {
    $actions += array(
            'transfer' => array(
                'icon' => 'icon-share',
                'action' => __('Transfer')
            ));
}

if ($agent->hasPerm(Ticket::PERM_DELETE, false)) {
    $actions += array(
            'delete' => array(
                'class' => 'danger',
                'icon' => 'icon-trash',
                'action' => __('Delete')
            ));
}
if ($actions) {
    $more = $options['morelabel'] ?: __('More');
    ?>
    <span
        class="action-button"
        data-dropdown="#action-dropdown-moreoptions">
        <i class="icon-caret-down pull-right"></i>
        <a class="tickets-action"
            href="#moreoptions"><i
            class="icon-reorder"></i> <?php
            echo $more; ?></a>
    </span>
    <div id="action-dropdown-moreoptions"
        class="action-dropdown anchor-right">
        <ul>
    <?php foreach ($actions as $a => $action) { ?>
            <li <?php
                if ($action['class'])
                    echo sprintf("class='%s'", $action['class']); ?> >
                <a class="no-pjax tickets-action"
                    <?php
                    if ($action['dialog'])
                        echo sprintf("data-dialog-config='%s'", $action['dialog']);
                    if ($action['redirect'])
                        echo sprintf("data-redirect='%s'", $action['redirect']);
                    ?>
                    href="<?php
                    echo sprintf('#tickets/mass/%s', $a); ?>"
                    ><i class="icon-fixed-width <?php
                    echo $action['icon'] ?: 'icon-tag'; ?>"></i> <?php
                    echo $action['action']; ?></a>
            </li>
        <?php
        } ?>
        </ul>
    </div>
 <?php
 } ?>
<script type="text/javascript">
$(function() {
    $(document).off('.tickets-actions');
    $(document).on('click.tickets-actions', 'a.tickets-action', function(e) {
        e.preventDefault();
        var count = checkbox_checker($('form#tickets'), 1);
        if (count) {
            var url = 'ajax.php/'
            +$(this).attr('href').substr(1)
            +'?count='+count
            +'&_uid='+new Date().getTime();
            $.dialog(url, [201], function (xhr) {
                $.pjax.reload('#pjax-container');
             });
        }
        return false;
    });
});
</script>
