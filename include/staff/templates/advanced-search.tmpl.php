<?php
$parent_id = $_REQUEST['parent_id'] ?: $search->parent_id;
if ($parent_id
    && (!($parent = CustomQueue::lookup($parent_id)))
) {
    $parent_id = 0;
}

$queues = array();
foreach (CustomQueue::queues() as  $q)
    $queues[$q->id] = $q->getFullName();
asort($queues);
$queues = array(0 => ('—'.__("My Searches").'—')) + $queues;
$queue = $search;
$qname = $search->getName() ?:  __('Advanced Ticket Search');
?>
<div id="advanced-search" class="advanced-search">
<h3 class="drag-handle"><?php echo Format::htmlchars($qname); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<?php
$info['error'] = $info['error'] ?: $errors['err'];
if ($info['error']) {
    echo sprintf('<p id="msg_error">%s</p>', $info['error']);
} elseif ($info['warn']) {
    echo sprintf('<p id="msg_warning">%s</p>', $info['warn']);
} elseif ($info['msg']) {
    echo sprintf('<p id="msg_notice">%s</p>', $info['msg']);
} ?>
<form action="#tickets/search" method="post" name="search" id="advsearch"
    class="<?php echo ($search->isSaved() || $parent) ? 'savedsearch' : 'adhocsearch'; ?>">
  <input type="hidden" name="id" value="<?php echo $search->getId(); ?>">
  <div class="flex row">
    <div class="span12">
      <select id="parent" name="parent_id" >
          <?php
foreach ($queues as $id => $name) {
    ?>
          <option value="<?php echo $id; ?>"
              <?php if ($parent_id == $id) echo 'selected="selected"'; ?>
              ><?php echo $name; ?></option>
<?php       } ?>
      </select>
    </div>
   </div>
<ul class="clean tabs">
    <li class="active"><a href="#criteria"><i class="icon-search"></i> <?php echo __('Criteria'); ?></a></li>
    <li><a href="#columns"><i class="icon-columns"></i> <?php echo __('Columns'); ?></a></li>
    <li><a href="#fields"><i class="icon-download"></i> <?php echo __('Export'); ?></a></li>
</ul>

<div class="tab_content" id="criteria">
  <div class="flex row">
    <div class="span12" style="overflow-y: auto; height:auto;">
      <div class="error"><?php echo Format::htmlchars($errors['criteria']); ?></div>
      <div class="faded <?php echo $parent ? ' ': 'hidden'; ?>"
            id="inherited-parent" style="margin-bottom: 1em">

      <div>
        <strong><a href="#" id="parent-info"><i class="icon-caret-right"></i>&nbsp;<?php
            echo sprintf('%s (<span id="parent-name">%s</span>)',
                __('Inherited Criteria'),
                $parent ? $parent->getName() : '');
      ?></a></strong>
      </div>
      <div id="parent-criteria" class="hidden">
        <?php echo $parent ? nl2br(Format::htmlchars($parent->describeCriteria())) : ''; ?>
      </div>
      </div>
      <input type="hidden" name="a" value="search">
      <?php include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php'; ?>
    </div>
  </div>

</div>

<div class="tab_content hidden" id="columns" style="overflow-y: auto;
height:auto;">
    <?php
    include STAFFINC_DIR . "templates/queue-columns.tmpl.php";
    ?>
</div>
<div class="tab_content hidden" id="fields">
    <?php
    include STAFFINC_DIR . "templates/queue-fields.tmpl.php";  ?>
</div>
   <?php
   $save = (($parent && !$search->isSaved()) || $errors); ?>
  <div style="margin-top:10px;"><a href="#"
    id="save"><i class="icon-caret-<?php echo $save ? 'down' : 'right';
    ?>"></i>&nbsp;<span><?php echo __('Save Search'); ?></span></a></div>
  <div id="save-changes" class="<?php echo $save ? '' : 'hidden'; ?>" style="padding:5px; border-top: 1px dotted #777;">
      <div><input name="name" type="text" size="40"
        value="<?php echo $search->isSaved() ? Format::htmlchars($search->getName()) : ''; ?>"
        placeholder="<?php echo __('Search Title'); ?>">
        <span class="buttons">
             <button class="button" type="button" name="save"
             value="save"><i class="icon-save"></i>  <?php echo $search->id
             ? __('Save Changes') : __('Save'); ?></button>
        </span>
        </div>
      <div class="error" id="name-error"><?php echo Format::htmlchars($errors['name']); ?></div>
  </div>
  <hr/>
 <div>
  <p class="full-width">
    <span class="buttons pull-left">
        <input type="reset"  id="reset"  value="<?php echo __('Reset'); ?>">
        <input type="button" name="cancel" class="close"
        value="<?php echo __('Cancel'); ?>">
    </span>
    <span class="buttons pull-right">
      <button class="button" type="submit" name="submit" value="search"
        id="do_search"><i class="icon-search"></i>
        <?php echo __('Search'); ?></button>
    </span>
   </p>
 </div>
</form>

<script>
+function() {
   // Return a helper with preserved width of cells
   var fixHelper = function(e, ui) {
      ui.children().each(function() {
          $(this).width($(this).width());
      });
      return ui;
   };
   // Sortable tables for dynamic forms objects
   $('.sortable-rows').sortable({
       'helper': fixHelper,
       'cursor': 'move',
       'stop': function(e, ui) {
           var attr = ui.item.parent('tbody').data('sort'),
               offset = parseInt($('#sort-offset').val(), 10) || 0;
           warnOnLeave(ui.item);
           $('input[name^='+attr+']', ui.item.parent('tbody')).each(function(i, el) {
               $(el).val(i + 1 + offset);
           });
       }
    });

    $('a#parent-info').click(function() {
        var $this = $(this);
        $('#parent-criteria').slideToggle('fast', function(){
           if ($(this).is(":hidden"))
            $this.find('i').removeClass('icon-caret-down').addClass('icon-caret-right');
           else
            $this.find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
        });
        return false;
    });

    $('form select#parent').change(function() {
        var form = $(this).closest('form');
        var qid = parseInt($(this).val(), 10) || 0;

        if (qid > 0) {
            $.ajax({
                type: "GET",
                url: 'ajax.php/queue/'+qid,
                dataType: 'json',
                success: function(queue) {
                    $('#parent-name', form).html(queue.name);
                    $('#parent-criteria', form).html(queue.criteria);
                    $('#inherited-parent', form).fadeIn();
                    }
                })
                .done(function() { })
                .fail(function() { });
        } else {
            $('#inherited-parent', form).fadeOut();
        }
    });

    $('a#save').click(function() {
        var $this = $(this);
        $('#save-changes').slideToggle('fast', function(){
           if ($(this).is(":hidden"))
            $this.find('i').removeClass('icon-caret-down').addClass('icon-caret-right');
           else
            $this.find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
        });
        return false;
    });

    $('form.savedsearch').on('keyup change paste', 'input, select, textarea', function() {
       var form = $(this).closest('form');
       $this = $('#save-changes', form);
       if ($this.is(":hidden"))
           $this.fadeIn();
        $('a#save').find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
        $('button[name=save]', form).addClass('save pending');
        $('div.error', form).html('');
     });

    $(document).on('click', 'form#advsearch input#reset', function(e) {
        var f = $(this).closest('form');
        $('button[name=save]', f).removeClass('save pending');
        $('div#save-changes', f).hide();
    });

    $('button[name=save]').click(function() {
        var $form = $(this).closest('form');
        var id = parseInt($('input[name=id]', $form).val(), 10) || 0;
        var action = '#tickets/search';
        if (id > 0)
            action = action + '/'+id;

        $form.prop('action', action+'/save');
        $form.submit();
    });

}();
</script>
