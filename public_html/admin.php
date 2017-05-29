<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

if (!($user_id == 1))
  exit;

$body = <<<BODY
<div class="box">
    <h3>Comics Administration</h3>
    <p class="text_left">
      <a href="/add.php">Add a comic</a><br/>
      <a href="/edit.php">Edit/Refresh/Delete a comic</a><br/>
    </p>
</div>
BODY;

$page = new Page('Admin - Starcatcher Comics', true);
$page->SetContent($body);
$page->DisplayPage();

?>
