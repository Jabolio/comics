<?php

// forward to either the login page or the choose page, depending on whether or not the cookie exists.

if ($_COOKIE['session_key'])
  header('Location: /choose.php');
else
  header('Location: /login.php');

?>
