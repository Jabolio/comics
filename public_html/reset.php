<?php

$noRedirect = true;
include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

$emailError = null;

if ($_POST['submit'])
{
  if (!isset($db))
    $db = new DB();

  $email = $db->escape($_POST['email']);

  if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
    $emailError = sprintf(Page::ERROR_TEMPLATE, 'A valid email is required');

 // create a session key, store it in the DB, and send a message out with a link.
  $user_id = $db->GetValue("select user_id from users where email = '{$email}'", 'user_id');
  if ($user_id)
  {
    $session_key = md5(microtime(null));
    setcookie('session_key', null, 0, '/', 'comics.starcatcher.ca');
    $db->Execute("update users set session_key = '{$session_key}' where user_id = {$user_id}");
    $body = <<<BODY
Hi!

Click on this link to restore your password to the comics page:

http://comics.starcatcher.ca/password.php?reset=$session_key

Thanks!
BODY;

    mail($email, 'Comics Password Reset Key', $body, 'From: Starcatcher Comics <comics@starcatcher.ca>');
    header('Location: /login.php?err=3');
    exit;
  }
  else
    $emailError = sprintf(Page::ERROR_TEMPLATE, 'Email address not found in the system.');
}

$body = <<<BODY
<div class="loginbox">
  <h3>Password reset</h3>
  <p class="info">Please enter your e-mail address and we will send you a link from which you can choose a new password.</p>
  <br/>
  <form method="post">
    <table>
      <tr %s>
        <td><label>Your email</label></td>
        <td><input id="id_data" name="email" size="50" value="%s"/></td>
        %s
      </tr>
    </table>
    <p><input type="submit" name='submit' value="Reset my password" />, <a href="/">or go back</a></p>
  </form>
</div>
BODY;

$page = new Page('Password Reset - Starcatcher Comics', (isset($user_id)));
$page->SetContent(sprintf($body, ($emailError ? Page::ERROR_CLASS : ''), $email, $emailError));
$page->DisplayPage();

?>
