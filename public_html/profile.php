<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

$body = <<<BODY
<div class="box">
    <h3>Account settings</h3>
    <p class="text_left">
      <a href="/password.php">Change password</a><br/>
      <a href="/settings.php">Change e-mail & other settings</a><br/><br/>
    </p>
</div>
BODY;

$page = new Page('Profile - Starcatcher Comics', true);
$page->SetContent($body);
$page->DisplayPage();

?>
