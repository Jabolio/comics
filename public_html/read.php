<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

$template='<div id=comic_%d class=comic></div>';
$rows = $markAllRead = $hideNoUnread = '';
$numUnread = 0;
$onlyUnread = isset($_GET['u']);

$page = new Page('Showing '.($onlyUnread ? 'only unread' : 'all subscribed').' comics - Starcatcher Comics', true);

// get a list of comics for this user.  count unread comics
$comics = $db->GetArray("call USERCOMICLISTALL({$user_id})");
if (is_array($comics) && count($comics) > 0)
{
  foreach($comics as $c)
  {
    $unread = strtotime($c['newest']) > strtotime($c['last_viewed']);
    if ($unread || !$onlyUnread)
    {
      $rows .= sprintf($template, $c['comic_id']);
      if ($unread)
        $numUnread++;
    }
  }
}

// make sure something is there, and set up JS calls to load the comics
if ($rows)
{
  $curTime = time();
  $markAllRead = "<a class='comic_info_mark_all_read' onclick='MarkRead(\"all\",{$curTime});' style='font-size:18px'>Mark all comics as read</a>";
  $js = '$("#read .comic").each(function() { load(this.id.substring(6), 0, 0, this.id); });';
  $hideNoUnread = 'style="display:none"';
}

// unread tracking
$js .= "numUnread = {$numUnread};";
if ($onlyUnread)
  $js .= 'onlyUnread = 1;';

// go.
$page->SetContent("<h2 id='no_unread' {$hideNoUnread}>No unread comics!</h2>{$markAllRead}<div id=read>{$rows}</div>{$markAllRead}<br><br>");
$page->SetBodyOnLoad($js);
$page->DisplayPage();

?>
