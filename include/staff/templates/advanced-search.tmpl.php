<?php
global $thisstaff;

$parent_id = $_REQUEST['parent_id'] ?: $search->parent_id;
if ($parent_id
    && is_numeric($parent_id)
    && (!($parent = SavedQueue::lookup($parent_id)))
) {
    $parent_id = 0;
}

$editable = $search->checkOwnership($thisstaff);
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
}

// Form action
$action = '#tickets/search';
if ($search->isSaved() && $search->getId())
    $action .= sprintf('/%s/save', $search->getId());
elseif (!$search instanceof AdhocSearch)
    $action .= '/save';
?>
<form action="<?php echo $action; ?>" method="post" name="search" id="advsearch"
    class="<?php echo ($search->isSaved() || $parent) ? 'savedsearch' : 'adhocsearch'; ?>">
  <input type="hidden" name="id" value="<?php echo $search->getId(); ?>">
<?php
if ($editable) {
    ?>
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
<?php
} ?>
<ul class="clean tabs">
    <li class="active"><a href="#criteria"><i class="icon-search"></i> <?php echo __('Criteria'); ?></a></li>
    <li><a href="#columns"><i class="icon-columns"></i> <?php echo __('Columns'); ?></a></li>
    <?php
    if ($search->isSaved()) { ?>
    <li><a href="#settings"><i class="icon-cog"></i> <?php echo __('Settings'); ?></a></li>
    <?php
    } ?>
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
     <?php
        include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php';
     ?>
    </div>
  </div>

</div>

<div class="tab_content hidden" id="columns">
    <?php
    include STAFFINC_DIR . "templates/queue-columns.tmpl.php";
    ?>
</div>
<?php
if ($search->isSaved()) { ?>
<div class="tab_content hidden" id="settings">
    <?php
    include STAFFINC_DIR . "templates/savedqueue-settings.tmpl.php";
    ?>
</div>
<?php
} else { // Not saved.
    $save = (($parent && !$search->isSaved()) || isset($_POST['queue-name']));
?>
<div>
  <div style="margin-top:10px;"><a href="#"
    id="save"><i class="icon-caret-<?php echo $save ? 'down' : 'right';
    ?>"></i>&nbsp;<span><?php echo __('Save Search'); ?></span></a></div>
  <div id="save-changes" class="<?php echo $save ? '' : 'hidden'; ?>" style="padding:5px; border-top: 1px dotted #777;">
      <div><input name="queue-name" type="text" size="40"
        value="<?php echo Format::htmlchars($search->isSaved() ? $search->getName() :
        $_POST['queue-name']); ?>"
        placeholder="<?php echo __('Search Title'); ?>">
        <?php
        if ($search instanceof AdhocSearch && !$search->isSaved()) { ?>
        <span class="buttons">
             <button class="save button" type="button"  name="save-search"
             value="save"><i class="icon-save"></i>  <?php echo $search->id
             ? __('Save Changes') : __('Save'); ?></button>
        </span>
        <?php
        } ?>
        </div>
      <div class="error" id="name-error"><?php echo
      Format::htmlchars($errors['queue-name']); ?></div>
  </div>
 </div>
<?php
} ?>
  <hr/>
 <div>
  <p class="full-width">
    <span class="buttons pull-left">
        <input type="button"  name="cancel"  class="close" value="<?php echo __('Cancel'); ?>">
        <?php
        if ($search->isSaved()) { ?>
        <input type="button" name="done" class="done" value="<?php echo
            __('Done'); ?>" >
        <?php
        } ?>
    </span>
    <span class="buttons pull-right">
      <?php
      if (!$search instanceof AdhocSearch) { ?>
      <button class="save button" type="submit" name="save" value="save"
        id="do_save"><i class="icon-save"></i>
        <?php echo __('Save'); ?></button>
      <?php
      } else { ?>
      <button class="button" type="submit" name="submit" value="search"
        id="do_search"><i class="icon-search"></i>
        <?php echo __('Search'); ?></button>
      <?php
      } ?>
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

    $('form#advsearch').on('keyup change paste', 'input, select, textarea', function() {

        var form = $(this).closest('form');
        $this = $('#save-changes', form);
        $('button.save', form).addClass('save pending');
        $('div.error, div.error-banner', form).html('').hide();
     });

    $(document).on('click', 'form#advsearch input#reset', function(e) {
        var f = $(this).closest('form');
        $('button.save', f).removeClass('save pending');
        $('div#save-changes', f).hide();
    });

    $('button[name=save-search]').click(function() {
        var $form = $(this).closest('form');
        var id = parseInt($('input[name=id]', $form).val(), 10) || 0;
        var name = $('input[name=queue-name]', $form).val();
        if (name.length) {
            var action = '#tickets/search';
            if (id > 0)
                action = action + '/'+id;
            $form.prop('action', action+'/save');
            $form.submit();
        } else {
            $('div#name-error', $form).html('<?php echo __('Name required');
                    ?>').show();
        }

        return false;
    });

    $('input.done').click(function() {
        var $form = $(this).closest('form');
        var id = parseInt($('input[name=id]', $form).val(), 10) || 0;
        if ($('button.save', $form).hasClass('pending'))
            alert('Unsaved Changes - save or cancel to discard!');
        else
            window.location.href = 'tickets.php?queue='+id;
    });
}();
</script>
