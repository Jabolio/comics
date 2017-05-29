<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

$state = $db->escape($_GET['s']);
$comic_id = $db->escape($_GET['id']);

if ($state == 1)
  $db->Execute("insert into userComics (fk_user_id, fk_comic_id, last_viewed) values ({$user_id}, {$comic_id}, now());");
else
  $db->Execute("delete from userComics where fk_user_id = {$user_id} and fk_comic_id = {$comic_id};");

?>
