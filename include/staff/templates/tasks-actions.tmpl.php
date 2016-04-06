<?php
// Tasks' mass actions based on logged in agent

$actions = array();

if ($agent->hasPerm(Task::PERM_CLOSE, false)) {

    if (isset($options['status'])) {
        $status = $options['status'];
    ?>
        <span
            class="action-button"
            data-dropdown="#action-dropdown-tasks-status">
            <i class="icon-caret-down pull-right"></i>
            <a class="tasks-status-action"
                href="#statuses"
                data-placement="bottom"
                data-toggle="tooltip"
                title="<?php echo __('Change Status'); ?>"><i
                class="icon-flag"></i></a>
        </span>
        <div id="action-dropdown-tasks-status"
            class="action-dropdown anchor-right">
            <ul>
                <?php
                if (!$status || !strcasecmp($status, 'closed')) { ?>
                <li>
                    <a class="no-pjax tasks-action"
                        href="#tasks/mass/reopen"><i
                        class="icon-fixed-width icon-undo"></i> <?php
                        echo __('Reopen');?> </a>
                </li>
                <?php
                }
                if (!$status || !strcasecmp($status, 'open')) {
                ?>
                <li>
                    <a class="no-pjax tasks-action"
                        href="#tasks/mass/close"><i
                        class="icon-fixed-width icon-ok-circle"></i> <?php
                        echo __('Close');?> </a>
                </li>
                <?php
                } ?>
            </ul>
        </div>
<?php
    } else {

        $actions += array(
                'reopen' => array(
                    'icon' => 'icon-undo',
                    'action' => __('Reopen')
                ));

        $actions += array(
                'close' => array(
                    'icon' => 'icon-ok-circle',
                    'action' => __('Close')
                ));
    }
}

if ($agent->hasPerm(Task::PERM_ASSIGN, false)) {
    $actions += array(
            'claim' => array(
                'icon' => 'icon-user',
                'action' => __('Claim')
            ));
     $actions += array(
            'assign/agents' => array(
                'icon' => 'icon-user',
                'action' => __('Assign to Agent')
            ));
    $actions += array(
            'assign/teams' => array(
                'icon' => 'icon-group',
                'action' => __('Assign to Team')
            ));
}

if ($agent->hasPerm(Task::PERM_TRANSFER, false)) {
    $actions += array(
            'transfer' => array(
                'icon' => 'icon-share',
                'action' => __('Transfer')
            ));
}

if ($agent->hasPerm(Task::PERM_DELETE, false)) {
    $actions += array(
            'delete' => array(
                'class' => 'danger',
                'icon' => 'icon-trash',
                'action' => __('Delete')
            ));
}
if ($actions && !isset($options['status'])) {
    $more = $options['morelabel'] ?: __('More');
    ?>
    <span
        class="action-button"
        data-dropdown="#action-dropdown-moreoptions">
        <i class="icon-caret-down pull-right"></i>
        <a class="tasks-action"
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
                <a class="no-pjax tasks-action"
                    <?php
                    if ($action['dialog'])
                        echo sprintf("data-dialog-config='%s'", $action['dialog']);
                    if ($action['redirect'])
                        echo sprintf("data-redirect='%s'", $action['redirect']);
                    ?>
                    href="<?php
                    echo sprintf('#tasks/mass/%s', $a); ?>"
                    ><i class="icon-fixed-width <?php
                    echo $action['icon'] ?: 'icon-tag'; ?>"></i> <?php
                    echo $action['action']; ?></a>
            </li>
        <?php
        } ?>
        </ul>
    </div>
 <?php
 } else {
    // Mass Claim/Assignment
    if ($agent->hasPerm(Task::PERM_ASSIGN, false)) {?>
    <span
        class="action-button" data-placement="bottom"
        data-dropdown="#action-dropdown-assign" data-toggle="tooltip" title=" <?php
        echo __('Assign'); ?>">
        <i class="icon-caret-down pull-right"></i>
        <a class="tasks-action" id="tasks-assign"
            href="#tasks/mass/assign"><i class="icon-user"></i></a>
    </span>
    <div id="action-dropdown-assign" class="action-dropdown anchor-right">
      <ul>
         <li><a class="no-pjax tasks-action"
            href="#tasks/mass/claim"><i
            class="icon-chevron-sign-down"></i> <?php echo __('Claim'); ?></a>
         <li><a class="no-pjax tasks-action"
            href="#tasks/mass/assign/agents"><i
            class="icon-user"></i> <?php echo __('Agent'); ?></a>
         <li><a class="no-pjax tasks-action"
            href="#tasks/mass/assign/teams"><i
            class="icon-group"></i> <?php echo __('Team'); ?></a>
      </ul>
    </div>
    <?php
    }

    // Mass Transfer
    if ($agent->hasPerm(Task::PERM_TRANSFER, false)) {?>
    <span class="action-button">
     <a class="tasks-action" id="tasks-transfer" data-placement="bottom"
        data-toggle="tooltip" title="<?php echo __('Transfer'); ?>"
        href="#tasks/mass/transfer"><i class="icon-share"></i></a>
    </span>
    <?php
    }


    // Mass Delete
    if ($agent->hasPerm(Task::PERM_DELETE, false)) {?>
    <span class="red button action-button">
     <a class="tasks-action" id="tasks-delete" data-placement="bottom"
        data-toggle="tooltip" title="<?php echo __('Delete'); ?>"
        href="#tasks/mass/delete"><i class="icon-trash"></i></a>
    </span>
<?php
    }
} ?>


<script type="text/javascript">
$(function() {
    $(document).off('.tasks-actions');
    $(document).on('click.tasks-actions', 'a.tasks-action', function(e) {
        e.preventDefault();
        var $form = $('form#tasks');
        var count = checkbox_checker($form, 1);
        if (count) {
            var tids = $('.ckb:checked', $form).map(function() {
                    return this.value;
                    }).get();
            var url = 'ajax.php/'
            +$(this).attr('href').substr(1)
            +'?count='+count
            +'&tids='+tids.join(',')
            +'&_uid='+new Date().getTime();
            var $redirect = $(this).data('redirect');
            $.dialog(url, [201], function (xhr) {
                if (!!$redirect)
                    $.pjax({url: $redirect, container:'#pjax-container'});
                else
                  <?php
                  if (isset($options['callback_url']))
                    echo sprintf("$.pjax({url: '%s', container: '%s', push: false});",
                           $options['callback_url'],
                           @$options['container'] ?: '#pjax-container'
                           );
                  else
                    echo sprintf("$.pjax.reload('%s');",
                            @$options['container'] ?: '#pjax-container');
                 ?>
             });
        }
        return false;
    });
});
</script>
