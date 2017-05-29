<?php

class Page
{
  private $title;
  private $headStuff;
  private $bodyOnLoad;

  const ERROR_CLASS    = 'class=error_tr';
  const ERROR_TEMPLATE = '<tr><td>&nbsp;</td><td><ul class=errorlist><li>%s</li><ul></td></tr>';

  public function __construct($title = null, $logged_in = false)
  {
    global $email, $user_id;

    $this->title = $title;
    if ($logged_in)
    {
      $adminLink = ($user_id == 1 ? '<a href="/admin.php">admin</a> - ' : '');

      $this->headStuff = <<<HEAD
  <div id="head_right">
    <a href="/profile.php"><i><b>$email</b></i></a> - <a href="/profile.php">profile</a> - <a href="/request.php">request</a> - {$adminLink}<a href="/logout.php">logout</a>
  </div>
  <div id="head_left">
    Comics:&nbsp;&nbsp;<a href="/choose.php">choose</a> - <a href="/read.php">show all subscribed</a> - <a href="/read.php?u=1">show only unread</a>
  </div>
HEAD;

    }
    else
    {

      $this->headStuff = <<<HEAD
<div id="head_right">
  <span class=""><a href="/login.php">login</a></span>
</div>
<div id="head_left">&nbsp;</div>
HEAD;

    }
  }

  public function SetContent($content)
  {
    $this->content = $content;
  }

  public function SetBodyOnLoad($code)
  {
    $this->bodyOnLoad .= $code;
  }

  public function DisplayPage()
  {
    if ($this->bodyOnLoad)
      $bodyOnLoad = "<script type='text/javascript'>{$this->bodyOnLoad}</script>";
    else
      $bodyOnLoad = null;

    echo <<<PAGE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="utf-8" http-equiv="encoding">
    <title>{$this->title}</title>
    <link rel="stylesheet" href="http://comics.starcatcher.ca/css/base.css" type="text/css"/>
    <link rel="shortcut icon" href="http://comics.starcatcher.ca/images/favicon.png" type="image/png" />
    <script src="http://comics.starcatcher.ca/js/jquery-1.9.1.min.js" type="text/javascript"></script>
    <script src="http://comics.starcatcher.ca/js/comics.js" type="text/javascript"></script>
    <script src="http://comics.starcatcher.ca/js/hoverintent.js" type="text/javascript"></script>
  </head>
  <body>
    <div id="head">
      {$this->headStuff}
      <div id="branding">
        <a href="/">{$this->title}</a>
      </div>
    </div>
    <div id=content>{$this->content}</div>
  </body>
  {$bodyOnLoad}
</html>
PAGE;
  }
}

?>
