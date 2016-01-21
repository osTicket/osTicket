<form action="task-templates.php" method="POST" name="forms">

<div class="sticky bar opaque">
  <div class="content">
    <div class="pull-left flush-left">
      <h2><?php echo __('Task Template Sets'); ?></h2>
    </div>
    <div class="pull-right flush-right">
    <a href="#" data-dialog="ajax.php/tasks/template/group/add"
      class="green button action-button"><i class="icon-plus-sign"></i> <?php
      echo __('Add New Template Set'); ?></a>
      <span class="action-button" data-dropdown="#action-dropdown-more">
          <i class="icon-caret-down pull-right"></i>
          <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
      </span>
      <div id="action-dropdown-more" class="action-dropdown anchor-right">
        <ul id="actions">
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
            <a class="confirm" data-name="delete" href="forms.php?a=delete">
              <i class="icon-trash icon-fixed-width"></i>
              <?php echo __( 'Delete'); ?>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>
<div class="clear"></div>

<?php
$sets = TaskTemplateGroup::objects()
  ->exclude(array('flags__hasbit' => TaskTemplateGroup::FLAG_DELETED))
  ->annotate(array('tasks' => SqlAggregate::COUNT('templates')));

$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = $sets->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('task-templates.php');
$showing=$pageNav->showing().' '._N('template set','template sets',$count);
?>

<?php csrf_token(); ?>

<input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
<table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
  <tbody>
  <thead>
    <tr>
      <th width="12px">&nbsp;</th>
      <th width="50%"><?php echo __('Name'); ?></th>
      <th><?php echo __('Tasks'); ?></th>
      <th><?php echo __('Status'); ?></th>
      <th><?php echo __('Last Updated'); ?></th>
    </tr>
  </thead>
  <tbody>
<?php foreach ($sets->order_by('name')
        ->limit($pageNav->getLimit())
        ->offset($pageNav->getStart()) as $set) {
      $sel=false;
      if($ids && in_array($set->get('id'),$ids))
        $sel=true; ?>
    <tr>
      <td align="center">
        <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $set->get('id'); ?>"
          <?php echo $sel?'checked="checked"':''; ?>>
      </td>
      <td><a href="?group_id=<?php echo $set->get('id'); ?>"><?php echo $set->get('name'); ?></a></td>
      <td><?php echo $set->tasks; ?></td>
      <td><?php echo $set->getStatus(); ?></td>
      <td><?php echo Format::datetime($set->get('updated')); ?></td>
    </tr>
  <?php }
  ?>
  </tbody>
  <tfoot>
   <tr>
    <td colspan="5">
      <?php if($count){ ?>
      <?php echo __('Select'); ?>:&nbsp;
      <a id="selectAll" href="#ckb"><?php echo __('All'); ?></a>&nbsp;&nbsp;
      <a id="selectNone" href="#ckb"><?php echo __('None'); ?></a>&nbsp;&nbsp;
      <a id="selectToggle" href="#ckb"><?php echo __('Toggle'); ?></a>&nbsp;&nbsp;
      <?php }else{
        echo sprintf(__(
          'No task template sets defined yet &mdash; %s add one! %s'),
          '<a  href="#" data-dialog="ajax.php/tasks/template/group/add">','</a>');
      } ?>
    </td>
   </tr>
  </tfoot>
</table>
<?php
if ($count) //Show options..
  echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

</form>

<div style="display:none;" class="dialog" id="confirm-action">
  <h3><?php echo __('Please Confirm'); ?></h3>
  <a class="close" href=""><i class="icon-remove-circle"></i></a>
  <hr/>
  <p class="confirm-action" style="display:none;" id="delete-confirm">
    <font color="red"><strong><?php echo sprintf(__(
    'Are you sure you want to DELETE %s?'),
    _N('selected template set', 'selected template sets', 2));?></strong></font>
    <br><br><?php echo __('Deleted data CANNOT be recovered.'); ?>
  </p>
  <div><?php echo __('Please confirm to continue.'); ?></div>
  <hr style="margin-top:1em"/>
  <p class="full-width">
    <span class="buttons pull-left">
      <input type="button" value="<?php echo __('No, Cancel'); ?>" class="close">
    </span>
    <span class="buttons pull-right">
      <input type="button" value="<?php echo __('Yes, Do it!'); ?>" class="confirm">
    </span>
   </p>
  <div class="clear"></div>
</div>

