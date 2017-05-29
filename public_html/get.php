<?php

include('/home/comics/classes/DB.class.php');
include('auth.php');

$comic_id = $db->escape($_GET['id']);
$responseDiv = $db->escape($_GET['r']);
$height = $db->escape($_GET['h']);

$comicNumbers = $db->GetArray("call COMICALLFILES({$comic_id}, {$user_id})");
foreach($comicNumbers as $c)
  $numbers[] = $c['file_id'];

$lastViewed = (isset($comicNumbers[0]) ? strtotime($comicNumbers[0]['last_viewed']) : null);
$numComics = count($numbers);
$target = $icons = $markReadBox = $numUnread = null;

// if there was more than one image found, build the navigation stuff
if ($numComics > 1)
{
  $lastComic = end($comicNumbers);
  $target = (isset($_GET['t']) ? $db->escape($_GET['t']) : $lastComic['file_id']);
  $pos = array_search($target, $numbers);

  $selectorLinks = null;
  $selectorTemplate = '<option value=%d %s>%s%s</option>';

 // build the jump-to selector list
  foreach($comicNumbers as $c)
  {
    $uploadedTime = strtotime($c['uploaded']);
    if (($uploadedTime > $lastViewed) && $lastViewed)
      $numUnread++;

    $selectorLinks .= sprintf($selectorTemplate,
                              $c['file_id'],
                              ($c['file_id'] == $target ? 'selected=\'selected\'' : ''),
                              ((($uploadedTime > $lastViewed) && $lastViewed) ? '*NEW* ' : ''),
                              date('F d, Y \a\t g:i:sa', $uploadedTime));
  }

  $nav = "<div class='nav_select'><select onchange='load({$comic_id}, this.value, {$height}, \"{$responseDiv}\");'>{$selectorLinks}</select></div>";

  if ($pos > 0)
  {
    $nav .= "<div title='First' class='nav first' onclick='load({$comic_id}, {$numbers[0]}, {$height}, \"{$responseDiv}\");'>&nbsp;</div>".
            "<div title='Previous' class='nav prev' onclick='load({$comic_id}, {$numbers[$pos-1]}, {$height}, \"{$responseDiv}\");'>&nbsp;</div>";
  }

  if ($pos < $numComics-1)
  {
    $nav .= "<div title='Next' class='nav next' onclick='load({$comic_id}, {$numbers[$pos+1]}, {$height}, \"{$responseDiv}\");'>&nbsp;</div>".
            "<div title='Most Recent' class='nav last' onclick='load({$comic_id}, {$numbers[$numComics-1]}, {$height},\"{$responseDiv}\");'>&nbsp;</div>";
  }

  if ($numUnread)
    $nav .= "<div class=numUnread id='numUnread_{$comic_id}'>{$numUnread} unread</div>";
}
elseif (isset($numbers[0]))
  $target = $numbers[0];

if ($target)
{
  $comicData = $db->GetRow("call COMICSINGLEIMAGE({$target})");

  $h = $comicData['comic_hash'];
  $comicImagePath = '/'.substr($h,0,2).'/'.substr($h,2,2).'/'.$h;
  $i = new Imagick('/home/comics/public_html'.$comicImagePath);

  $maxHeightOffset = 50;
  $imgStyle = ($height > 0 && $i->GetImageHeight() > ($height - $maxHeightOffset) ? 'height: '.($height - $maxHeightOffset).'px;' : '');

  $uploaded = strtotime($comicData['uploaded']);
  $date = 'Retrieved on '.date('l, F jS, Y \a\t g:i:sa', $uploaded);
  $new = ((($uploaded > $lastViewed) && $lastViewed)) ? "<div id='new_icon_{$comic_id}' class='new_icon'>&nbsp;</div>" : null;
  $recent = ($uploaded > time() - 24*3600) ? '<div class="recent_icon" title="Uploaded within the last 24 hours">&nbsp;</div>' : null;

  $alt_text = htmlspecialchars($comicData['alt_text']);

 // if the comic is a video, then embed the video, otherwise display the image.
 // (video classes contain template strings for embedding)
  if ($comicData['video_key'])
  {
    include_once('/home/comics/classes/Video.class.php');
    $url = sprintf(Video::GetTemplate($comicData['video_mode'], true), $comicData['video_key']);
    $comicLink = $url ? "<iframe width=500 height=350 frameborder=0 webkitAllowFullScreen mozallowfullscreen allowfullscreen='' src='{$url}'>" : '<img src=/images/embed.jpg width=500 title="embed failed - '.Video::$classes[$comicData['video_mode']].'">';
  }
  else
    $comicLink = "<img id='comic_last' style='cursor:pointer;{$imgStyle}' title=\"{$alt_text}\" src='{$comicImagePath}' onclick=\"window.location.href='{$comicImagePath}'\">";

  if ($user_id == 1)
  {
   // are we suspending or unsuspending?
    if ($comicData['active'])
    {
      $suspendAction = 0;
      $suspendText = 'Suspend';
      $r = 'refresh';
      $s = 'suspend';
    }
    else
    {
      $suspendAction = 1;
      $suspendText = 'Unsuspend';
      $r = 'refresh_suspended';
      $s = 'unsuspend';
    }

    $icons = <<<ICONS
<div class=delete onclick="DeleteComic({$comic_id},{$target},{$height},'{$responseDiv}');" title='Delete this comic'></div>
<div id='refresh_{$comic_id}' class={$r} onclick="RefreshComic({$comic_id},{$height},'{$responseDiv}');" title='Refresh this comic'></div>
<div id='suspend_{$comic_id}' class={$s} onclick="SuspendComic({$comic_id},{$suspendAction},{$height},'{$responseDiv}');" title='{$suspendText} this comic'></div>
ICONS;
  }

  $curTime = time();
  $markReadBox = ($numUnread ? "<div class=mark_read id='mark_read_{$comic_id}' onclick='MarkRead({$comic_id},{$curTime});'>Mark this comic as read</div>" : '');
}
else
{
  $comicData = $db->GetRow("select * from comics where comic_id = {$comic_id}");
  $comicLink = '<center><h3>No data for this one yet!</h3></center>';
  $date = null;
}

echo <<<COMIC
{$nav}
{$icons}
{$markReadBox}
<div class="comic_header">
  <div class="comic_title">
    <h3>{$comicData['name']}</h3>
    <a href="{$comicData['url']}">Link to its website</a>

  </div>
  {$new}
  {$recent}
</div>
<div class="comic_date">{$date}</div>
<p class="comic_image">{$comicLink}</p>
COMIC;
?>
