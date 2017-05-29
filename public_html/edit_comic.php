<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

if (!($user_id == 1))
  exit;

include('/home/comics/classes/Comics.class.php');
$c = new Comics($db);

//=========================================//

function debugFn($msg)
{
  global $debugMsgs;
  $debugMsgs[] = $msg;
}

//=========================================//

$comicName = $comicNameError = $url = $urlError = $mode = $modeError = $params = $paramsError = $numFiles = $numFilesError = $minSize = $minSizeError = $jsMsg = null;

// handle postback
if (isset($_POST['submit']))
{
  switch($_POST['submit'])
  {
    case 'Update':
      $comic_id = $_POST['id'];
      $comicName = htmlspecialchars($_POST['comicName']);
      $url = htmlspecialchars($_POST['url']);
      $mode = $_POST['mode'];

      $params = htmlspecialchars($_POST['params']);
      $numFiles = $_POST['numFiles'] ? htmlspecialchars($db->escape($_POST['numFiles'])) : 0;
      $minSize = htmlspecialchars($_POST['minSize']);
      $active = $_POST['active'];

      if (!$comicName)
        $comicNameError = sprintf(Page::ERROR_TEMPLATE, 'Comic name is required');

      if (!$url)
        $urlError = sprintf(Page::ERROR_TEMPLATE, 'URL required');
      elseif(filter_var($url, FILTER_VALIDATE_URL) === FALSE)
        $urlError = sprintf(Page::ERROR_TEMPLATE, 'URL does not seem valid');

      if (!is_numeric($numFiles))
        $numFilesError = sprintf(Page::ERROR_TEMPLATE, 'Number of files must be numeric');

     // no errors, proceed with test
      if (!($comicNameError || $urlError || $numFilesError))
      {
        $c->TestMode(true);
        $debugMsgs = array();
        $c->SetDebugFn('debugFn');

       // prepare array to pass to Download method.  Need to decode the stuff I encoded earlier...
       // active is always set to 1 here so the download will at least try to run.
        $comic = array('comic_id' => $comic_id, 'url' => htmlspecialchars_decode($url), 'name' => htmlspecialchars_decode($comicName),
                       'fetch_mode' => $mode, 'file_path' => htmlspecialchars_decode($params), 'num_files' => htmlspecialchars_decode($numFiles),
                       'min_size' => htmlspecialchars_decode($minSize), 'active' => 1);

         $c->Download($comic);

       // if there were files returned, then show them on the screen.
        $debug = implode("\n", $debugMsgs);

        $files = $c->GetTestingFiles();
        if (count($files) > 0)
        {
          foreach($files as $f)
            $fileLinks .= '<img src="/tmp/'.basename($f).'" style="max-width:700px"><br>';

          $fileHtml = <<<HTML
<div class='box testBox'>
  <h3>Debug text:</h3>
  <div style='text-align:left'><pre>{$debug}</pre></div>
</div>
<div class='box testBox'>
  <h3>Files Retrieved:</h3>
  $fileLinks
  <form method=POST>
    <input type=hidden name=id value="{$comic_id}">
    <input type=hidden name=comicName value="{$comicName}">
    <input type=hidden name=url value="{$url}">
    <input type=hidden name=mode value="{$mode}">
    <input type=hidden name=params value="{$params}">
    <input type=hidden name=numFiles value="{$numFiles}">
    <input type=hidden name=minSize value="{$minSize}">
    <input type="submit" name='submit' value="Confirm" />
  </form>
</div>
HTML;
        }
        else
        {
          $jsMsg = 'alert("No files found!");';

          $fileHtml = <<<HTML
<div class='box testBox'>
  <h3>Debug text:</h3>
  <div style='text-align:left'><pre>{$debug}</pre></div>
</div>
HTML;
        }
      }

      break;

   // update the details for this comic in the database
    case 'Confirm':
      $comicName = $db->escape($_POST['comicName']);
      $url = $db->escape($_POST['url']);
      $mode = $_POST['mode'];
      $params = $db->escape($_POST['params']);
      $numFiles = $_POST['numFiles'] ? $db->escape($db->escape($_POST['numFiles'])) : 0;
      $minSize = $db->escape($_POST['minSize']);
      $added = date('Y-m-d');

      $db->Execute("update comics set name='{$comicName}', fetch_mode='{$mode}', url='{$url}', file_path='{$params}', num_files='{$numFiles}', min_size='{$minSize}' where comic_id = {$_POST['id']};");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been updated!");';
      $c->Cleanup();
      break;

   // fetch strips for this comic
    case 'Refresh':
      exec("sudo -u comics /home/comics/comics.php -r={$_POST['id']}");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been refreshed!");';
      $c->Cleanup();
      break;

   // suspend fetching of this strip
    case 'Suspend':
      $db->Execute("update comics set active = 0 where comic_id = {$_POST['id']}");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been suspended!");';
      $c->Cleanup();
      break;

   // resume fetching of this strip
    case 'Reactivate':
      $db->Execute("update comics set active = 1 where comic_id = {$_POST['id']}");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been reactivated!");';
      $c->Cleanup();
      break;

   // delete all record of this strip from the database
    case 'Delete':
      exec("sudo -u comics /home/comics/comics.php --delete {$_POST['id']}");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been permanently deleted!"); window.location.href="/edit.php"; ';
      $c->Cleanup();
      break;

   // clear all images for this strip from the database
    case 'Clear':
      exec("sudo -u comics /home/comics/comics.php --clear {$_POST['id']}");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been cleared out!");';
      $c->Cleanup();
      break;

   // bump this strip (set "last viewed" field for all users to now)
    case 'Bump':
      $db->Execute("update userComics set last_viewed = now() where fk_comic_id = {$db->escape($_POST['id'])};");
      $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been bumped!");';
      $c->Cleanup();
  }
}

// ==================================================================================================== //

// populate variables if necessary
if (!isset($comic_id))
{
  $comic_id = $_REQUEST['id'];
  $data = $db->GetRow("select * from comics where comic_id = {$comic_id}");

  $comicName = htmlspecialchars($data['name']);
  $url = htmlspecialchars($data['url']);
  $mode = $data['fetch_mode'];
  $params = htmlspecialchars($data['file_path']);
  $numFiles = $data['num_files'] ? htmlspecialchars($data['num_files']) : 0;
  $minSize = htmlspecialchars($data['min_size']);
  $active = $data['active'];
}

$fetchModes = array
  (
    1 => '1 - Download page completely; get files based on file path',
    2 => '2 - Download HTML only; get files based on regexp location in HTML',
    3 => '3 - Cheezburger sites (multiple pages, DOM-based, handles video)',
    4 => '4 - Baby Blues (custom hack)',
    5 => '5 - Oglaf (custom hack)',
    6 => '6 - Download HTML only; double regexp query (get tag, then get images within)'
  );

$optionTemplate = '<option value=%d %s>%s</option>';
foreach($fetchModes as $k => $m)
  $options .= sprintf($optionTemplate, $k, ($mode == $k ? 'selected' : ''), $m);

$width = 'style="width:450px"';

$body = <<<BODY
<div class="box" style='width:700px'>
    <h3>Edit a Comic</h3>
    <form method="post" class="text_left"">
      <input type=hidden name=id value={$comic_id}>
      <input type=hidden name=active value={$active}>
      <table style="margin-left:auto;margin-right:auto">
        <tr %s>
          <td><label>Comic Name</label></td>
          <td><input id="id_comicName" type="text" name="comicName" {$width} value="{$comicName}"/></td>
        </tr>
        %s
        <tr %s>
          <td><label>URL</label></td>
          <td><input id="id_url" type="text" name="url" {$width} value="{$url}"/></td>
        </tr>
        %s
        <tr %s>
          <td><label>Fetch Mode</label></td>
          <td><select id="id_mode" name="mode" {$width}>{$options}</select></td>
        </tr>
        %s
        <tr %s>
          <td><label>Search Parameters</label></td>
          <td><input id="id_params" type="text" name="params" {$width} value="{$params}"/></td>
        </tr>
        %s
        <tr %s>
          <td><label>Number of files</label></td>
          <td><input id="id_numFiles" type="text" name="numFiles" {$width} value="{$numFiles}"/></td>
        </tr>
        %s
        <tr %s>
          <td><label>Minimum File Size</label></td>
          <td><input id="id_minSize" type="text" name="minSize" {$width} value="{$minSize}"/></td>
        </tr>
        %s
      </table>
      <div class="text_center">
        <input type="submit" name='submit' value="Update" /> 
        <input type="submit" name='submit' value="Refresh" /> 
        <input type="submit" name="submit" value="%s"> 
        <input type="submit" name="submit" value="Delete" onclick="return confirm('Are you sure you want to permanently delete this comic?');">
        <input type="submit" name="submit" value="Clear" onclick="return confirm('Are you sure you want to clear all images for this comic?');">
        <input type="submit" name="submit" value="Bump">
      </div>
    </form>
    <br />
    <a href="/edit.php">Go back.</a>
</div>
%s
BODY;

$page = new Page('Edit a Comic - Starcatcher Comics', true);
$page->SetContent(sprintf($body,
                          ($comicNameError ? Page::ERROR_CLASS : ''),
                           $comicNameError,
                          ($urlError ? Page::ERROR_CLASS : ''),
                           $urlError,
                          ($modeError ? Page::ERROR_CLASS : ''),
                           $modeError,
                          ($paramsError ? Page::ERROR_CLASS : ''),
                           $paramsError,
                          ($numFilesError ? Page::ERROR_CLASS : ''),
                           $numFilesError,
                          ($minSizeError ? Page::ERROR_CLASS : ''),
                           $minSizeError,
                          ($active ? 'Suspend' : 'Reactivate'),
                           $fileHtml));

$page->SetBodyOnLoad($jsMsg);
$page->DisplayPage();

?>
