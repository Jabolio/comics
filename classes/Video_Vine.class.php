<?php

class Video_Vine extends Video
{
  const CLASS_ID = 5;

  public function __construct($src, $link, $thumbFile)
  {
    parent::__construct($src, $link, $thumbFile);
  }

  static public function initFromLink($src, $comic, $imageLink)
  {
    if (strpos($src, 'vine.co/'))
    {
      $comic->DebugMsg('  * Vine video');
      preg_match('/\/v\/([^\/]*)\//', $src, $srcArray);

      if (isset($srcArray[1]))
      {
        if (!$imageLink)
        {
          $thumbFile = $comic->DOWNLOAD_DIR.'/'.$srcArray[1].'_thumb.png';
          $vine = file_get_contents("http://vine.co/v/{$srcArray[1]}");
          preg_match('/property="og:image" content="(.*?)"/', $vine, $matches);
          if ($matches[1])
            file_put_contents($thumbFile, $comic->GetFile($matches[1]));
          else
            copy(Comics::HOME.'video-overlay.png', $thumbFile);
        }
        else
          file_put_contents($thumbFile, $comic->GetFile($imageLink));


        return new Video_Vine($src, $srcArray[1], $thumbFile);
      }
      else
        $comic->DebugMsg('    -- Vine video source not found');
    }
  }

  static public function _GetTemplate($embed)
  {
    return $embed ? 'https://vine.co/v/%s/card?mute=1' : 'vine.co/v/%s/';
  }
}

?>
