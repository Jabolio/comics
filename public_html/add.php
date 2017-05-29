<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

if (!($user_id == 1))
  exit;

//=========================================//

function debugFn($msg)
{
  global $debugMsgs;
  $debugMsgs[] = $msg;
}

//=========================================//

$comicName = $comicNameError = $url = $urlError = $mode = $modeError = $params = $paramsError = $numFiles = $numFilesError = $minSize = $minSizeError = $jsMsg = null;

// set up initial values from the DB.

if ($_POST['submit'] == 'Scrape it')
{
  $comicName = htmlspecialchars($_POST['comicName']);
  $url = htmlspecialchars($_POST['url']);
  $mode = $_POST['mode'];
  $params = htmlspecialchars($_POST['params']);
  $numFiles = $_POST['numFiles'] ? htmlspecialchars($_POST['numFiles']) : 0;
  $minSize = htmlspecialchars($_POST['minSize']);

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
    include('/home/comics/classes/Comics.class.php');
    $c = new Comics($db);
    $c->TestMode(true);
    $debugMsgs = array();
    $c->SetDebugFn('debugFn');

   // prepare array to pass to Download method.  Need to decode the stuff I encoded earlier...
    $comic = array('comic_id' => 'new', 'url' => htmlspecialchars_decode($url), 'name' => htmlspecialchars_decode($comicName), 
                   'fetch_mode' => $mode, 'file_path' => htmlspecialchars_decode($params), 'num_files' => htmlspecialchars_decode($numFiles), 
                   'min_size' => htmlspecialchars_decode($minSize), 'active' => 1);

    $c->Download($comic);

   // if there were files returned, then show them on the screen.
    $files = $c->GetTestingFiles();
    if (count($files) > 0)
    {
      foreach($files as $f)
        $fileLinks .= '<img src="/tmp/'.basename($f).'" style="max-width:700px"><br>';

      $fileHtml = <<<HTML
<div class='box testBox'>
  <h3>Files Retrieved:</h3>
  $fileLinks
  <form method=POST>
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
      $debug = implode('<br>', $debugMsgs);

      $fileHtml = <<<HTML
<div class='box testBox'>
  <h3>Debug text:</h3>
  <div style='text-align:left'>{$debug}</div>
</div>
HTML;
    }
  }
}

// add this comic to the database, and load a few strips for it.
elseif ($_POST['submit'] == 'Confirm')
{
  $comicName = $db->escape($_POST['comicName']);
  $url = $db->escape($_POST['url']);
  $mode = $_POST['mode'];
  $params = $db->escape($_POST['params']);
  $numFiles = $_POST['numFiles'] ? $db->escape($db->escape($_POST['numFiles'])) : 0;
  $minSize = $db->escape($_POST['minSize']);
  $added = date('Y-m-d');

  $db->Execute("insert into comics (name, fetch_mode, url, file_path, num_files, min_size, added) values ('{$comicName}', '{$mode}', '{$url}', '{$params}', '{$numFiles}', '{$minSize}', '{$added}');");
  $comic_id = $db->InsertedId();
  exec("sudo -u comics /home/comics/comics.php -r={$comic_id}");

  $jsMsg = 'alert("'.htmlspecialchars($_POST['comicName']).' has been inserted succesfully!");';
  $comicName = $url = $mode = $params = $numFiles = $minSize = null;

  include('/home/comics/classes/Comics.class.php');
  $c = new Comics($db);
  $c->Cleanup();
}

// ==================================================================================================== //

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
    <h3>Add a Comic</h3>
    <form method="post" class="text_left">
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
      <div class="text_center"><input type="submit" name='submit' value="Scrape it" /></div>
    </form>
    <br />
    <a href="/admin.php">Go back.</a>
</div>
{$fileHtml}
BODY;

$page = new Page('Add a Comic - Starcatcher Comics', true);
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
                           $minSizeError));

$page->SetBodyOnLoad($jsMsg);
$page->DisplayPage();

?>
