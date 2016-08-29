<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();

// This page is EDIT ONLY. Additions are done via dialog

$title=__('Task Template Set');
$action='resort';
$submit_text=__('Save Changes');
$info['group_id'] = $set->getId();
$qs += array('group_id' => $set->getId());
?>

<div class="sticky bar opaque">
  <div class="content">
    <div class="pull-left flush-left">
      <h2><?php echo $title; ?>
          <?php if (isset($set->id)) { ?><small>
          — <a href="task-templates.php?group_id=<?php echo $set->getId(); ?>"
            ><?php echo Format::htmlchars($set->getName()); ?></a></small>
          <?php } ?>
      </h2>
    </div>
    <div class="pull-right flush-right">
      <a href="task-templates.php?group_id=<?php echo $set->id; ?>&amp;a=add-tpl"
        class="green button action-button"><i class="icon-plus-sign"></i> <?php
        echo __('Add Template'); ?></a>
      <span class="action-button" data-dropdown="#action-dropdown-more">
        <i class="icon-caret-down pull-right"></i>
        <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
      </span>
      <div id="action-dropdown-more" class="action-dropdown anchor-right">
        <ul id="actions">
          <li>
            <a data-dialog="ajax.php/tasks/template/group/<?php echo $set->getId(); ?>" href="#">
              <i class="icon-cogs icon-fixed-width"></i>
              <?php echo __('Edit Set Info'); ?>
            </a>
          </li>
          <li>
            <a class="confirm" data-name="enable" data-form-id="save"
                href="task-templates.php?a=enable&amp;group_id=<?php echo $set->getId(); ?>">
              <i class="icon-ok-sign icon-fixed-width"></i>
              <?php echo __('Enable'); ?>
            </a>
          </li>
          <li>
            <a class="confirm" data-name="disable" data-form-id="save"
                 href="task-templates.php?a=disable&amp;group_id=<?php echo $set->getId(); ?>">
              <i class="icon-ban-circle icon-fixed-width"></i>
              <?php echo __('Disable'); ?>
            </a>
          </li>
          <li class="danger">
            <a class="confirm" data-name="delete" data-form-id="save"
                href="task-templates.php?a=delete&amp;group_id=<?php echo $set->getId(); ?>">
              <i class="icon-trash icon-fixed-width"></i>
              <?php echo __('Delete'); ?>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
<div class="clear"></div>


<form action="task-templates.php?<?php echo Http::build_query($qs); ?>" method="post" id="save" autocomplete="off">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" id="action" value="<?php echo $action; ?>" />
  <input type="hidden" name="action" value="<?php echo Format::htmlchars($_REQUEST['action']); ?>" />
  <input type="hidden" name="group_id" value="<?php echo $set->id; ?>" />

  <table class="list full-width" cellspacing="1">
    <thead>
      <tr class="flush-left">
        <th style="width:12px"></th>
        <th style="width:50%"><?php echo __('Title and Dependency'); ?></th>
        <th><?php echo __('Status'); ?></th>
        <th><?php echo __('Created'); ?></th>
      </tr>
    </thead>
    <tbody class="sortable-rows">
<?php
$display_level = function($items, $level=0) use ($set, &$display_level) {
    foreach ($items as $id=>$info) {
        list($template, $children) = $info;
?>
      <tr>
        <td>
          <input type="checkbox" name="ids[]" class="ckb" value="<?php echo $id; ?>" />
          <input type="hidden" name="sort[]" value="<?php echo $id; ?>" />
        </td>
        <td><?php
          for ($i=0; $i<$level; $i++) {
            echo '<span class="child indent"></span>';
          } ?>
            <a href="task-templates.php?group_id=<?php echo $set->id; ?>&amp;tpl_id=<?php echo $id; ?>"
              ><?php echo $template->getName(); ?></a>
        </td>
        <td><?php echo $template->getStatus(); ?></td>
        <td><?php echo Format::datetime($template->created); ?></td>
      </tr>
<?php
        if ($children) {
            $display_level($children, $level + 1);
        }
    }
};

$display_level($set->getTreeOrganizedTemplates());
?>
    </tbody>
  </table>

  <p style="text-align:center;">
      <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
      <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
      <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="window.history.go(-1);">
  </p>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected template', 'selected templates', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected template', 'selected templates', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected template', 'selected templates', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
