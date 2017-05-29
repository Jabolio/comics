<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');
include('page.php');

$comics = $db->GetArray('call ALLCOMICSWITHUSER('.$user_id.')');
$template = '<li id="comic_li_%d" class="comic %s %s" ><div id="unread_li_%1$d" class=unread %s></div><div id="recent_li_%1$d" class=recent %s></div><div id="suspend_li_%1$d" class=suspended %s></div>%s</li>';
$jsTemplate = 'c = {"name":"%s", "id":%d} ; comics[%2$d] = c ;';
$hide = 'style="display:none"';

$li = '';
$day = 24*3600;
$month = 30*$day;
$now = time();
$hasUnread = false;
$numComics = count($comics);
$numUnread = 0;
$numSubscribed = 0;
$numUpdated = 0;
$numNew = 0;

foreach($comics as $c)
{
 // is this comic active?
  if($active = ($c['active'] == 1))
  {
    $newest = strtotime($c['newest']);
    if($updated = $now - $newest < $day)
      $numUpdated++;

   // is this new?  (keep count)
    if($new = $c['added'] && ($now - strtotime($c['added']) < $month))
      $numNew++;

   // does this have unread? (keep count)
    if($unread = $c['last_viewed'] && (strtotime($c['last_viewed']) < $newest))
      $numUnread++;
  }
  else
    $new = $updated = $unread = null;

 // am I subscribed?
  if ($c['fk_user_id'])
    $numSubscribed++;

  $li .= sprintf($template, $c['comic_id'], ($c['fk_user_id'] ? 'added' : 'reg'), ($new ? 'new' : ''), ($unread ? '' : $hide), ($updated ? '' : $hide), ($active ? $hide : ''), $c['name']);
}

// prep the list and display divs based on the current browser size, and add hover/onclick events to the comic list.
$onload = <<<ONLOAD
\$(document).ready(function(){
  \$("#comic_list").height(\$(window).height()-323);
  \$("#comic_info").height(\$(window).height()-85);

  \$("#comic_list li").hoverIntent(function() { loadComic(this); }, function() {});
  \$("#comic_list li").on("click", function() { toggleSubscribe(this); });

  numUnread = {$numUnread};
});
ONLOAD;

$showUnread = ($numUnread == 0 ? 'display:none' : '');
$curTime = time();

$content = <<<CONTENT
<div id="add">
  <h3>Add/Remove Comics</h3>
  <p>Starcatcher Comics currently aggregates <b>{$numComics}</b> comics/sites.<br>
     You are subscribed to <b id=num_subscribed>{$numSubscribed}</b> of them.<br>
     <b>{$numNew}</b> comics/sites have been added to the list in the last 30 days.<br>
     <b id=num_updated>{$numUpdated}</b> comics/sites have published new content in the last 24 hrs.<br>
     <b id=num_unread>{$numUnread}</b> of your comics/sites have unread content..<br></p>
  <p><b>Click</b> on a comic to add it to/remove it from your list.<br>
     <b>Hover</b> over a comic to load it in the viewer pane.</p>
  <div id=comic_legend>
     <img src=/images/ok12.png> - comic you are subscibed to.<br>
     <img src=/images/flag12.png> - new comic added to the site in the last 30 days.<br>
     <img src=/images/bluestar.png> - updated within the last 24 hours.<br>
     <img src=/images/star.png> - contains unread comics (subscribed comics only).<br>
     <img src=/images/suspend_small.png> - this strip is not currently being refreshed
  </div>
  <div id="comic_list_wrap">
    <div>Show: <a onclick="filter_allcomics()">all</a>, <a onclick="filter_newcomics()">new</a>, 
               <a onclick="filter_addedcomics()">added</a>, <a onclick="filter_unaddedcomics()">unadded</a>
      <div class='comic_info_mark_all_read' style='float:right;{$showUnread}'><a onclick='MarkRead("all",{$curTime});'>Mark all as read</a></div>
    </div>
    <ul id="comic_list">
      {$li}
    </ul>
  </div>
  <div id="comic_info_outer">
    <div id="comic_info"></div>
  </div>
</div>
CONTENT;

$page = new Page('Modify Your Comic Subscription - Starcatcher Comics', true);
$page->setContent($content);
$page->setBodyOnLoad($onload);
$page->DisplayPage();

?>
