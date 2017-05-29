<?php

class Video_Vimeo extends Video
{
  const CLASS_ID = 2;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'vimeo.com/'))
    {
      $comic->DebugMsg('  * Vimeo video');
      preg_match('/video\/([^\?]*)/', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        if (!$imageLink)
        {
          $metaData = simplexml_load_string($comic->GetFile("http://vimeo.com/api/v2/video/{$srcArray[1]}.xml"));
          file_put_contents($thumbFile, $comic->GetFile($metaData->video->thumbnail_large));
        }
        else
          file_put_contents($thumbFile, $comic->GetFile($imageLink));

        return new Video_Vimeo($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- Vimeo video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? 'http://player.vimeo.com/video/%s' : 'vimeo.com/%s';
  }
}

?>
