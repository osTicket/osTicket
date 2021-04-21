<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$filter) die('Access Denied');

$qs = array();
$select='SELECT rule.* ';
$from='FROM '.FILTER_RULE_TABLE.' rule ';
$where='WHERE rule.filter_id='.db_input($filter->getId());
$search=false;
if($_REQUEST['q'] && strlen($_REQUEST['q'])>3) {
    $search=true;
    if(strpos($_REQUEST['q'],'@') && Validator::is_email($_REQUEST['q']))
        $where.=' AND rule.val='.db_input($_REQUEST['q']);
    else
        $where.=' AND rule.val LIKE "%'.db_input($_REQUEST['q'],false).'%"';

}elseif($_REQUEST['q']) {
    $errors['q']=__('Term too short!');
}

$sortOptions=array('email'=>'rule.val','status'=>'isactive','created'=>'rule.created','created'=>'rule.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'email';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'rule.val';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}

$order=$order?$order:'ASC';
if($order_column && strpos($order_column,',')){
    $order_column=str_replace(','," $order,",$order_column);
}
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(DISTINCT rule.id) '.$from.' '.$where);
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('banlist.php', $qs);
$qstr.='&amp;order='.($order=='DESC'?'ASC':'DESC');
$query="$select $from $where ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
//echo $query;
?>
<div id='basic_search'>
    <div style="height:25px">
        <form action="banlist.php" method="GET" name="filter">
            <input type="hidden" name="a" value="filter" >
            <div class="attached input">
                <input name="q" type="text" class="basic-search" size="30" autofocus
                       value="<?php echo Format::htmlchars($_REQUEST['q']); ?>">
                <button type="submit" class="attached button"><i class="icon-search"></i></button>
            </div>
        </form>
    </div>
</div>
<div class="clear"></div>
<form action="banlist.php" method="POST" name="banlist">
    <div style="margin-bottom:20px; padding-top:5px;">
        <div class="sticky bar opaque">
            <div class="content">
                <div class="pull-left flush-left">
                    <h2><?php echo __('Banned Email Addresses');?>
                        <i class="help-tip icon-question-sign" href="#ban_list"></i>
                    </h2>
                </div>
                <div class="pull-right flush-right">
                    <a href="banlist.php?a=add" class="red button action-button">
                        <i class="icon-ban-circle"></i> <?php echo __('Ban New Email');?></a>
                    <span class="action-button" data-dropdown="#action-dropdown-more">
                        <i class="icon-caret-down pull-right"></i>
                    <span ><i class="icon-cog"></i> <?php echo __('More');?></span>                        </span>
                    <div id="action-dropdown-more" class="action-dropdown anchor-right">
                        <ul id="actions">
                            <li><a class="confirm" data-name="enable" href="banlist.php?a=enable">
                                <i class="icon-ok-sign icon-fixed-width"></i>
                                <?php echo __('Enable'); ?></a></li>
                            <li><a class="confirm" data-name="disable" href="banlist.php?a=disable">
                                <i class="icon-ban-circle icon-fixed-width"></i>
                                <?php echo __('Disable'); ?></a></li>
                            <li><a class="confirm" data-name="delete" href="banlist.php?a=delete">                                <i class="icon-undo icon-fixed-width"></i>
                                <?php echo __('Remove'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="clear"></div>
    <?php
    if(($res=db_query($query)) && ($num=db_num_rows($res)))
        $showing=$pageNav->showing();
    else
        $showing=__('No banned emails matching the query found!');

    if($search)
        $showing=__('Search Results').': '.$showing;

    ?>
    <?php csrf_token(); ?>
    <input type="hidden" name="do" value="mass_process" >
    <input type="hidden" id="action" name="a" value="" >
    <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
        <thead>
            <tr>
                <th width="4%">&nbsp;</th>
                <th width="56%"><a <?php echo $email_sort; ?> href="banlist.php?<?php echo $qstr; ?>&sort=email"><?php echo __('Email Address');?></a></th>
                <th width="10%"><a  <?php echo $status_sort; ?> href="banlist.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Ban Status');?></a></th>
                <th width="10%"><a <?php echo $created_sort; ?> href="banlist.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Date Added');?></a></th>
                <th width="20%"><a <?php echo $updated_sort; ?> href="banlist.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
            </tr>
        </thead>
        <tbody>
        <?php
            if($res && db_num_rows($res)):
                $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
                while ($row = db_fetch_array($res)) {
                    $sel=false;
                    if($ids && in_array($row['id'],$ids))
                        $sel=true;
                    ?>
                   <tr id="<?php echo $row['id']; ?>">
                    <td align="center">
                      <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['id']; ?>" <?php echo $sel?'checked="checked"':''; ?>>
                    </td>
                    <td>&nbsp;<a href="banlist.php?id=<?php echo $row['id']; ?>"><?php echo Format::htmlchars($row['val']); ?></a></td>
                    <td>&nbsp;&nbsp;<?php echo $row['isactive']?__('Active'):'<b>'.__('Disabled').'</b>'; ?></td>
                    <td><?php echo Format::date($row['created']); ?></td>
                    <td><?php echo Format::datetime($row['updated']); ?>&nbsp;</td>
                   </tr>
                <?php
                } //end of while.
            endif; ?>
        <tfoot>
         <tr>
            <td colspan="5">
                <?php if($res && $num){ ?>
                <?php echo __('Select');?>:&nbsp;
                <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
                <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
                <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
                <?php }else{
                    echo __('No banned emails found!');
                } ?>
            </td>
         </tr>
        </tfoot>
    </table>
    <?php
    if($res && $num): //Show options..
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
            _N('selected ban rule', 'selected ban rules', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected ban rule', 'selected ban rules', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected ban rule', 'selected ban rules', 2));?></strong></font>
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

