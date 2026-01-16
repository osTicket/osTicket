<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

?>
<?php
if($_REQUEST['q'] || $_REQUEST['cid'] || $_REQUEST['topicId']) { //Search
    $faqs = FAQ::allPublic()
        ->annotate(array(
            'attachment_count'=>SqlAggregate::COUNT('attachments'),
            'topic_count'=>SqlAggregate::COUNT('topics')
        ))
        ->order_by('question');

    if ($_REQUEST['cid'])
        $faqs->filter(array('category_id'=>$_REQUEST['cid']));

    if ($_REQUEST['topicId'])
        $faqs->filter(array('topics__topic_id'=>$_REQUEST['topicId']));

    if ($_REQUEST['q'])
        $faqs->filter(Q::all(array(
            Q::ANY(array(
                'question__contains'=>$_REQUEST['q'],
                'answer__contains'=>$_REQUEST['q'],
                'keywords__contains'=>$_REQUEST['q'],
                'category__name__contains'=>$_REQUEST['q'],
                'category__description__contains'=>$_REQUEST['q'],
            ))
        )));

    include CLIENTINC_DIR . 'kb-search.inc.php';

} else { //Category Listing.
    include CLIENTINC_DIR . 'kb-categories.inc.php';
}
?>
