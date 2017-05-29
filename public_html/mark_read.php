<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

$comic_id = $db->escape($_GET['id']);
$time = date('Y-m-d H:i:s', $db->escape($_GET['t']));
$where = ($comic_id == 'all') ? '' : "and fk_comic_id = {$comic_id}";

$db->Execute("update userComics set last_viewed = greatest(last_viewed, '{$time}') where fk_user_id = {$user_id} {$where};");

?>
