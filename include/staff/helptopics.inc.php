<?php
if (!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');


$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Topic::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('helptopics.php', $_qstr);
$showing = $pageNav->showing().' '._N('help topic', 'help topics', $count);

$order_by = ($cfg->getTopicSortMode() == 'm') ? 'sort' : 'topic';

?>
<div class="pull-left" style="width:700px;padding-top:5px;">
 <h2><?php echo __('Help Topics');?></h2>
 </div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">
    <b><a href="helptopics.php?a=add" class="Icon newHelpTopic"><?php echo __('Add New Help Topic');?></a></b></div>
<div class="clear"></div>
<form action="helptopics.php" method="POST" name="topics">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="sort" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><span class="pull-left" style="display:inline-block;vertical-align:middle"><?php
         echo $showing; ?></span>
         <div class="pull-right"><?php echo __('Sorting Mode'); ?>:
        <select name="help_topic_sort_mode" onchange="javascript:
    var $form = $(this).closest('form');
    $form.find('input[name=a]').val('sort');
    $form.submit();
">
<?php foreach (OsticketConfig::allTopicSortModes() as $i=>$m)
    echo sprintf('<option value="%s"%s>%s</option>',
        $i, $i == $cfg->getTopicSortMode() ? ' selected="selected"' : '', $m); ?>
        </select>
    </div>
    </caption>
    <thead>
        <tr>
            <th width="7" style="height:20px;">&nbsp;</th>
            <th style="padding-left:4px;vertical-align:middle" width="360"><?php echo __('Help Topic'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="80"><?php echo __('Status'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="100"><?php echo __('Type'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="100"><?php echo __('Priority'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="160"><?php echo __('Department'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="150" nowrap><?php echo __('Last Updated'); ?></th>
        </tr>
    </thead>
    <tbody class="<?php if ($cfg->getTopicSortMode() == 'm') echo 'sortable-rows'; ?>"
        data-sort="sort-">
    <?php
        $ids= ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count) {
            $topics = Topic::objects()
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_by))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());

            $defaultDept = $cfg->getDefaultDept();
            $defaultPriority = $cfg->getDefaultPriority();
            $sort = 0;
            foreach($topics as $topic) {
                $id = $topic->getId();
                $sort++; // Track initial order for transition
                $sel=false;
                if ($ids && in_array($id, $ids))
                    $sel=true;

                if ($topic->dept_id) {
                    $deptId = $topic->dept_id;
                    $dept = (string) $topic->dept;
                } elseif ($defaultDept) {
                    $deptId = $defaultDept->getId();
                    $dept = (string) $defaultDept;
                } else {
                    $deptId = 0;
                    $dept = '';
                }
                $priority = $team->priority ?: $defaultPriority;
                ?>
            <tr id="<?php echo $id; ?>">
                <td width=7px>
                  <input type="hidden" name="sort-<?php echo $id; ?>" value="<?php
                        echo $topic->sort ?: $sort; ?>"/>
                  <input type="checkbox" class="ckb" name="ids[]"
                    value="<?php echo $id; ?>" <?php
                    echo $sel ? 'checked="checked"' : ''; ?>>
                </td>
                <td>
                    <?php
                    if ($cfg->getTopicSortMode() == 'm') { ?>
                        <i class="icon-sort"></i>
                    <?php } ?>
                    <a href="helptopics.php?id=<?php echo $id; ?>"><?php
                    echo Topic::getTopicName($id); ?></a>&nbsp;
                </td>
                <td><?php echo $topic->isactive ? __('Active') : '<b>'.__('Disabled').'</b>'; ?></td>
                <td><?php echo $topic->ispublic ? __('Public') : '<b>'.__('Private').'</b>'; ?></td>
                <td><?php echo $priority; ?></td>
                <td><a href="departments.php?id=<?php echo $deptId;
                ?>"><?php echo $dept; ?></a></td>
                <td>&nbsp;<?php echo Format::datetime($team->updated); ?></td>
            </tr>
            <?php
            } //end of foreach.
        }?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if ($count) { ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No help topics found');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count): //Show options..
     echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
<?php if ($cfg->getTopicSortMode() != 'a') { ?>
    <input class="button no-confirm" type="submit" name="sort" value="Save"/>
<?php } ?>
    <button class="button" type="submit" name="enable" value="Enable" ><?php echo __('Enable'); ?></button>
    <button class="button" type="submit" name="disable" value="Disable"><?php echo __('Disable'); ?></button>
    <button class="button" type="submit" name="delete" value="Delete"><?php echo __('Delete'); ?></button>
</p>
<?php
endif;
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected help topic', 'selected help topics', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected help topic', 'selected help topics', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected help topic', 'selected help topics', 2));?></strong></font>
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
