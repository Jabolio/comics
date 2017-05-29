#!/usr/bin/php
<?php

// re-write of the comics script!  2013-04-02 - 2013-04-25

$options = getopt('t:r::u:m:dl::e',array('ts:','lu','delete:','clear:','bump','resume'));

if (count($options) == 0 || (count($options) == 1 && isset($options['d'])))
  die("Nothing to do!\n");

if (isset($options['u']) && isset($options['m']))
  die ('Cannot send to a single user and to a frequency mode group');

if (isset($options['l']) || isset($options['lu']) || isset($options['t']))
{
  if (!(((isset($options['d']) && isset($options['e'])) && count($options) == 3) ||
        ((isset($options['d']) || isset($options['e'])) && count($options) == 2) ||
          count($options) == 1))
    die('Test and list must be done on their own.');
}

// include.  The Comics.class.php include also includes DB.class.php
include('/home/comics/classes/Comics.class.php');

// create the Comics and DB objects.  if d option is set, then set a debug function (outputs to console).
$db = new DB();
$c = new Comics($db);
if (isset($options['d']))
  $c->SetDebugFn('debug');

// some admin functionality, listing users and comics, and testing.

// display a list of comics.  If we provide a value for this param, show comics for that user only.
if (isset($options['l']))
{
  $comics = ($options['l']
              ? $db->GetArray("select comics.* from userComics left join comics on fk_comic_id = comic_id where fk_user_id = {$options['l']} order by comic_id")
              : $db->GetArray('select * from comics'));

  $template = "% 3d. %s\n";
  foreach($comics as $comic)
    printf($template, $comic['comic_id'], $comic['name']);
}

// display a list of users (and their last send times)
elseif (isset($options['lu']))
{
  $users = $db->GetArray('select * from users');
  $template = "% 2d. %40s ( %6s, last sent %s )\n";
  foreach($users as $u)
    printf($template, $u['user_id'], $u['email'], ($u['frequency_mode'] == 1 ? 'daily' : 'weekly'), $u['last_delivered']);
}

// test a comic.  downloaded data will not be cleaned up.
elseif (isset($options['t']))
{
  $c->TestMode(true);
  $comic = $db->GetRow("select * from comics where comic_id = {$options['t']}");
  echo "Testing {$comic['name']}...\n";
  $c->Download($comic);

 // email, if e option is set
  $filesRetrieved = $c->GetTestingFiles();
  if (count($filesRetrieved) > 0)
  {
    echo "Files retrieved:\n";
    print_r($filesRetrieved);

    if (isset($options['e']))
    {
      echo "Emailing...\n";
      $files = implode(' ', $filesRetrieved);
      exec("mutt -s 'Comics - test files retrieved' -a {$files} -- ".Comics::ME.' < /dev/null');
    }
  }
  else
    echo "No files retrieved.\n";
}

// delete/clear a comic.  gets rid of all files/records on the server pertaining to this strip.
elseif (isset($options['delete']) || isset($options['clear']))
{
  $target = isset($options['delete']) ? $options['delete'] : $options['clear'];
  if (!is_numeric($target))
    die('Deletion/clear target must be a number.');

  $comic = $db->GetRow("select * from comics where comic_id = {$target}");
  foreach($db->GetArray("select * from comicFiles where fk_comic_id = {$target}") as $f)
  {
    unlink(Comics::COMIC_DIR.substr($f['comic_hash'],0,2).'/'.substr($f['comic_hash'],2,2).'/'.$f['comic_hash']);
    $db->Execute("delete from videos where fk_file_id = {$f['file_id']}");
  }

  $db->Execute("delete from comicFiles where fk_comic_id = {$target}");

 // if we're deleting, then delete the comic records that define the comic and link to users.
  if (isset($options['delete']))
  {
    $db->Execute("delete from userComics where fk_comic_id = {$target}");
    $db->Execute("delete from comics where comic_id = {$target}");
  }
}

// bump all comics (set the "last_viewed" field to now for all users/comics
elseif (isset($options['bump']))
  $db->Execute('update userComics set last_viewed = now()');

// ========================================================================================== //

else
{
 // refresh the comics; resume if the option is given
  if (isset($options['r']))
  {
    foreach($db->GetArray('select * from comics '.(is_numeric($options['r']) ? ' where comic_id '. (isset($options['resume']) ? '>= ' : '= ').$options['r'] : '')) as $comic)
      $c->Download($comic);

   // send a report of any comics where nothing came up
    if (!isset($options['d']))
      $c->SendIssueReport();
  }

 // send out the most recent comics, either to a specific user or to a frequency group.
  $users = array();
  $suffix = null;
  if (isset($options['u']))
    $suffix = "user_id = {$options['u']}";
  elseif (isset($options['m']))
    $suffix = "frequency_mode = {$options['m']}";

  if ($suffix)
  {
    $users = $db->GetArray("select * from users where {$suffix}");

   // if there are users to send to, then send them comics.
   // a "ts" option will override the user's last_delivered value.
   // the time is passed in as a string because that's how the query needs it.
    if (count($users) > 0)
    {
      foreach ($users as $user)
        $c->DeliverComics($user, (isset($options['ts']) ? "'{$db->escape($options['ts'])}'" : 'null'));
    }
  }

  $c->Cleanup();
}

// debug function for command line functionality is just an echo.
function debug($msg)
{
  echo $msg."\n";
}

?>
