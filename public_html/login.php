<?php

$body = <<<BODY
<div id="login_wrap" class="loginbox">
	<h3>Login into your account</h3>
	<p>Write your email address and passwod to get you logged in.</p>
	%s
	<div class="text_left">
		<form method="post" enctype="application/x-www-form-urlencoded">
			<table>
				<tr>
					<td><label class="small_text">Email Address </label></td>
					<td><input type="text" name="email" id="id_email" /><td>
				</tr>
				<tr>
					<td><label class="small_text">Password </label></td>
					<td><input type="password" name="password" id="id_password" /></td>
				</tr>
				<tr>
					<td colspan="2" class="text_center"><input type="submit" value="Log in" /><input type="hidden" name="next" id="id_next" /></td>
				</tr>
			</table>
		</form>
	</div>
	<p>Don't you have an account? <a href="/register.php">Create one</a></p>
	<p>Can't remember your password? <a href="/reset.php">Send me a new password</a></p>
</div>
BODY;

if ($_POST['email'])
{
  include('/home/comics/classes/DB.class.php');
  $userData = $db->GetRow("select * from users where email='{$db->escape($_POST['email'])}' and password=PASSWORD('{$db->escape($_POST['password'])}')");

  if (!$userData['user_id'])
    $error = '<p><span id="errorContainer">Email address or password not valid!</span></p>';
  else
  {
    $session_key = $userData['session_key'] ? $userData['session_key'] : md5(microtime(null));
    setcookie('session_key', $session_key, time() + 10000000, '/', 'comics.starcatcher.ca');
    if (!$userData['session_key'])
      $db->Execute("update users set session_key = '{$session_key}' where user_id = {$userData['user_id']}");

    header('Location: /choose.php');
  }
}
elseif ($_GET['err'] == 1)
  $error = '<p><span id="errorContainer">Your session key is not valid anymore!</span></p>';
elseif ($_GET['err'] == 2)
  $error = '<p><span id="errorContainer">You must login before accessing this page!</span></p>';
elseif ($_GET['err'] == 3)
  $error = '<p><span id="errorContainer">An email with a password reset link has been sent to you.</span></p>';
elseif ($_GET['err'] == 4)
  $error = '<p><span id="errorContainer">Log in with your new password.</span></p>';

include('page.php');
$page = new Page('Login - Starcatcher Comics');
$page->SetContent(sprintf($body, $error));
$page->DisplayPage();

?>
