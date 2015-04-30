<?php
if(!defined('OSTSTAFFINC') || !$thisstaff) die('Access Denied');

?>
<h2><?php echo __('Frequently Asked Questions');?></h2>
<form id="kbSearch" action="kb.php" method="get">
    <input type="hidden" name="a" value="search">
    <div>
        <input id="query" type="text" size="20" name="q" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>">
        <select name="cid" id="cid">
            <option value="">&mdash; <?php echo __('All Categories');?> &mdash;</option>
            <?php
            $categories = Category::objects()
                ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')))
                ->filter(array('faq_count__gt'=>0))
                ->order_by('name');
print $categories;
            foreach ($categories as $C) {
                echo sprintf('<option value="%d" %s>%s (%d)</option>',
                    $C->getId(),
                    ($_REQUEST['cid'] && $C->getId()==$_REQUEST['cid']?'selected="selected"':''),
                    $C->getLocalName(),
                    $C->faq_count
                );
            } ?>
        </select>
        <input id="searchSubmit" type="submit" value="<?php echo __('Search');?>">
    </div>
    <div>
        <select name="topicId" style="width:350px;" id="topic-id">
            <option value="">&mdash; <?php echo __('All Help Topics');?> &mdash;</option>
            <?php
            $topics = Topic::objects()
                ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')))
                ->filter(array('faq_count__gt'=>0))
                ->all();
            usort($topics, function($a, $b) {
                return strcmp($a->getFullName(), $b->getFullName());
            });
            foreach ($topics as $T) {
                echo sprintf('<option value="%d" %s>%s (%d)</option>',
                    $T->getId(),
                    ($_REQUEST['topicId'] && $T->getId()==$_REQUEST['topicId']?'selected="selected"':''),
                    $T->getFullName(), $T->faq_count);
            }
            ?>
        </select>
    </div>
</form>
<hr>
<div>
<?php
if($_REQUEST['q'] || $_REQUEST['cid'] || $_REQUEST['topicId']) { //Search.
    $faqs = FAQ::objects()
        ->annotate(array(
            'attachment_count'=>SqlAggregate::COUNT('attachments'),
            'topic_count'=>SqlAggregate::COUNT('topics')
        ))
        ->order_by('question');

    if ($_REQUEST['cid'])
        $faqs->filter(array('category_id'=>$_REQUEST['cid']));

    if ($_REQUEST['topicId'])
        $faqs->filter(array('topic_id'=>$_REQUEST['topicId']));

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
        ->annotate(array('faq_count'=>SqlAggregate::COUNT('faqs')))
        ->all();

    if (count($categories)) {
        usort($categories, function($a, $b) {
            return strcmp($a->getLocalName(), $b->getLocalName());
        });
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
