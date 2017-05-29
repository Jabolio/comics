<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

if (!($user_id == 1))
  exit;

if ($_POST['submit'])
{
  header('Location: /edit_comic.php?id='.$_POST['comic_id']);
  exit;
}

$comics = $db->GetArray('select * from comics order by name');
$optionTemplate = '<option value=%d>%s</option>';
$options = '';
foreach($comics as $c)
  $options .= sprintf($optionTemplate, $c['comic_id'], $c['name']);

$width = 'style="width:300px"';

$body = <<<BODY
<div class="box" style='width:700px'>
    <h3>Edit a Comic</h3>
    <p>Select a comic to edit</p>
    <form method="post" class="text_left">
      <table style="margin-left:auto;margin-right:auto">
        <tr>
          <td><label>Comic Name</label></td>
          <td><select name="comic_id" {$width}>{$options}</select></td>
        </tr>
      </table>
      <div class="text_center"><input type="submit" name='submit' value="Edit it" /></div>
    </form>
    <br />
    <a href="/admin.php">Go back.</a>
</div>
BODY;

$page = new Page('Edit a Comic - Starcatcher Comics', true);
$page->SetContent($body);
$page->DisplayPage();

?>
