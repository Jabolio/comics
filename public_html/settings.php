<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

$emailError = $dailyChk = $weeklyChk = $attChk = $noChk = $htmlChk = $jsMsg = null;

// set up initial values from the DB.

if ($_POST['submit'])
{
  $email = $db->escape($_POST['email']);
  $format_in = $db->escape($_POST['format']);
  $freq_in = $db->escape($_POST['freq']);

  if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
    $emailError = sprintf(Page::ERROR_TEMPLATE, 'A valid email is required');

  if ($freq_in != 0 && $freq_in != 1 && $freq_in != 2)
    die('wtf');
  if ($format_in != 1 && $format_in != 2)
    die('wtf');

  if (!$emailError)
  {
    if ($freq == 0 && $freq_in != 0)
      $lastUpdate = ", last_delivered = '".date('Y-m-d G:i:s')."' ";
    else
      $lastUpdate = '';

    $db->Execute("update users set email = '{$email}', delivery_mode = {$format_in}, frequency_mode = {$freq_in} {$lastUpdate} where user_id = {$user_id}");
    $jsMsg = 'alert("Your settings have been updated.")';

   // update vars so the interface reflects the changes.
    $freq = $freq_in;
    $format = $format_in;
  }
}

switch($freq)
{
  case 0:  $noChk = 'checked'; break;
  case 1:  $dailyChk = 'checked'; break;
  case 2:  $weeklyChk = 'checked'; break;
}

switch($format)
{
  case 1:  $htmlChk = 'checked'; break;
  case 2:  $attChk = 'checked'; break;
}

$body = <<<BODY
<div class="box">
    <h3>Change account settings</h3>
    <p class="info">Update email format and delivery frequency, as well as your email address.</p>
    <ul class='settings_notes'>
      <li>Daily comics are sent out between 7:30am and 8:00am (Atlantic time) every day.</li>
      <li>Weekly comics (all comics for the week) are sent out on Sunday morning at 9am (Atlantic time).</li>
      <li>To suspend comic delivery, set the frequency to "not emailed".</li>
      <li>When you resume delivery (changing from "not emailed" to "daily" or "weekly"), you not be sent any of the comics you missed while suspended.</li>
    </ul>
    <form method="post" class="text_left">
      <table style="margin-left:auto;margin-right:auto">
        <tr %s>
          <td><label>Email address</label></td>
          <td><input id="id_email" type="text" name="email" size="35" value="{$email}"/></td>
        </tr>
        %s
        <tr>
          <td id="format_label"><label>Email format</label></td>
          <td><input id="id_format1" type=radio name=format value=1 %s>HTML with linked images &nbsp; <input id="id_format2" type=radio name=format value=2 %s>Files attached to email</td>
        </tr>
        <tr>
          <td id="freq_label"><label>Delivery Frequency</label></td>
          <td><input id="id_freq1" type=radio name=freq value=0 %s>Not emailed &nbsp; <input id="id_freq2" type=radio name=freq value=1 %s>Daily &nbsp; <input id="id_freq3" type=radio name=freq value=2 %s>Weekly</td>
        </tr>
      </table>
      <div class="text_center"><input type="submit" name='submit' value="Change it" /></div>
    </form>
    <br />
    <a href="/profile.php">Go back.</a>
</div>
BODY;

$page = new Page('Edit Settings - Starcatcher Comics', true);
$page->SetContent(sprintf($body,
                          ($emailError ? Page::ERROR_CLASS : ''),
                           $emailError,
                           $htmlChk,
                           $attChk,
                           $noChk,
                           $dailyChk,
                           $weeklyChk));

$page->SetBodyOnLoad($jsMsg);
$page->DisplayPage();

?>
