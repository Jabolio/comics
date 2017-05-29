<?php

class Video_Hulu extends Video
{
  const CLASS_ID = 99;

  public function __construct()
  {
    parent::__construct(null, null, null);
  }

  public function noData()
  {
    return true;
  }

  static public function initFromLink($src, $comic)
  {
    if (strpos($src, 'hulu.com/'))
    {
      $comic->DebugMsg('  * Hulu video (skipping)');
      return new Video_Hulu();
    }
  }
}

?>
