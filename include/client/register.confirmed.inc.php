<?php if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
        array($content->getName(), $content->getBody())); ?>
<h1><?php echo Format::display($title); ?></h1>
<p><?php
echo Format::display($body); ?>
</p>
<?php } else { ?>
<h1>S'enregistrer</h1>
<p>
<strong>Merci de vous être enregistré(e).</strong>
</p>
<p>
Vous avez confirmé votre adresse électronique et activé votre compte.
Vous pouvez maintenant vérifier vos tickets ouverts auparavant ou bien ouvrir un nouveau ticket.
</p>
<p><em>Votre équipe d'assistance</em></p>
<?php } ?>
