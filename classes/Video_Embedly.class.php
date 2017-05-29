<?php

// The Cheezburger site uses embedly as a content wrapper.
// Extracts the src param and recurse on that.

class Video_Embedly extends Video
{
  const CLASS_ID = 0;

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'embedly.com/'))
    {
      parse_str(parse_url($src, PHP_URL_QUERY), $urlData);
      $comic->DebugMsg('  * Embedly link');
      $imageLink = (isset($urlData['image']) ? urldecode($urlData['image']) : null);
      return Video::init(urldecode($urlData['src']), $comic, $imageLink);
    }
  }
}

?>
