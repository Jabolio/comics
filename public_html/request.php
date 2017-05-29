<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

$error = null;

if ($_POST['submit'])
{
  $url = $db->escape($_POST['url']);

  if (!$url)
    $error = sprintf(Page::ERROR_TEMPLATE, 'Please specify a strip website address');

  mail('jpdeveaux', 'Comic request', $url, 'From: '.$email);
  $onload = 'alert("Your request has been sent.  Thanks!");';
}

$body = <<<BODY
<div class="loginbox">
  <h3>Comic request</h3>
  <p class="info">Do you want a comic added to the list?  All you have to do is ask!</p>
  <br/>
  <form method="post">
    <table>
      <tr %s>
        <td><label>Comic name/address:</label></td>
        <td><input id="id_data" name="url" size="55"/></td>
        %s
      </tr>
    </table>
    <p><input type="submit" name='submit' value="Request a comic!" />, <a href="/">or go back</a></p>
  </form>
</div>
BODY;

$page = new Page('Strip Request - Starcatcher Comics', true);
$page->SetContent(sprintf($body, ($error ? Page::ERROR_CLASS : ''), $error));
if (isset($onload))
  $page->SetBodyOnLoad($onload);
$page->DisplayPage();

?>
