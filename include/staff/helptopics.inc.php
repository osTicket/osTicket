<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$sql='SELECT topic.* '
    .', dept.dept_name as department '
    .', priority_desc as priority '
    .' FROM '.TOPIC_TABLE.' topic '
    .' LEFT JOIN '.DEPT_TABLE.' dept ON (dept.dept_id=topic.dept_id) '
    .' LEFT JOIN '.TICKET_PRIORITY_TABLE.' pri ON (pri.priority_id=topic.priority_id) ';
$sql.=' WHERE 1';
$order_by = ($cfg->getTopicSortMode() == 'm' ? '`sort`' : '`topic_id`');

$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
//Ok..lets roll...create the actual query
$query="$sql ORDER BY $order_by";
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=sprintf(_N('Showing %d help topic', 'Showing %d help topics', $num), $num);
else
    $showing=__('No help topics found!');

// Get the full names and filter for this page
$topics = array();
while ($row = db_fetch_array($res))
    $topics[] = $row;

foreach ($topics as &$t)
    $t['name'] = Topic::getTopicName($t['topic_id']);

if ($cfg->getTopicSortMode() == 'a')
    usort($topics, function($a, $b) { return strcmp($a['name'], $b['name']); });

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
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if (count($topics)):
            $defaultDept = $cfg->getDefaultDept();
            $defaultPriority = $cfg->getDefaultPriority();
            $sort = 0;
            foreach($topics as $row) {
                $sort++; // Track initial order for transition
                $sel=false;
                if($ids && in_array($row['topic_id'],$ids))
                    $sel=true;

                if (!$row['dept_id'] && $defaultDept) {
                    $row['dept_id'] = $defaultDept->getId();
                    $row['department'] = (string) $defaultDept;
                }

                if (!$row['priority'] && $defaultPriority)
                    $row['priority'] = (string) $defaultPriority;

                ?>
            <tr id="<?php echo $row['topic_id']; ?>">
                <td width=7px>
                  <input type="hidden" name="sort-<?php echo $row['topic_id']; ?>" value="<?php
                        echo $row['sort'] ?: $sort; ?>"/>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['topic_id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?>>
                </td>
                <td>
<?php if ($cfg->getTopicSortMode() == 'm') { ?>
                    <i class="icon-sort"></i>
<?php } ?>
<a href="helptopics.php?id=<?php echo $row['topic_id']; ?>"><?php echo $row['name']; ?></a>&nbsp;</td>
                <td><?php echo $row['isactive']?__('Active'):'<b>'.__('Disabled').'</b>'; ?></td>
                <td><?php echo $row['ispublic']?__('Public'):'<b>'.__('Private').'</b>'; ?></td>
                <td><?php echo $row['priority']; ?></td>
                <td><a href="departments.php?id=<?php echo $row['dept_id']; ?>"><?php echo $row['department']; ?></a></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="7">
            <?php if($res && $num){ ?>
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
if($res && $num): //Show options..
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
        <?php echo sprintf(__('Are you sure want to <b>enable</b> %s?'),
            _N('selected help topic', 'selected help topics', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure want to <b>disable</b> %s?'),
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

