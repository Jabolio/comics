<?php

// assumes DB has already been initted

if ($_COOKIE['session_key'])
{
  $db = new DB();
  $loginData = $db->GetRow("select * from users where session_key = '{$db->escape($_COOKIE['session_key'])}'");
  if ($loginData['user_id'])
  {
    $user_id = $loginData['user_id'];
    $email = $loginData['email'];
    $password = $loginData['password'];
    $freq = $loginData['frequency_mode'];
    $format = $loginData['delivery_mode'];
  }
  else
  {
    setcookie('session_key', null, 0, '/', 'comics.starcatcher.ca');
    if (!isset($noRedirect))
      header('Location: /login.php?err=1');
  }
}
elseif (!isset($noRedirect))
  header('Location: /login.php?err=2');

?>
