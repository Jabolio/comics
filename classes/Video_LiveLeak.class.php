<?php

class Video_LiveLeak extends Video
{
  const CLASS_ID = 7;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'liveleak.com/'))
    {
      $comic->DebugMsg('  * LiveLeak video');
      preg_match('/\?f=(.*)/', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        if ($imageLink)
          file_put_contents($thumbFile, $comic->GetFile($imageLink));
        else
          copy(Comics::HOME.'video-overlay.png', $thumbFile);

        return new Video_LiveLeak($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- LiveLeak video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? '' : 'www.liveleak.com/view?f=%s';
  }
}

?>
