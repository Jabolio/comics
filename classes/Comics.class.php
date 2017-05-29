<?php

// Comics class definition (and related functions).

include_once('/home/comics/classes/DB.class.php');
include('/home/comics/classes/Video.class.php');

class Issue
{
  private $comicName;
  private $notFound;
  private $errors;
  const TEMPLATE = "%s\n %s\n%s";
  const LINE = '********************************************************';

  public function __construct($comicName)
  {
    $this->comicName = $comicName;
    $this->notFound = false;
    $this->errors = array();
  }

  public function NotFound()
  {
    $this->notFound = true;
  }

  public function AddError($error)
  {
    $this->errors[] = $error;
  }

 // returns a string interpretation of the error, using "\n" as a line delimiter.
  public function report()
  {
    $err = null;
    if ($this->notFound)
      $err = "    - Comic could not be downloaded\n";

    if (count($this->errors) > 0)
    {
      foreach($this->errors as $e)
        $err .= "    {$e}\n";
    }

    return sprintf(self::TEMPLATE, self::LINE, $this->comicName, $err);
  }
}


class Comics
{
  private $db;
  private $debugFn = null;
  private $dateStamp;
  private $testing = false;
  private $testingFiles = array();
  private $noFilesFound;

 // fake constants
  public $DOWNLOAD_DIR = '/tmp/comicDownload';  // this is set dynamically (albeit once).

  const ME = 'jpdeveaux@starcatcher.ca';
  const COMIC_WIDTH = 700;
  const COMIC_DIR = '/home/comics/public_html/';
  const COOKIE_FILE = '/home/comics/_comics.cookie.txt';
  const HOME = '/home/comics/';
  const AGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:20.0) Gecko/20100101 Firefox/20.0';
  const IMAGE_TEMPLATE = '<hr><img src="http://comics.starcatcher.ca/%s" alt="%s" title="%s" style="max-width:700px">';
  const VIDEO_TEMPLATE = '<hr><a href="http://%s" style="text-decoration:none; display:block;" class="nonplayable"><img src="http://comics.starcatcher.ca/%s" alt="%s" title="%s"></a>';
  const IMAGE_PATH = '%s/%s/%s';
  const COMIC_TEMPLATE = '<div style="padding:10px;width:720px;border: 1px solid black;text-align:center;"><h3 style="margin-top:6px; padding-top: 0px; text-align: left; padding-left: 30px;">%s &nbsp;&nbsp;&nbsp;<a style="font-size:11px" href="%s">(visit the site)</a></h3>%s</div>';

  public function __construct($db = null)
  {
    $this->db = ($db ? $db : new DB());
    $this->dateStamp = date('_Y-m-d');
    $this->DOWNLOAD_DIR .= (isset($_SERVER['HTTP_HOST']) ? '_web' : '');

   // list of comics for which there were issues (not found, etc)
    $this->issues = array();

    if (is_dir($this->DOWNLOAD_DIR))
      rrmdir($this->DOWNLOAD_DIR);

    mkdir($this->DOWNLOAD_DIR);
  }

 // set the outside function to be called when debug messages are sent.
  public function SetDebugFn($fn)
  {
    $this->debugFn = $fn;
  }

  public function TestMode($mode=true)
  {
    $this->testing = $mode;
  }

  public function GetTestingFiles()
  {
    return $this->testingFiles;
  }

// ===================================== COMIC FUNCTIONS (HIGH LEVEL) =================================================== //

 // download a comic, depending on its download mode.
  public function Download($comic)
  {
    if (!($comic['url'] && ($comic['active'] == 1)))
      return;

    $this->DebugMsg("Downloading {$comic['name']} (id: {$comic['comic_id']}; mode: {$comic['fetch_mode']})...");

    switch($comic['fetch_mode'])
    {
     // generic comic fetch: download page, then find strip files.
      case 1:
        if (!$this->GetComic($comic['comic_id'], $comic['url'], $comic['file_path'], $comic['num_files'], $comic['min_size']))
          $this->NotFound($comic);

        break;

     // this is for comics where the main page has a bunch of links, and each link must be accessed separately.  Never more than two pages deep.
     // the file_path field in the DB can contain 2 parts:
     //  - match pattern for the script/image URLs (including one set of parens that denote the url to execute, prefixed by the base url) from the main page retrieved
     //  - (optional) match pattern for any images that are to be retrieved from the second document retrieved (absolute or relative URLs)
      case 2:
        $script = $this->GetFile($comic['url']);
	$searchParams = explode('|',$comic['file_path']);
        $count = 0;

       // match against the first level regexp
        if (preg_match_all($searchParams[0], $script, $match))
        {
         // fetch each of these urls (to a limit), and retrieve all images from that page.
          foreach($match[1] as $m)
          {
           // prepend the base of the script URL if there was no http in the target (also below).  assumes $m starts with a /
            $internalReferer = BuildURL($comic['url'], $m);
            $internalScript = $this->GetFile($internalReferer, $comic['url']);

           // if there was an internal search specified, retrieve internal images.  there could be more than one.
            if (isset($searchParams[1]))
            {
              if(preg_match_all($searchParams[1], $internalScript, $internalMatch))
              {
                foreach($internalMatch[1] as $im)
                {
                  $imUrl = BuildURL($comic['url'], $im);
                  if (!$this->ProcessComic($this->GetFile($imUrl, $internalReferer), $comic['comic_id'], FindTitleTag($im, $internalScript), md5($comic['comic_id'].$imUrl)))
                    $this->NotFound($comic);
                }
              }
            }
            else
            {
              if (!$this->ProcessComic($internalScript, $comic['comic_id'], FindTitleTag($m, $script), md5($comic['comic_id'].$internalReferer)))
                $this->NotFound($comic);
            }

           // num_files for these comics pertains to the number of pages to load, as opposed to the number of files to return.
            $count++;
            if ($count == $comic['num_files'])
              break;
          }
        }
        else
          $this->NotFound($comic);

        break;

     // the Cheezburger sites.  These pages have lots of images and lots of structure so it's not easy to haul stuff out without DOM manipulation.
      case 3:
        $url = $comic['url'];
        $stopImage = $comic['file_path'];
        $newStopImage = null;
        $foundStopImage = false;
        $count = 0;
        $page = 1;

        while((!$foundStopImage) && $count < $comic['num_files'])
        {
          if ($this->testing)
            $this->DebugMsg(" - Loading {$url}...");

          $dom = @DOMDocument::loadHTML($this->GetFile($url));
          $xp = new DOMXpath($dom);

          $item = $xp->evaluate("//div[@class = 'post-asset-inner']");
          foreach($item as $i)
          {
            $srcArray = $title = $id = $src = null;

            $data = $i->getElementsByTagName('img');
            if ($data->length > 0)
            {
              $d = $data->item(0);
              $id = $d->getAttribute('id');
              $src = $d->getAttribute('src');
              $title = $d->getAttribute('title');

             // if we've handled this image before for this site, then stop.
              if ($id == $stopImage)
              {
                $foundStopImage = true;
                break;
              }

             // set the new "last seen image" id so the script knows where to stop
              if (!$newStopImage)
                $newStopImage = $id;

             // add this image to the database
              if (!$this->ProcessComic($this->GetFile($src, $url), $comic['comic_id'], $title))
                $this->NotFound($comic);

              $count++;
              if ($count == $comic['num_files'])
                break;
            }

           // if there wasn't an image in the div, then handle video.
            else
            {
              $data = $i->getElementsByTagName('div');
              if ($data->length > 0)
              {
                $id = $data->item(0)->getAttribute('id');
                $iframe = $i->getElementsByTagName('iframe');

               // if there was no iframe, then go to the next one.
                if (!$iframe->length)
                  continue;

                $title = $i->parentNode->parentNode->parentNode->getElementsByTagName('h2')->item(0)->getElementsByTagName('a')->item(0)->nodeValue;

               // pass the video init stuff to the video class framework
                $src = $iframe->item(0)->getAttribute('src');
                if ($videoObj = Video::init($src, $this))
                {
                 // some videos have no functionality; if that's the case, continue.
                  if ($videoObj->noData())
                    continue;

                 // don't add when testing...
                  if ($this->testing)
                    $this->DebugMsg(" - Iframe link found: {$src} - {$title} - {$videoObj->getLink()} - {$videoObj->getMode(true)}");
                  else
                  {
                    if($fileName = $this->SaveFile($videoObj->getThumbfile(), $videoObj->getMd5()))
                    {
                      $this->DebugMsg(" - Adding iframe link: {$src} - {$title} - {$videoObj->getLink()}");
                      $this->db->Execute("CALL ADDCOMIC({$comic['comic_id']}, '{$videoObj->getMd5()}', '{$videoObj->getImageMimeType()}', '{$this->db->escape($title)}', '{$videoObj->getLink()}', {$videoObj->getMode()})");
                    }
                  }
                }
               // if there was no thumbnail, the next part will fail horribly, so notify my and skip this.
                else
                {
                  $this->DebugMsg(" - No thumbFile found, skipping this video: Source: {$src}, Title: {$title}, ID: {$id}, URL: {$comic['url']}");
                  $this->AddError($comic, '- No thumbFile found');
                  $this->AddError($comic, "  * Source: {$src}");
                  $this->AddError($comic, "  * Title: {$title}");
                  $this->AddError($comic, "  * ID: {$id}");
                  $this->AddError($comic, "  * URL: {$comic['url']}");
                  continue;
                }
              }
            }
          }

         // if we haven't found the last image yet, then go to the next page.
          if (!$foundStopImage)
            $url = $comic['url'].'page/'.++$page;
        }

       // we save the id of the first image we saw so the script knows where to stop next time.
        if ((!$this->testing) && $newStopImage)
          $this->db->Execute("update comics set file_path = '{$newStopImage}' where comic_id = {$comic['comic_id']}");

        break;

     // Baby Blues.  From their site, not the King Features syndicated newspaper sites, which are nearly impossible to navigate  (nb: not currently used)
      case 4:
        $this->GetComic($comic['comic_id'], $comic['url']);
        $searchParams = explode('|', $comic['file_path']);

        if (!(grep($this->DOWNLOAD_DIR."/{$comic['comic_id']}/{$searchParams[0]}", $searchParams[1], $matches) && $this->ProcessComic($this->GetFile($matches[0], $comic['url']), $comic['comic_id'])))
          $this->NotFound($comic);

        break;

     // Oglaf.com.  Another weird structure: main page refers to first page of a story, but there could be more, have to follow links.
      case 5:
       // hit the page first to create the session cookie, and to accept the "over 18" thing.
        $cookie = $this->DOWNLOAD_DIR.'/oglaf.cookie.txt';
        $this->GetComic($comic['comic_id'], $comic['url'], null, null, null, true, null, $cookie, "--post-data 'over18=%C2%A0'");

        $parsed_url = parse_url($comic['url']);
        $url = $parsed_url['host'].'/';
        $searchParams = explode('|', $comic['file_path']);
        do
        {
          $this->GetComic($comic['comic_id'], 'http://'.$url, null, null, null, true, null, $cookie);
          if (preg_match($searchParams[0], file_get_contents($this->DOWNLOAD_DIR."/{$comic['comic_id']}/{$url}index.html"), $newUrl))
            $url = preg_filter($searchParams[1], $parsed_url['host'].'/$1', $newUrl[0]);
          else
            $url = '';
        } while ($url);

        if (!$this->GetComic($comic['comic_id'], null, $searchParams[2]))
          $this->NotFound($comic);

        break;

     //  - this is identical to case 2, except the next level of matching occurs on the result of the first level of matching (allowing nested regexp processing
     //  - levels of regexps must be separated by a pipe.
      case 6:
        $script = $this->GetFile($comic['url']);
        $searchParams = explode('|',$comic['file_path']);
        $count = 0;

       // match against the first level regexp
        if (preg_match_all($searchParams[0], $script, $match))
        {
         // each of these will be regexp'ed again for the actual URLs.fetch each of these urls (to a limit), and retrieve all images from that page.
          foreach($match[1] as $m)
          {
           // if there was an internal search specified, retrieve internal images.  there could be more than one.
            if (isset($searchParams[1]))
            {
              if(preg_match_all($searchParams[1], $m, $internalMatch))
              {
                foreach($internalMatch[1] as $im)
                {
                  $imUrl = BuildURL($comic['url'], $im);
                  if (!$this->ProcessComic($this->GetFile($imUrl, $comic['url']), $comic['comic_id'], FindTitleTag($im, $script), md5($comic['comic_id'].$imUrl)))
                    $this->NotFound($comic);
                }
              }
            }
            else
            {
             // this should work like case 2 if no second search param is provided.
              $internalReferer = BuildURL($comic['url'], $m);
              $internalScript = $this->GetFile($internalReferer, $comic['url']);

              if (!$this->ProcessComic($internalScript, $comic['comic_id'], FindTitleTag($m, $script), md5($comic['comic_id'].$internalReferer)))
                $this->NotFound($comic);
            }

           // num_files for these comics pertains to the number of pages to load, as opposed to the number of files to return.
            $count++;
            if ($count == $comic['num_files'])
              break;
          }
        }
        else
          $this->NotFound($comic);

        break;

    }

    $this->DebugMsg("=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=");
  }

 // send comics downloaded since $ts (which is a datetime) to this user
 // $ts must be properly formatted before coming in here (with quotes around the datetime, or as the word 'null')
  public function DeliverComics($userData, $ts)
  {
    if (!isset($userData['user_id']))
      return;

   // get a list of comics for this user
    $comicsToSend = $this->db->GetArray("call usercomiclist({$userData['user_id']}, {$ts})");
    $this->DebugMsg("Comics to send:\n".print_r($comicsToSend, true).print_r($userData, true));

   // if there are files to deliver, then deliver them.
    if (count($comicsToSend) > 0)
    {
     // copy files to a temp dir for attachment delivery (so they can be renamed)
      $deliveryDir = $this->DOWNLOAD_DIR.'/delivery';
      mkdir ($deliveryDir);

      $body = '';
      foreach($comicsToSend as $comic)
      {
        $imagesForThisComic = $this->db->GetArray("call USERCOMICIMAGES({$comic['comic_id']}, '{$comic['ts']}')");
        $this->DebugMsg(" - Images for {$comic['name']}:\n".print_r($imagesForThisComic, true));

        $comicData = '';
        if (is_array($imagesForThisComic) && count($imagesForThisComic) > 0)
        {
          $count = 0;
          foreach($imagesForThisComic as $i)
          {
            $imgPath = sprintf(self::IMAGE_PATH, substr($i['comic_hash'],0,2), substr($i['comic_hash'],2,2), $i['comic_hash']);

           // videos are only included in the HTML comics.
            if ($i['video_key'])
              $comicData .= sprintf(self::VIDEO_TEMPLATE, sprintf(Video::GetTemplate($i['video_mode']), $i['video_key']), $imgPath, $comic['name'], htmlspecialchars($i['alt_text']))."\n";
            else
            {
              $comicData .= sprintf(self::IMAGE_TEMPLATE, $imgPath, $comic['name'], htmlspecialchars($i['alt_text']))."\n";
              copy (self::COMIC_DIR.$imgPath, $this->DOWNLOAD_DIR.'/delivery/'.str_replace(' ','',$comic['name']).$this->dateStamp.($count ? sprintf('_%02d', $count) : '').'.'.substr($i['mime_type'], 6));
              $count++;
            }
          }
        }

        $body .= sprintf(self::COMIC_TEMPLATE, $comic['name'], $comic['url'], $comicData)."\n";
      }

      $mailSubject = "Comics for ".date('l, F jS, Y');

     // if we're sending by HTML, then use what we created, otherwise send the files we copied as attachments.
      if ($userData['delivery_mode'] == 1)
      {
        $this->DebugMsg("==============================\nBody: {$body}\n=================================");
        $headers  = "From: Starcatcher Comics <comics@starcatcher.ca>\r\nContent-type: text/html\r\n"; 

       // sent the comic out.
        mail($userData['email'], $mailSubject, $body, $headers);
      }
      else
      {
        $this->DebugMsg("==============================\n".print_r(glob($deliveryDir.'/*'), true)."==============================");     // */
        exec("mutt -s '{$mailSubject}' -a {$deliveryDir}/* -- {$userData['email']} < /dev/null");   // */
      }

     // update DB to reflect when the comics were last sent.
      $this->db->Execute('update userComics set last_viewed = now() where fk_user_id = '.$userData['user_id']);

     // clean up
      rrmdir($deliveryDir);
    }
  }

 // add a comic to the not-found list.  $c is a row from the comic list query
  public function NotFound($c)
  {
    $this->GetIssue($c)->NotFound();
  }

 // document an error that occurred during the comic retrieval process.
  public function AddError($c, $err)
  {
    $this->GetIssue($c)->AddError($err);
  }

 // initialize (if necessary) and return the issue variable for a comic.
 // $c can be a comic list row or a comic ID.
  public function GetIssue($c)
  {
    global $db;
    $id = (isset($c['comic_id']) ? $c['comic_id'] : $c);

    if (!isset($this->issues[$id]))
      $this->issues[$id] = new Issue(isset($c['name']) ? $c['name'] : $db->GetValue("select name from comics where comic_id = {$id}", 'name'));

    return $this->issues[$id];
  }

 // if any strips had issues, send a report.
  public function SendIssueReport()
  {
    if (count($this->issues) > 0)
    {
      $body = "The following strips had issues:\n\n";
      foreach($this->issues as $c)
        $body .= $c->report();

      mail(self::ME, 'Comics issues - '.date('F jS, Y \a\t g:ia'), $body.Issue::LINE);
    }
  }

 // delete temporary files
  public function Cleanup()
  {
    chdir(self::HOME);
    rrmdir($this->DOWNLOAD_DIR);
  }

// ===================================== COMIC FUNCTIONS (LOW LEVEL) =================================================== //

 // download and return a single file from a url
  public function GetFile($url, $referer = null, $cookie = self::COOKIE_FILE)
  {
    $this->DebugMsg(" - getting file at {$url} ".($referer ? "(with referer {$referer})" : ''));

    if (!$url)
      return false;

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    curl_setopt($ch, CURLOPT_USERAGENT, self::AGENT);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);

   // set up cookie handling
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);

    if ($referer)
      curl_setopt($ch, CURLOPT_REFERER, $referer);

    return curl_exec($ch);
  }

 // request a URL; download and save all files (images, scripts, etc) related to this URL.
  private function wget($url, $cookieFile = self::COOKIE_FILE, $referer = null, $extraParams = null)
  {
    if (!$url)
      return false;

    $referer = ($referer ? "--referer={$referer}" : null);
    $url = str_replace("'", "\'", $url);
    $wgetCommand = "wget -E --header='accept-encoding: none' -U '".self::AGENT."' -q -H -p -T 60 -t 1 {$referer} {$extraParams} --load-cookies $cookieFile --save-cookies $cookieFile -e 'robots = off' '{$url}'";

    $this->DebugMsg(" - {$wgetCommand}");
    exec($wgetCommand);
  }

 // download and process a comic from a particular url.  The target files will be at the path described in $file.
 // $maxFiles sets the maximum number of files to return
 // $minSize specifies the minimum size (e.g., 50k) of a file to return, in case other unwanted, smaller files are in the same path
 // set $hashFileName to true to use a hash of the file name when storing the file; otherwise a hash of the image will be used.
  private function GetComic($id, $url, $file=null, $maxFiles=null, $minSize=null, $hashFileName=true, $referer=null, $cookieFile=self::COOKIE_FILE, $extra=null)
  {
    $this->DebugMsg(" - GetComic call: $id, $url, $file, $maxFiles, $minSize, $referer, $cookieFile");

   // for easier searching for title tags
    $dir = $this->DOWNLOAD_DIR."/{$id}";
    if(!is_dir($dir))
      mkdir($dir);

    chdir($dir);

   // process the URL
    $this->wget($url, $cookieFile, $referer, $extra);

   // if the file to fetch is empty, then return false (not necessarily a bad thing)
    if (!$file)
      return false;

   // size limit (to filter out small files that are probably not comics)
    $sizeLimit = ($minSize ? "-size +{$minSize}" : null);

   // if we only want a certain number, find files matching the pattern, ordered by size in descending order
   // otherwise find all files and sort alphabetically
    if ($maxFiles)
    {
      $findStr = "find {$file} {$sizeLimit} -type f -exec du -sk {} \; 2>&1 | sort -nr";
      exec($findStr, $results);
      foreach($results as &$r)
        $r = preg_replace('/\d*\s*(.*)/', '\1', $r);
    }
    else
    {
      $findStr = "find {$file} {$sizeLimit} -type f 2>&1 | sort";
      exec($findStr, $results);
    }

    $this->DebugMsg(" - {$findStr}\n====================\n".print_r($results,true)."====================");

   // if "no such file" was there, the file wasn't found.
    if (!isset($results[0]))
    {
      $this->AddError($id, '- find results missing');
      $this->AddError($id, print_r(debug_backtrace(), true));
    }
    elseif (strpos($results[0], 'No such file') === false)
    {
      $count = 0;
      foreach($results as $comicFile)
      {
       // try to find a title tag for this comic.  Some comics don't have the full $comicFile var in the image, so strip it down to just the path.
        $imgPath = parse_url('http://'.$comicFile);
        if(grep ('.', '/<img[^>]*'.str_replace('/', '\/', substr($imgPath['path'],1)).'[^>]*>/i', $imgTagScan))
          $titleTag = html_entity_decode(preg_filter('/.*title\s*=\s*"([^"]*).*/i', '$1', preg_replace("/[\r\n]/", '', $imgTagScan[0])), ENT_QUOTES);
        else
          $titleTag = null;

       // create the md5 based on the filename
        $this->ProcessComic(file_get_contents($comicFile), $id, $titleTag, ($hashFileName ? md5($id.$comicFile) : null));
        $count++;
        if ($count == $maxFiles)
          break;
      }

      return true;
    }
  }

 // process a comic file and add it to the DB.
 // $comicData is a variable containing the raw image data
 // $m is an (optional) hash to be used as the filename for the image when it is saved.
  private function ProcessComic($comicData, $comic_id, $alt_text=null, $m=null)
  {
   // if there's nothing, get out.
    if (!$comicData)
      return false;

    if ($alt_text)
      $this->DebugMsg(" - Title tag: {$alt_text}");

   // make sure there are no unescaped apostrophes in here...
    if ($alt_text)
      $alt_text = $this->db->escape($alt_text);

   // if we're testing, add the file to the testing files array, but don't add it.
    if ($this->testing)
    {
      $fileName = $this->DOWNLOAD_DIR.'/'.md5(microtime(null)).'.png';
      file_put_contents($fileName, $comicData);
      $this->testingFiles[] = $fileName;
      return true;
    }

    try
    {
      if($fileName = $this->SaveFile($comicData, $m))
      {
        $img = new Imagick($fileName);

       // if the file is too wide (and not animated), then shrink it.
        $frames = exec("identify {$fileName} | wc -l");
        if($frames == 1)
        {
          if ($img->GetImageWidth() > self::COMIC_WIDTH)
          {
            $img->scaleImage(self::COMIC_WIDTH, 0);
            unlink($fileName);
            $img->writeImage($fileName);
          }
        }

       // register this image with the database
        $this->db->Execute("CALL ADDCOMIC({$comic_id}, '{$m}', '{$img->getImageMimeType()}', '{$alt_text}', null, null)");
        $this->DebugMsg(" - file {$fileName} added to database.");
      }
    }
    catch (Exception $e)
    {
      $this->DebugMsg(" - Thumbnail creation error: {$e->getMessage()}");
      $this->AddError($comic_id, '- No thumbFile found');
      $this->AddError($comic_id, '  * '.$e->getMessage());
    }
    finally
    {
      return true;
    }
  }

 // save a file as its hash, and return the name of the file.
  private function SaveFile($comicData, &$m=null)
  {
   // get MD5 hash (if one wasn't already provided)
    if (!$m)
      $m = md5($comicData);

   // check if the file is already here.
    $d1 = substr($m,0,2);
    $d2 = substr($m,2,2);
    $path = self::COMIC_DIR."$d1/$d2";
    $fileName = "{$path}/{$m}";

   // if file exists, don't return anything.
    if (file_exists($fileName))
      $this->DebugMsg(" - file {$fileName} already exists in the database.");
    else
    {
     // create the folder (recursively)
      if (!is_dir($path))
        mkdir($path, 00777, true);

     // save the file, and create an ImageMagick object from it
      file_put_contents($fileName, $comicData);
      $this->DebugMsg(" - file {$fileName} saved locally.");

      return $fileName;
    }
  }

 // if there is a debug function set, then call it with the message that was passed in.
  public function DebugMsg($msg)
  {
    if (function_exists($this->debugFn))
      call_user_func($this->debugFn, $msg);
  }
}

// ===================================== NON-CLASS FUNCTIONS ======================================================= //

function rrmdir($path)
{
  exec("rm -rf {$path}");
}

// a simple grep implementation.  The pattern must be one that works with preg_match
function grep($path, $pattern, &$results)
{
 // find all files in the path (incl folders)
  $items = glob($path . '/*');               // */

  for ($i = 0; $i < count($items); $i++)
  {
    if (is_dir($items[$i]))
    {
      $add = glob($items[$i] . '/*');        // */
      $items = array_merge($items, $add);
    }
  }

 // search through the files.
  $results = array();
  foreach($items as $f)
  {
    if (filetype($f) == 'file')
    {
      if (preg_match($pattern, file_get_contents($f), $matches))
        $results = array_merge($results, $matches);
    }
  }

  return (count($results) > 0);
}

// used to build full URLs out of incomplete URLs where necessary
function BuildURL($url, $target)
{
 // if :// is in the target, return the target intact.
  if (strpos($target, '://') !== false)
    return $target;

  $p = parse_url($url);

 // if the target starts with //, return the existing scheme and the target (all that was missing was http or https)
  if(strpos($target, '//') === 0)
    return "{$p['scheme']}:{$target}";

 // all paths in the DB must end in a slash.  If they don't, the last part is assumed to be a file,
 // and must be removed from the path variable.
  if (isset($p['path']))
  {
    if ($p['path'][strlen($p['path'])-1] != '/')
      $p['path'] = dirname($p['path']).'/';
  }
  else
    $p['path'] = '/';

  return $p['scheme'].'://'.$p['host'] . ($target[0] == '/' ? '' : $p['path']) . $target;
}

// look for a title tag within a script.
function FindTitleTag($needle, $haystack)
{
  preg_match('/<img[^>]*'.str_replace('/', '\/', $needle).'[^>]*>/i', $haystack, $imgTagScan);
  if (isset($imgTagScan[0]))
    return html_entity_decode(preg_filter('/.*title\s*=\s*"([^"]*).*/i', '$1', preg_replace("/[\r\n]/", '', $imgTagScan[0])), ENT_QUOTES);
}

?>
