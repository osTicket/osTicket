    <h3><?php echo __('Item Properties'); ?> &mdash; <?php echo $item->getValue() ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <form method="post" action="ajax.php/list/<?php
            echo $list->getId(); ?>/item/<?php
            echo $item->getId(); ?>/properties" onsubmit="javascript:
            var form = $(this);
            $.post(this.action, form.serialize(), function(data, status, xhr) {
                if (!data.length) {
                    form.closest('.dialog').hide();
                    $('#overlay').hide();
                } else {
                    form.closest('.dialog .body').empty().append(data);
                }
            });
            return false;">
        <?php
        echo csrf_token();
        $config = $item->getConfiguration();
        $internal = $item->isInternal();
        foreach ($item->getConfigurationForm()->getFields() as $f) {
            $name = $f->get('id');
            if (isset($config[$name]))
                $f->value = $f->to_php($config[$name]);
            else if ($f->get('default'))
                $f->value = $f->get('default');
            ?>
            <div class="custom-field">
            <div class="field-label">
            <label for="<?php echo $f->getWidget()->name; ?>"
                style="vertical-align:top;padding-top:0.2em">
                <?php echo Format::htmlchars($f->get('label')); ?>:</label>
                <?php
                if (!$internal && $f->isEditable() && $f->get('hint')) { ?>
                    <br /><em style="color:gray;display:inline-block"><?php
                        echo Format::htmlchars($f->get('hint')); ?></em>
                <?php
                } ?>
            </div><div>
            <?php
            $f->render();
            if ($internal && !$f->isEditable())
                $f->render('view');
            else {
                $f->render();
                if ($f->get('required')) { ?>
                    <font class="error">*</font>
                <?php
                }
            }
            ?>
            </div>
            <?php
            foreach ($f->errors() as $e) { ?>
                <div class="error"><?php echo $e; ?></div>
            <?php } ?>
            </div>
            <?php
        }
        ?>
        </table>
        <hr>
        <p class="full-width">
            <span class="buttons" style="float:left">
                <input type="reset" value="<?php echo __('Reset'); ?>">
                <input type="button" value="<?php echo __('Cancel'); ?>" class="close">
            </span>
            <span class="buttons" style="float:right">
                <input type="submit" value="<?php echo __('Save'); ?>">
            </span>
         </p>
    </form>
    <div class="clear"></div>

