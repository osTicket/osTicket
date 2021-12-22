<?php
if (!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');


$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Topic::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$pageNav->setURL('helptopics.php', $_qstr);
$showing = $pageNav->showing().' '._N('help topic', 'help topics', $count);

$order_by = 'sort';

?>
<form action="helptopics.php" method="POST" name="topics">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Help Topics');?></h2>
            </div>
            <div class="pull-right flush-right">
                <?php if ($cfg->getTopicSortMode() != 'a') { ?>
                <button class="button no-confirm" type="submit" name="sort"><i class="icon-save"></i>
                <?php echo __('Save'); ?></button>
                <?php } ?>
                <a href="helptopics.php?a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php echo __('Add New Help Topic');?></a>
                <span class="action-button" data-dropdown="#action-dropdown-more">
           <i class="icon-caret-down pull-right"></i>
            <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul id="actions">
                        <li>
                            <a class="confirm" data-name="enable" href="helptopics.php?a=enable">
                                <i class="icon-ok-sign icon-fixed-width"></i>
                                <?php echo __( 'Enable'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="confirm" data-name="disable" href="helptopics.php?a=disable">
                                <i class="icon-ban-circle icon-fixed-width"></i>
                                <?php echo __( 'Disable'); ?>
                            </a>
                        </li>
                        <li>
                            <a class="confirm" data-name="archive" href="helptopics.php?a=archive">
                                <i class="icon-folder-close icon-fixed-width"></i>
                                <?php echo __( 'Archive'); ?>
                            </a>
                        </li>
                        <li class="danger">
                            <a class="confirm" data-name="delete" href="helptopics.php?a=delete">
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
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="sort" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">

    <thead>
<tr><td colspan="7">
    <div style="padding:3px" class="pull-right"><?php echo __('Sorting Mode'); ?>:
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
</td></tr>
        <tr>
            <th width="4%" style="height:20px;">&nbsp;</th>
            <th style="padding-left:4px;vertical-align:middle" width="26%"><?php echo __('Help Topic'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="8%"><?php echo __('Status'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="8%"><?php echo __('Type'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="10%"><?php echo __('Priority'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="14%"><?php echo __('Department'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="15%" nowrap><?php echo __('Last Updated'); ?></th>
            <th style="padding-left:4px;vertical-align:middle" width="15%" nowrap><?php echo __('Created'); ?></th>
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

            $T = $topics;
            $names = $topics = array();
            foreach ($T as $topic) {
                $names[$topic->getId()] = $topic->getFullName();
                $topics[$topic->getId()] = $topic;
            }
            if ($cfg->getTopicSortMode() != 'm')
                $names = Internationalization::sortKeyedList($names);

            $defaultDept = $cfg->getDefaultDept();
            $defaultPriority = $cfg->getDefaultPriority();
            $sort = 0;
            foreach($names as $topic_id=>$name) {
                $topic = $topics[$topic_id];
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
                $priority = $topic->priority ?: $defaultPriority;
                ?>
            <tr id="<?php echo $id; ?>">
                <td align="center">
                  <input type="hidden" name="sort-<?php echo $id; ?>" value="<?php
                        echo $topic->sort ?: $sort; ?>"/>
                  <input type="checkbox" class="ckb" name="ids[]"
                    value="<?php echo $id; ?>" <?php
                    echo $sel ? 'checked="checked"' : ''; ?>>
                </td>
                <td>
                    <?php
                    if ($cfg->getTopicSortMode() == 'm') { ?>
                        <i class="icon-sort faded"></i>
                    <?php } ?>
                    <a href="helptopics.php?id=<?php echo $id; ?>"><?php
                    echo Topic::getTopicName($id); ?></a>&nbsp;
                </td>
                <td><?php
                  if($topic->getStatus() == __('Active'))
                    echo $topic->getStatus();
                  else
                    echo '<b>'.$topic->getStatus();
                  ?>
                </td>
                <td><?php echo $topic->ispublic ? __('Public') : '<b>'.__('Private').'</b>'; ?></td>
                <td><?php echo $priority; ?></td>
                <td><a href="departments.php?id=<?php echo $deptId;
                ?>"><?php echo $dept; ?></a></td>
                <td>&nbsp;<?php echo Format::datetime($topic->updated); ?></td>
                <td>&nbsp;<?php echo Format::datetime($topic->created); ?></td>
            </tr>
            <?php
            } //end of foreach.
        }?>
    <tfoot>
     <tr>
        <td colspan="8">
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
    <p class="confirm-action" style="display:none;" id="archive-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>archive</b> %s?'),
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
