<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

if (!($user_id == 1))
  exit;

$db->Execute("update comicFiles set deleted = 1 where file_id = {$db->escape($_GET['id'])}");

?>
