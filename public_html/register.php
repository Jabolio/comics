<?php

include('page.php');

// for the captcha
$date = date("Ymd");
$rand = rand(0,9999999999999);
$height = "80";
$width  = "240";
$img    = "$date$rand-$height-$width.jpgx";

$emailError = $email = $pwError = $dailyChk = $weeklyChk = $captchaErr = $attChk = null;
$noChk = $htmlChk = 'checked';

$body = <<<BODY
<div id="register_wrap" class="loginbox">
        <h3>Register new account</h3>
        <p class="info">Please fill in the following fields to create your account.</p>
        <br/>
        <form method="post" enctype="application/x-www-form-urlencoded">
        <table id="register_table">
                <colgroup>
                        <col width=110>
                        <col width=580>
                </colgroup>
                <tbody>
                <tr %s >
                <td id="username_label"><label>E-mail</label></td>
                <td><input id="id_email" type="text" name="email" size="50" value="%s"/><br/><span class="small_text">Comics will be sent here.</span></td>
                %s
                </tr>
                <tr %s >
                <td id="password_label"><label>Password</label></td>
                <td><input id="id_password1" type="password" name="password1" size="25" /></td>
                </tr>
                <tr %4\$s >
                <td id="password2_label"><label>Password again</label></td>
                <td><input id="id_password2" type="password" name="password2" size="25" /><br/><span class="small_text">Type your password again. Just in case.</span></td>
                %s
                </tr>
                <tr>
                <td id="format_label"><label>Email format</label></td>
                <td><input id="id_format1" type=radio name=format value=1 %s>HTML with linked images &nbsp; <input id="id_format2" type=radio name=format value=2 %s>Files attached to email</td>
                </tr>
                <tr>
                <td id="freq_label"><label>Delivery Frequency</label></td>
                <td><input id="id_freq1" type=radio name=freq value=0 %s>Not emailed &nbsp; <input id="id_freq2" type=radio name=freq value=1 %s>Daily &nbsp; <input id="id_freq3" type=radio name=freq value=2 %s>Weekly</td>
                </tr>
		<tr><td colspan=2>
   		<ul class='settings_notes'>
			<li>Daily comics are sent out between 8am and 8:30am (Atlantic time) every day.</li>
			<li>Weekly comics (all comics for the week) are sent out on Sunday morning at 9am (Atlantic time).</li>
		</ul>
		</td></tr>
                <tr><td colspan=2><hr></td></tr>
                <tr %s >
                <td id="captcha_label"><label>No Robots!</label></td>
                <td>
                        <input type='hidden' name='img' value='$img'>
                        <a href='http://www.opencaptcha.com'><img src='http://www.opencaptcha.com/img/$img' height='$height' alt='captcha' width='$width' border='0' /></a><br>
                        <input type=text name=code value='Enter The Code' size='35' />
                </td>
		%s
                </tr>
                <tr><td colspan="2" style="text-align:center;"><input type="submit" name='submit' value="Create account"/>, <a href="/">or go back</a></td></tr>
                </tbody>
        </table>
        </form>
</div>
BODY;

if ($_POST['submit'])
{
  include('/home/comics/classes/DB.class.php');

 // getting input.
  $email = $db->escape($_POST['email']);
  $password1 = $db->escape($_POST['password1']);
  $password2 = $db->escape($_POST['password2']);
  $freq = $db->escape($_POST['freq']);
  $format = $db->escape($_POST['format']);
  $code = $db->escape($_POST['code']);
  $img = $db->escape($_POST['img']);

  if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
    $emailError = sprintf(Page::ERROR_TEMPLATE, 'A valid email is required');

  if (!$password1)
    $pwError = sprintf(Page::ERROR_TEMPLATE, 'A password is required');
  elseif(!($password1 == $password2))
    $pwError = sprintf(Page::ERROR_TEMPLATE, 'Your passwords do not match');

 // frequency
  $noChk = '';
  if (!($freq == 0 || $freq == 1 || $freq == 2))
    die('wtf');
  else
  {
    switch($freq)
    {
      case 0:      $noChk = 'checked'; break;
      case 1:   $dailyChk = 'checked'; break;
      case 2:  $weeklyChk = 'checked'; break;
    }
  }

 // attachment format
  $htmlChk = '';
  if (!($format == 1 || $format == 2))
    die('wtf');
  else
  {
    switch($format)
    {
      case 1:  $htmlChk = 'checked'; break;
      case 2:   $attChk = 'checked'; break;
    }
  }

 // check captcha
  if(!(file_get_contents("http://www.opencaptcha.com/validate.php?ans={$code}&img={$img}")=='pass'))
    $captchaErr = sprintf(Page::ERROR_TEMPLATE, 'Your captcha code didn\'t match');

 // if there were no errors, then create the user, and forward to the choose page.
  if (!($emailError || $pwError || $captchaErr))
  {
   // create session key
    $session_key = md5(microtime(null));
    setcookie('session_key', $session_key, time() + 10000000, '/', 'comics.starcatcher.ca');

   // add user to DB
    $id = $db->GetValue('select max(user_id)+1 as id from users', 'id');
    $db->Execute("insert into users values ({$id}, '{$email}', PASSWORD('{$password1}'), {$format}, {$freq}, '{$session_key}');");

   // forward to choose page.
    header('Location: /choose.php');
    exit;
  }
}

$page = new Page('Register - Starcatcher Comics');
$page->SetContent(sprintf($body, 
			  ($emailError ? Page::ERROR_CLASS : ''),
			  $email,
			  $emailError,
			  ($pwError ? Page::ERROR_CLASS : ''),
			  $pwError,
			  $htmlChk,
			  $attChk,
			  $noChk,
			  $dailyChk,
			  $weeklyChk,
			  ($captchaErr ? Page::ERROR_CLASS : ''),
			  $captchaErr));

$page->DisplayPage();

?>
