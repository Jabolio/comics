<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

if (!($user_id == 1))
  exit;

$count = $db->GetValue("select count(*) as num from comicFiles where fk_comic_id = {$_GET['id']}", 'num');
exec("sudo -u comics /home/comics/comics.php -r={$_GET['id']}");
$updatedCount = $db->GetValue("select count(*) as num from comicFiles where fk_comic_id = {$_GET['id']}", 'num');

echo $updatedCount - $count;

?>
