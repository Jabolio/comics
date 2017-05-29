<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

if ($_COOKIE['session_key'])
{
  setcookie('session_key', null, 0, '/', 'comics.starcatcher.ca');
  $db->Execute("update users set session_key = null where user_id = {$user_id}");
  header('Location: /login.php');
}

?>
