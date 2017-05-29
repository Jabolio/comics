<?php

include('/home/comics/classes/DB.class.php');

// if we're resetting, stick the reset key in place of the session cookie
if (isset($_GET['reset']))
{
  $notLoggedIn = true;
  $_COOKIE['session_key'] = $db->escape($_GET['reset']);
}
else
  $notLoggedIn = false;

include('auth.php');
include('page.php');

$curBlock = <<<CUR
      <tr %s>
        <td><label>Current password</label></td>
        <td><input id="id_old_password" type="password" name="old_password" size="25" /></td>
      </tr>
      %s
CUR;

$resetLink = '<p>If you can\'t remember your current password, use the password reset page <a href=/reset.php>here</a></p>';
$curPwError = $pwError = null;

// process PW change.
if ($_POST['submit'])
{
  $old_password = $db->escape($_POST['old_password']);
  $new_password1 = $db->escape($_POST['new_password1']);
  $new_password2 = $db->escape($_POST['new_password2']);

  $curPW = $db->GetValue("select PASSWORD('{$old_password}') as pw;", 'pw');

  if (!$notLoggedIn && $curPW != $password)
    $curPwError = sprintf(Page::ERROR_TEMPLATE, 'Current password is invalid');

  if ($new_password1 != $new_password2)
    $pwError = sprintf(Page::ERROR_TEMPLATE, 'Passwords do not match');

  if (!$new_password1)
    $pwError = sprintf(Page::ERROR_TEMPLATE, 'Password cannot be empty');

  if (!($curPwError || $pwError))
  {
    $db->Execute("update users set password=PASSWORD('{$new_password1}') where user_id = $user_id");
    setcookie('session_key', null, 0, '/', 'comics.starcatcher.ca');
    header('Location: /login.php?err=4');
    exit;
  }
}

$body = <<<BODY
<div class="loginbox">
  <h3>Password change</h3>
  <p class="info">Here you can change the password for your account.</P>
  <form method="post">
    %s
    <table style="margin-left:auto;margin-right:auto" class="text_left">
      %s
      <tr %s>
        <td><label>New password</label></td>
        <td><input id="id_new_password1" type="password" name="new_password1" size="35" /></td>
      </tr>
      <tr %3\$s>
        <td><label>New password again</label></td>
        <td><input id="id_new_password2" type="password" name="new_password2" size="35" /></td>
      </tr>
      %s
    </table>
    <input name=submit type="submit" value="Change it" />
  </form>
  <br />
  %s
  <a href="/profile.php">Go back</a>
</div>
BODY;

$page = new Page('Password Reset - Starcatcher Comics', !$notLoggedIn);

$curBlock = sprintf($curBlock, ($curPwError ? Page::ERROR_CLASS : ''), $curPwError);

$page->SetContent(sprintf($body, 
                          ($notLoggedIn ? "<input type=hidden name=key value={$_COOKIE['session_key']}>":''), 
                          (!$notLoggedIn ? $curBlock : ''), 
			  ($pwError ? Page::ERROR_CLASS : ''), 
			   $pwError,
                          (!$notLoggedIn ? $resetLink : '')));

$page->DisplayPage();

?>
