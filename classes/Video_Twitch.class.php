<?php

class Video_Twitch extends Video
{
  const CLASS_ID = 8;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'twitch.tv/'))
    {
      $comic->DebugMsg('  * Twitch video');
      preg_match('/channel=(.*)/', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        if ($imageLink)
          file_put_contents($thumbFile, $comic->GetFile($imageLink));
        else
          copy(Comics::HOME.'video-overlay.png', $thumbFile);

        return new Video_Twitch($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- Twitch feed source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? '' : 'www.twitch.tv/%s';
  }
}

?>
