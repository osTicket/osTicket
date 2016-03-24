<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$qs = array();
if($_REQUEST['type']) {
    $qs += array('type' => $_REQUEST['type']);
}
$type=null;
switch(strtolower($_REQUEST['type'])){
    case 'error':
        $title=__('Errors');
        $type=$_REQUEST['type'];
        break;
    case 'warning':
        $title=__('Warnings');
        $type=$_REQUEST['type'];
        break;
    case 'debug':
        $title=__('Debug logs');
        $type=$_REQUEST['type'];
        break;
    default:
        $type=null;
        $title=__('All logs');
}

$qwhere =' WHERE 1';
//Type
if($type)
    $qwhere.=' AND log_type='.db_input($type);

//dates
$startTime  =($_REQUEST['startDate'] && (strlen($_REQUEST['startDate'])>=8))?strtotime($_REQUEST['startDate']):0;
$endTime    =($_REQUEST['endDate'] && (strlen($_REQUEST['endDate'])>=8))?strtotime($_REQUEST['endDate']):0;
if( ($startTime && $startTime>time()) or ($startTime>$endTime && $endTime>0)){
    $errors['err']=__('Entered date span is invalid. Selection ignored.');
    $startTime=$endTime=0;
}else{
    if($startTime){
        $qwhere.=' AND created>=FROM_UNIXTIME('.$startTime.')';
        $qs += array('startDate' => $_REQUEST['startDate']);
    }
    if($endTime){
        $qwhere.=' AND created<=FROM_UNIXTIME('.$endTime.')';
        $qs += array('endDate' => $_REQUEST['endDate']);
    }
}
$sortOptions=array('id'=>'log.log_id', 'title'=>'log.title','type'=>'log_type','ip'=>'log.ip_address'
                    ,'date'=>'log.created','created'=>'log.created','updated'=>'log.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'id';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'log.created';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}
$order=$order?$order:'DESC';

if($order_column && strpos($order_column,',')){
    $order_column=str_replace(','," $order,",$order_column);
}
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$qselect = 'SELECT log.* ';
$qfrom=' FROM '.SYSLOG_TABLE.' log ';
$total=db_count("SELECT count(*) $qfrom $qwhere");
$page = ($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
//pagenate
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$pageNav->setURL('logs.php',$qs);
$qs += array('order' => ($order=='DESC' ? 'ASC' : 'DESC'));
$qstr = '&amp;'. Http::build_query($qs);
$query="$qselect $qfrom $qwhere ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' '.$title;
else
    $showing=__('No logs found!');
?>

<div id="basic_search">
    <div style="height:25px">
        <div id='filter' >
            <form action="logs.php" method="get">
                <div style="padding-left:2px;">
                    <i class="help-tip icon-question-sign" href="#date_span"></i>
                    <?php echo __('Between'); ?>:
                    <input class="dp" id="sd" size=15 name="startDate" value="<?php echo Format::htmlchars($_REQUEST['startDate']); ?>" autocomplete=OFF>
                    &nbsp;&nbsp;
                    <input class="dp" id="ed" size=15 name="endDate" value="<?php echo Format::htmlchars($_REQUEST['endDate']); ?>" autocomplete=OFF>
                    &nbsp;<?php echo __('Log Level'); ?>:&nbsp;<i class="help-tip icon-question-sign" href="#type"></i>
                    <select name='type'>
                        <option value="" selected><?php echo __('All');?></option>
                        <option value="Error" <?php echo ($type=='Error')?'selected="selected"':''; ?>><?php echo __('Errors');?></option>
                        <option value="Warning" <?php echo ($type=='Warning')?'selected="selected"':''; ?>><?php echo __('Warnings');?></option>                <option value="Debug" <?php echo ($type=='Debug')?'selected="selected"':''; ?>><?php echo __('Debug');?></option>
                    </select>
                    &nbsp;&nbsp;
                    <input type="submit" Value="<?php echo __('Go!');?>" />
                </div>
            </form>
        </div>
    </div>
</div>
<div class="clear"></div>
<form action="logs.php" method="POST" name="logs">
    <div style="margin-bottom:20px; padding-top:5px;">
        <div class="sticky bar opaque">
            <div class="content">
                <div class="pull-left flush-left">
                    <h2><?php echo __('System Logs');?>
            <i class="help-tip icon-question-sign" href="#system_logs"></i>
            </h2>
                </div>
                <div id="actions" class="pull-right flush-right">
                    <button class="red button" type="submit" name="delete"><i class="icon-trash"></i>
                        <?php echo __( 'Delete Selected Entries');?>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="40%"><a <?php echo $title_sort; ?> href="logs.php?<?php echo $qstr; ?>&sort=title"><?php echo __('Log Title');?></a></th>
            <th width="11%"><a  <?php echo $type_sort; ?> href="logs.php?<?php echo $qstr; ?>&sort=type"><?php echo __('Log Type');?></a></th>
            <th width="30%" nowrap><a  <?php echo $date_sort; ?>href="logs.php?<?php echo $qstr; ?>&sort=date"><?php echo __('Log Date');?></a></th>
            <th width="15%"><a  <?php echo $ip_sort; ?> href="logs.php?<?php echo $qstr; ?>&sort=ip"><?php echo __('IP Address');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            while ($row = db_fetch_array($res)) {
                $sel=false;
                if($ids && in_array($row['log_id'],$ids))
                    $sel=true;
                ?>
            <tr id="<?php echo $row['log_id']; ?>">
                <td align="center" nowrap>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['log_id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?>> </td>
                <td>&nbsp;<a class="tip" href="#log/<?php echo $row['log_id']; ?>"><?php echo Format::htmlchars($row['title']); ?></a></td>
                <td><?php echo $row['log_type']; ?></td>
                <td>&nbsp;<?php echo Format::daydatetime($row['created']); ?></td>
                <td><?php echo $row['ip_address']; ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    </tbody>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if($res && $num){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No logs found');
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
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected log entry', 'selected log entries', 2));?></strong></font>
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
