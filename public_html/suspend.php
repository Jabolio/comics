<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

if (!($user_id == 1))
  exit;

// toggle the comic's state.
$db->Execute("update comics set active = {$db->escape($_GET['action'])} where comic_id = {$db->escape($_GET['id'])}");

?>
