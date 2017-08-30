<?php
if(!defined('OSTSTAFFINC') || !$thisstaff) die('Access Denied');

?>

<div class="subnav">

    <div class="float-left subnavtitle">
                          
    <?php echo __('Frequently Asked Questions');?>                        
    
    </div>
    <div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">
   &nbsp;
      </div>   
   <div class="clearfix"></div> 
</div> 


<div class="card-box">
<div class="row">
<div class="col">
 <div class="float-right">
            <form  id="kbSearch" class="form-inline" action="kb.php" method="get">
                <input type="hidden" name="a" value="search">
                <input type="hidden" name="cid" value="<?php echo Format::htmlchars($_REQUEST['cid']); ?>"/>
                <input type="hidden" name="topicId" value="<?php echo Format::htmlchars($_REQUEST['topicId']); ?>"/>
                
                 <div class="input-group input-group-sm">
                 <input type="hidden" name="a" value="search">
                    <input id="query" type="text" class="form-control form-control-sm rlc-search basic-search" name="q" autofocus
                value="<?php echo Format::htmlchars($_REQUEST['q']); ?>"
                   autocomplete="off" autocorrect="off" autocapitalize="off" placeholder="Search KB" >
                <!-- <td>&nbsp;&nbsp;<a href="" id="advanced-user-search">[advanced]</a></td> -->
                    <button type="submit"  class="input-group-addon" id="searchSubmit" ><i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

<?php
$total = FAQ::objects()->count();

$categories = Category::objects()
    ->annotate(array('faq_count' => SqlAggregate::COUNT('faqs')))
    ->filter(array('faq_count__gt' => 0))
    ->order_by('name')
    ->all();
array_unshift($categories, new Category(array('id' => 0, 'name' => __('All Categories'), 'faq_count' => $total)));


      $cselected = Category::findNameById($_GET['cid']);
      if (!$cselected) {$cselected = 'Category';}
      
      
?>

<div class="btn-group btn-group-sm float-right m-b-10" role="group" aria-label="Button group with nested dropdown">

<div class="btn-group btn-group-sm" role="group">
        <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-placement="bottom" data-toggle="tooltip" 
         title="<?php echo __('Categories'); ?>"><i class="fa fa-filter"></i> <?php echo $cselected;?>
        </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="btnGroupDrop1">
              
                      <?php      
              foreach ($categories as $C) {
        $active = $_REQUEST['cid'] == $C->getId(); ?>
        
            <a class="dropdown-item no-pjax" href="kb.php?a=search&cid=<?php echo $C->getId(); ?>&topicId=<?php echo $_GET['topicId'];?>">
                <i class="fa fa-filter"></i>
                <?php echo sprintf('%s (%d)',
                    Format::htmlchars($C->getLocalName()),
                    $C->faq_count); ?></a>
         <?php
} ?>
                       
        
            </div>
    </div>


       

<?php
$topics = Topic::objects()
    ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')))
    ->filter(array('faq_count__gt'=>0))
    ->all();
usort($topics, function($a, $b) {
    return strcmp($a->getFullName(), $b->getFullName());
});
array_unshift($topics, new Topic(array('id' => 0, 'topic' => __('All Topics'), 'faq_count' => $total)));

 $tselected = Topic::getTopicName($_GET['topicId']);
      if (!$tselected) {$tselected = 'Help Topic';}
?>

<div class="btn-group btn-group-sm" role="group">
        <button id="btnGroupDrop1" type="button" class="btn btn-light dropdown-toggle" 
        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-placement="bottom" data-toggle="tooltip" 
         title="<?php echo __('Help Topics'); ?>"><i class="fa fa-filter"></i> <?php echo $tselected;?>
        </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="btnGroupDrop1">
              
               <?php foreach ($topics as $T) { ?>

            <a class="dropdown-item no-pjax" href="kb.php?a=search&cid=<?php echo $_GET['cid']; ?>&topicId=<?php echo $T->getId(); ?>">
                <i class="icon-fixed-width <?php
                if ($active) echo 'icon-hand-right'; ?>"></i>
                <?php echo sprintf('%s (%d)',
                    Format::htmlchars($T->getFullName()),
                    $T->faq_count); ?></a>
         <?php
} ?>
                       
        
            </div>
    </div>
</div>


</div>






</form>

<div class="col-sm-12"> 


<?php
if($_REQUEST['q'] || $_REQUEST['cid'] || $_REQUEST['topicId']) { //Search.
    $faqs = FAQ::objects()
        ->annotate(array(
            'attachment_count'=>SqlAggregate::COUNT('attachments'),
            'topic_count'=>SqlAggregate::COUNT('topics')
        ))
        ->order_by('question');

  

    if ($_GET['topicId'])
        $faqs->filter(array('topics__topic_id'=>$_GET['topicId']));

    if ($_REQUEST['q'])
        $faqs->filter(Q::ANY(array(
            'question__contains'=>$_REQUEST['q'],
            'answer__contains'=>$_REQUEST['q'],
            'keywords__contains'=>$_REQUEST['q'],
            'category__name__contains'=>$_REQUEST['q'],
            'category__description__contains'=>$_REQUEST['q'],
        )));

    echo "<div><strong>".__('Search Results')."</strong></div><div class='clear'></div>";
    if ($faqs->exists(true)) {
        echo '<div id="faq">
                <ol>';
        foreach ($faqs as $F) {
            echo sprintf(
                '<li><a href="faq.php?id=%d" class="previewfaq">%s</a> - <span>%s</span></li>',
                $F->getId(), $F->getLocalQuestion(), $F->getVisibilityDescription());
        }
        echo '  </ol>
             </div>';
    } else {
        echo '<strong class="faded">'.__('The search did not match any FAQs.').'</strong>';
    }
} else { //Category Listing.
    $categories = Category::objects()
        ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')));

    if (count($categories)) {
        $categories->sort(function($a) { return $a->getLocalName(); });
        echo '<div>'.__('Click on the category to browse FAQs or manage its existing FAQs.').'</div>
                <ul id="kb">';
        foreach ($categories as $C) {
            echo sprintf('
                <li>
                    <h4><a class="truncate" style="max-width:600px" href="kb.php?cid=%d">%s (%d)</a> - <span>%s</span></h4>
                    %s
                </li>',$C->getId(),$C->getLocalName(),$C->faq_count,
                $C->getVisibilityDescription(),
                Format::safe_html($C->getLocalDescriptionWithImages())
            );
        }
        echo '</ul>';
    } else {
        echo __('NO FAQs found');
    }
}
?>
</div>
</div>
