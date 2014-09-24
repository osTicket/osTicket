<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

?>
<h1><?php echo __('Frequently Asked Questions');?></h1>
<form action="index.php" method="get" id="kb-search">
    <input type="hidden" name="a" value="search">
    <div>
    <input id="query" type="text" size="20" name="q" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>">
        <input id="searchSubmit" type="submit" value="<?php echo __('Search');?>">
    </div>
    <div class="sidebar">
        <select name="topicId" id="topic-id">
            <option value="">&mdash; <?php echo __('All Help Topics');?> &mdash;</option>
            <?php
foreach (Topic::objects()
        ->annotate(array('faqs_count'=>Aggregate::count('faqs')))
        ->filter(array('faqs_count__gt'=>0))
    as $t) {
                echo sprintf('<option value="%d" %s>%s</option>',
                    $t->getId(),
                    ($_REQUEST['topicId'] && $t->getId() == $_REQUEST['topicId']?'selected="selected"':''),
                    $t->getFullName());
            }
            ?>
        </select>
    </div>
</form>
<hr>
<div>
<?php
if($_REQUEST['q'] || $_REQUEST['cid'] || $_REQUEST['topicId']) { //Search.
    $sql='SELECT faq.faq_id, question '
        .' FROM '.FAQ_TABLE.' faq '
        .' LEFT JOIN '.FAQ_CATEGORY_TABLE.' cat ON(cat.category_id=faq.category_id) '
        .' LEFT JOIN '.FAQ_TOPIC_TABLE.' ft ON(ft.faq_id=faq.faq_id) '
        .' WHERE faq.ispublished=1 AND cat.ispublic=1';

    if($_REQUEST['cid'])
        $sql.=' AND faq.category_id='.db_input($_REQUEST['cid']);

    if($_REQUEST['topicId'])
        $sql.=' AND ft.topic_id='.db_input($_REQUEST['topicId']);


    if($_REQUEST['q']) {
        $sql.=" AND (question LIKE ('%".db_input($_REQUEST['q'],false)."%')
                 OR answer LIKE ('%".db_input($_REQUEST['q'],false)."%')
                 OR keywords LIKE ('%".db_input($_REQUEST['q'],false)."%')
                 OR cat.name LIKE ('%".db_input($_REQUEST['q'],false)."%')
                 OR cat.description LIKE ('%".db_input($_REQUEST['q'],false)."%')
                 )";
    }

    $sql.=' GROUP BY faq.faq_id ORDER BY question';
    echo "<div><strong>".__('Search Results').'</strong></div><div class="clear"></div>';
    if(($res=db_query($sql)) && ($num=db_num_rows($res))) {
        echo '<div id="faq">'.sprintf(__('%d FAQs matched your search criteria.'),$num).'
                <ol>';
        while($row=db_fetch_array($res)) {
            echo sprintf('
                <li><a href="faq.php?id=%d" class="previewfaq">%s</a></li>',
                $row['faq_id'],$row['question'],$row['ispublished']?__('Published'):__('Internal'));
        }
        echo '  </ol>
             </div>';
    } else {
        echo '<strong class="faded">'.__('The search did not match any FAQs.').'</strong>';
    }
} else { //Category Listing.
    $categories = Category::objects()
        ->filter(array('ispublic'=>true, 'faqs__ispublished'=>true))
        ->annotate(array('faq_count'=>Aggregate::count('faqs')))
        ->filter(array('faq_count__gt'=>0));
    if ($categories->all()) {
        echo '<div>'.__('Click on the category to browse FAQs.').'</div>
                <ul id="kb">';

        foreach ($categories as $C) { ?>
            <li><i></i>
            <h4><?php echo sprintf('<a href="faq.php?cid=%d">%s (%d)</a>',
                $C->getId(), Format::htmlchars($C->name), $C->faq_count); ?></h4>
                <?php echo Format::safe_html($C->description); ?>
<?php       foreach ($C->faqs->order_by('-view')->limit(5) as $F) { ?>
                <div class="popular-faq"><?php echo $F->question; ?></div>
<?php       } ?>
            </li>
<?php   } ?>
       </ul>
<?php
    } else {
        echo __('NO FAQs found');
    }
}
?>
</div>
