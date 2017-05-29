<?php

class Video_FunnyOrDie extends Video
{
  const CLASS_ID = 4;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'funnyordie.com/'))
    {
      $comic->DebugMsg('  * FunnyOrDie video');
      preg_match('/embed\/(.*)/', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        if (!$imageLink)
        {
          $metaData = json_decode(file_get_contents("http://www.funnyordie.com/oembed.json?url=http%3A%2F%2Fwww.funnyordie.com%2Fvideos%2F{$srcArray[1]}"), true);
          file_put_contents($thumbFile, $comic->GetFile($metaData['thumbnail_url']));
        }
        else
          file_put_contents($thumbFile, $comic->GetFile($imageLink));

        return new Video_FunnyOrDie($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- FunnyOrDie video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? 'http://www.funnyordie.com/embed/%s' : 'www.funnyordie.com/videos/%s';
  }
}

?>
