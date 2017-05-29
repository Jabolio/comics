<?php

class Video_Coub extends Video
{
  const CLASS_ID = 6;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'coub.com/'))
    {
      $comic->DebugMsg('  * Coub video');
      preg_match('/embed\/([^\?]*)/', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
        if ($imageLink)
          file_put_contents($thumbFile, $comic->GetFile($imageLink));
        else
          copy(Comics::HOME.'video-overlay.png', $thumbFile);

        return new Video_Coub($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- Coub video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? '' : 'coub.com/view/%s';
  }
}

?>
