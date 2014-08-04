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
Nous venons de vous envoyer un email à l'adresse que vous avez saisie. Veuillez cliquer sur le lien qu'il contient pour confirmer votre enregistrement et avoir accès à vos tickets.
</p>
<?php } ?>
