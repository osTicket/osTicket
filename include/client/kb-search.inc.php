<div class="row">
  <div class="col-xs-12 col-sm-8">
    <h1><?php echo __('Frequently Asked Questions');?></h1>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><?php echo __('Search Results'); ?></h3>
      </div>
      <?php
      if ($faqs->exists(true)) {
        echo '<div class="panel-body text-muted">'.sprintf(__('%d FAQs matched your search criteria.'),
            $faqs->count())
            .'<ul class="list-group">';
        foreach ($faqs as $F) {
            echo sprintf(
                '<li class="list-group-item"><a href="faq.php?id=%d" class="previewfaq">%s</a></li>',
                $F->getId(), $F->getLocalQuestion(), $F->getVisibilityDescription());
        }
        echo '</ul>';
      } else {
        echo '<div class="panel-body text-muted">'.__('The search did not match any FAQs.').'</div>';
      }?>
    </div>
  </div>
  <div class="col-xs-12 col-sm-4">
    <div class="sidebar">
      <div class="searchbar">
        <form method="get" action="faq.php">
          <input type="hidden" name="a" value="search"/>
          <input class="form-control" type="text" name="q" class="search" placeholder="<?php
              echo __('Search our knowledge base'); ?>"/>
          <input type="submit" style="display:none" value="search"/>
        </form>
      </div>
      <br/>
      <div class="content clearfix">
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h3 class="panel-title"><?php echo __('Help Topics'); ?></h3>
          </div>
          <ul class="list-group">
            <?php
            foreach (Topic::objects()
              ->annotate(array('faqs_count'=>SqlAggregate::count('faqs')))
              ->filter(array('faqs_count__gt'=>0))
              as $t) { ?>
              <li class="list-group-item">
                <a href="?topicId=<?php echo urlencode($t->getId()); ?>">
                  <?php echo $t->getFullName(); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
        <div class="panel panel-primary">
          <div class="panel-heading">
            <h3 class="panel-title"><?php echo __('Categories'); ?></h3>
          </div>
          <ul class="list-group">
            <?php
            foreach (Category::objects()
              ->annotate(array('faqs_count'=>SqlAggregate::count('faqs')))
              ->filter(array('faqs_count__gt'=>0)) as $C) {
              ?>
              <li class="list-group-item">
                <a href="?cid=<?php echo urlencode($C->getId()); ?>">
                  <?php echo $C->getLocalName(); ?>
                </a>
              </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
