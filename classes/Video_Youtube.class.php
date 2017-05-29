<?php

class Video_Youtube extends Video
{
  const CLASS_ID = 1;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'youtube.com/'))
    {
      $comic->DebugMsg('  * YouTube video');
      preg_match('/embed\/([^\?]*)/', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        file_put_contents($thumbFile, $comic->GetFile($imageLink ? $imageLink : "http://img.youtube.com/vi/{$srcArray[1]}/mqdefault.jpg"));
        return new Video_Youtube($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- YouTube video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? 'http://www.youtube.com/embed/%s' : 'www.youtube.com/watch?v=%s&feature=share_video_user';
  }
}

?>
