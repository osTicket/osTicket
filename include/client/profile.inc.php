<?php

?>
<h1>Manage Your Profile Information</h1>
<p>
Use the forms below to update the information we have on file for your
account
</p>
<form action="profile.php" method="post">
  <?php csrf_token(); ?>
<table width="800">
<?php
foreach ($user->getForms() as $f) {
    $f->render(false);
}
?>
</table>
<hr>
<p style="text-align: center;">
    <input type="submit" value="Update"/>
    <input type="reset" value="Reset"/>
    <input type="button" value="Cancel" onclick="javascript:
        window.location.href='index.php';"/>
</p>
</form>
