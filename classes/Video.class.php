<?php

// Video class definition.  This script also includes the other Video class
// files (at the bottom)

class Video
{
  protected $src;
  protected $link;
  protected $mode;
  protected $tn;
  protected $thumbFile;

  static public $classes;

 // constructor for all classes; must be called explicitly from within the class.
  public function __construct($src, $link, $thumbFile)
  {
    $this->src = $src;
    $this->link = $link;
    $this->thumbFile = $thumbFile;
    $c = get_called_class();
    $this->mode = $c::CLASS_ID;
  }

 // called on behalf of all loaded video parsing classes
  static public function register()
  {
    $className = get_called_class();
    $id = $className::CLASS_ID;
    self::$classes[$id] = $className;
  }

  static public function init($src, $comic, $imageLink=null)
  {
    foreach(self::$classes as $id => $class)
    {
      if ($obj = call_user_func($class.'::initFromLink', $src, $comic, $imageLink))
      {
        if (!$obj->noData())
        {
          $tn = new Imagick($obj->thumbFile);
          $overlay = new Imagick('/home/comics/video-overlay.png');
          $tn->scaleImage(400, 0);
          $tn->compositeImage($overlay, Imagick::COMPOSITE_DEFAULT, 0, 0);
          $tn->flattenImages();
          unlink($obj->thumbFile);
          $tn->writeImage();
          $obj->tn = $tn;
        }

        return $obj;
      }
    }
  }

  static public function GetTemplate($id,$embed=false)
  {
    return call_user_func(self::$classes[$id].'::_GetTemplate', $embed);
  }

  public function getLink()
  {
    return $this->link;
  }

  public function getMode($verbose=false)
  {
    return $verbose ? self::$classes[$this->mode] : $this->mode;
  }

  public function getImageMimeType()
  {
    return $this->tn->getImageMimeType();
  }

  public function getMd5()
  {
    return md5($this->thumbFile);
  }

  public function getThumbfile()
  {
    return file_get_contents($this->thumbFile);
  }

 // override for classes that are placeholders but that don't actually do anything
  public function noData()
  {
    return false;
  }
}

// include derived Video classes.
foreach (glob('/home/comics/classes/Video_*.class.php') as $filename)
{
  include($filename);
  $class = basename($filename,'.class.php');
  $class::register();
}

?>
