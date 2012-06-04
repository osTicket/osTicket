<?php
if(!defined('OSTSTAFFINC') || !$thisstaff) die('Access Denied');

?>
<h2>Frequently Asked Questions</h2>
<form id="kbSearch" action="kb.php" method="get">
    <input type="hidden" name="a" value="search">
    <div>
        <input id="query" type="text" size="20" name="q" value="<?php echo Format::htmlchars($_REQUEST['q']); ?>">
        <select name="cid" id="cid">
            <option value="">&mdash; All Categories &mdash;</option>
            <?php
            $sql='SELECT category_id, name, count(faq.category_id) as faqs '
                .' FROM '.FAQ_CATEGORY_TABLE.' cat '
                .' LEFT JOIN '.FAQ_TABLE.' faq USING(category_id) '
                .' GROUP BY cat.category_id '
                .' HAVING faqs>0 '
                .' ORDER BY cat.name DESC ';
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while($row=db_fetch_array($res))
                    echo sprintf('<option value="%d" %s>%s (%d)</option>',
                            $row['category_id'],
                            ($_REQUEST['cid'] && $row['category_id']==$_REQUEST['cid']?'selected="selected"':''),
                            $row['name'],
                            $row['faqs']);
            }
            ?>
        </select>
        <input id="searchSubmit" type="submit" value="Search">
    </div>
    <div>
        <select name="topicId" style="width:350px;" id="topic-id">
            <option value="">&mdash; All Help Topics &mdash;</option>
            <?php
            $sql='SELECT ht.topic_id, ht.topic, count(faq.topic_id) as faqs '
                .' FROM '.TOPIC_TABLE.' ht '
                .' LEFT JOIN '.FAQ_TOPIC_TABLE.' faq USING(topic_id) '
                .' GROUP BY ht.topic_id '
                .' HAVING faqs>0 '
                .' ORDER BY ht.topic DESC ';
            if(($res=db_query($sql)) && db_num_rows($res)) {
                while($row=db_fetch_array($res))
                    echo sprintf('<option value="%d" %s>%s (%d)</option>',
                            $row['topic_id'],
                            ($_REQUEST['topicId'] && $row['topic_id']==$_REQUEST['cid']?'selected="selected"':''),
                            $row['topic'], $row['faqs']);
            }
            ?>
        </select>
    </div>
</form>
<hr>
<div>
<?php
if($_REQUEST['q'] || $_REQUEST['cid'] || $_REQUEST['topicId']) { //Search.
    $sql='SELECT faq.faq_id, question, ispublished, count(attach.file_id) as attachments '
        .' FROM '.FAQ_TABLE.' faq '
        .' LEFT JOIN '.FAQ_ATTACHMENT_TABLE.' attach ON(attach.faq_id=faq.faq_id) '
        .' WHERE 1 ';
    if($_REQUEST['cid'])
        $sql.=' AND faq.category_id='.db_input($_REQUEST['cid']);

    if($_REQUEST['q'])
        $sql.=" AND MATCH(question,answer,keywords) AGAINST ('".db_input($_REQUEST['q'],false)."')";

    $sql.=' GROUP BY faq.faq_id';
    echo "<div><strong>Search Results</strong></div><div class='clear'></div>";
    if(($res=db_query($sql)) && db_num_rows($res)) {
        echo '<div id="faq">
                <ol>';
        while($row=db_fetch_array($res)) {
            echo sprintf('
                <li><a href="faq.php?id=%d" class="previewfaq">%s</a> - <span>%s</span></li>',
                $row['faq_id'],$row['question'],$row['ispublished']?'Published':'Internal');
        }
        echo '  </ol>
             </div>';
    } else {
        echo '<strong class="faded">The search did not match any FAQs.</strong>';
    }
} else { //Category Listing.
    $sql='SELECT cat.category_id, cat.name, cat.description, cat.ispublic, count(faq.faq_id) as faqs '
        .' FROM '.FAQ_CATEGORY_TABLE.' cat '
        .' LEFT JOIN '.FAQ_TABLE.' faq ON(faq.category_id=cat.category_id) '
        .' GROUP BY cat.category_id '
        .' ORDER BY cat.name';
    if(($res=db_query($sql)) && db_num_rows($res)) {
        echo '<div>Click on the category to browse FAQs.</div>
                <ul id="kb">';
        while($row=db_fetch_array($res)) {

            echo sprintf('
                <li>
                    <h4><a href="kb.php?cid=%d">%s (%d)</a> - <span>%s</span></h4>
                    %s
                </li>',$row['category_id'],$row['name'],$row['faqs'],
                ($row['ispublic']?'Public':'Internal'),
                Format::safe_html($row['description']));
        }
        echo '</ul>';
    } else {
        echo 'NO FAQs found';
    }
}
?>
</div>
