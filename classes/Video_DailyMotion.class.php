<?php

class Video_DailyMotion extends Video
{
  const CLASS_ID = 3;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'dailymotion.com/'))
    {
      $comic->DebugMsg('  * DailyMotion video');
      preg_match('/embed\/video\/(.*)/', $src, $srcArray);

     // download a thumbnail from DailyMotion
      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        file_put_contents($thumbFile, $comic->GetFile($imageLink ? $imageLink : "http://www.dailymotion.com/thumbnail/video/{$srcArray[1]}"));
        return new Video_DailyMotion($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- DailyMotion video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? 'http://www.dailymotion.com/embed/video/%s' : 'www.dailymotion.com/video/%s';
  }
}

?>
