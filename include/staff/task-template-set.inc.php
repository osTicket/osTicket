<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();

// This page is EDIT ONLY. Additions are done via dialog

$title=__('Task Template Set');
$action='resort';
$submit_text=__('Save Changes');
$info['id'] = $set->getId();
$qs += array('id' => $set->getId());
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
            <a data-name="enable" href="task-templates.php?a=enable">
              <i class="icon-ok-sign icon-fixed-width"></i>
              <?php echo __('Enable'); ?>
            </a>
          </li>
          <li>
            <a data-name="disable" href="task-templates.php?a=disable">
              <i class="icon-ban-circle icon-fixed-width"></i>
              <?php echo __('Disable'); ?>
            </a>
          </li>
          <li class="danger">
            <a class="confirm" data-name="delete" href="task-templates.php?a=delete">
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
  <input type="hidden" name="do" value="<?php echo $action; ?>">
  <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
  <input type="hidden" name="group_id" value="<?php echo $info['id']; ?>">

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
$display_level = function($items, $level=0) use (&$children, $set, &$display_level) {
    foreach ($items as $id=>$info) {
        list($template, $children) = $info;
?>
      <tr>
        <td>
          <input type="checkbox" name="ckb[]" class="ckb" />
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

