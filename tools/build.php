<?php
// Show all errors
error_reporting(E_ALL);
// London timezone
date_default_timezone_set("Europe/London");

class builder{
  /*CONFIG START*/
  private $destinationFolder = 'htdocs/';
  private $dir       = '';
  private $blogposts = 'blogposts/';
  private $dirArticles  = 'articles/';
  private $dirPages  = 'pages/';
  private $cssPath   = 'css/style.css';
  private $css       = '';
  private $justCopy  = ['favicon.ico','imgs','css','js','robots.txt','uploads'];
  private $generateForIPFS = false;
  /*CONFIG END*/

  function __construct($dir=''){
    $this->dir = $dir;
    $this->destinationFolder = $this->dir.$this->destinationFolder;
  }

  private function recurse_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                echo 'copied '.$dst.'/'.$file."\n";
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
  }

  public function build(){
    $build = [];

    if(isset($GLOBALS['argv']) && is_array($GLOBALS['argv']) && count($GLOBALS['argv'])>1) {
        $args = $GLOBALS['argv'];
    }else {
        $args = [];
    }
    foreach($args as $k=>$v){
      switch ($v) {
          case 'ipfs':
              $this->generateForIPFS = true;
              unset($args[$k]);
              break;
      }
    }
    if(count($args)>1){
      foreach($args as $v){
        switch ($v) {
          case 'blog': 
          case 'blogs': 
          case 'blogposts':
            $build['blog'] = 'blog';
            $build['pages'] = 'pages';
            break; 
          case 'static':
            $build['static'] = 'static';
            break;
          case 'pages':
            $build['articles'] = 'articles';
            $build['blog'] = 'blog';
            $build['pages'] = 'pages';
            break;
          case 'articles':
            $build['articles'] = 'articles';
            break;
        }
      }
    } else {
      $build = ['static'=>1,'blog'=>1,'pages'=>1,'articles'=>1];
    }
    $this->css = file_get_contents($this->cssPath);
    if(isset($build['static'])){
      echo "Copying Static Files\n";
      $this->copyStaticFiles();
    }
    if(isset($build['blog'])){
      echo "Building Blog\n";
      $this->buildBlog();
    }
    if(isset($build['articles'])){
      echo "Building Articles\n";
      $this->buildArticles($this->dir.$this->dirArticles);
    }
    if(isset($build['pages'])){
      echo "Building Pages\n";
      $this->buildPages($this->dir.$this->dirPages);
    }
  }

  private function copyStaticFiles(){
    @mkdir($this->destinationFolder);
    //copy static files
    foreach($this->justCopy as $fileOrFolder){
      //if we are copying a file
      if(is_file($this->dir.$fileOrFolder)){
        echo 'copied '.$this->destinationFolder.$fileOrFolder."\n";
        copy($this->dir.$fileOrFolder,$this->destinationFolder.$fileOrFolder);
      }else{ //else we are copying a folder
        //check if the folder needs creating in the destination
        if(!is_dir($this->destinationFolder.$fileOrFolder)){
          mkdir($this->destinationFolder.$fileOrFolder);
        }
        //copy the contents over
        $this->recurse_copy($this->dir.$fileOrFolder,$this->destinationFolder.$fileOrFolder);
      }
    }
  }
  
  //build pages
  private function buildPages($dir){
    @mkdir($dir,0777,true);
    if($handle = opendir($dir)){
      while(false !== ($entry = readdir($handle))){
        if(is_dir($dir.$entry) && $entry != '.' && $entry != '..'){
          $this->buildPages($dir.$entry.'/');
        }
        if(substr($entry,-5) != '.json'){continue;}
        $json = json_decode(file_get_contents($dir.$entry),true);

        $page = new page($json['title'],$this->css,$this->generateForIPFS,$json['relativePath']);
        $content = file_get_contents(substr($dir.$entry,0,-5).'.html');
        //pagify everything except the home page
        if ('/' !== $json['url']){
          $content = $page->pagify('', $json['title'], '', $content);
        }
        $page->setContent($content);
        $content = $page->build();
        $this->generateFile($this->destinationFolder.$json['url'].'/index.html',$content);
      }
    }
  }

  //build articles
  private function buildArticles($dir){
    @mkdir($dir,0777,true);
    $jsonArticles = [];
    if($handle = opendir($dir)){
        while(false !== ($entry = readdir($handle))){
            if($entry=='.' || $entry=='..'){continue;}
            if(substr($entry, -5) !== '.json'){continue;}
            $json = json_decode(file_get_contents($dir.$entry),true);
            if(isset($json['live']) && $json['live']==='no'){continue;}

            $page = new page($json['title'],$this->css,$this->generateForIPFS,$this->generateForIPFS?'../../':'/');
            $content = file_get_contents(substr($dir.$entry,0,-5).'.html');
            $json['content'] = $content;

            $jsonArticles[$json['title']] = $json;

            $content = $page->pagify('', 'Article', 'By Craig Mayhew', $content);
            $page->setContent($content);
            $content = $page->build();
            $this->generateFile($this->destinationFolder.'articles/'.$json['url'].'/index.html',$content);
        }

        //now order articles by date DESC
        uasort($jsonArticles, function($a, $b) {
            if($a['lastupdated']==$b['lastupdated']){
                return 0;
            }

            return ($b['lastupdated'] < $a['lastupdated'] ? -1 : 1);
        });

        //build list of articles page
        $frontPage = '';
        $textPreviewLength = 220;
        $i=0;
        foreach($jsonArticles as $json){
            $i++;
            $text = explode(' ', substr(strip_tags($json['content']), 0, $textPreviewLength));
            array_pop($text);
            $text = trim(implode(' ', $text)).'…';
            $frontPage .=
                '<br /><br /><br /><h3><a href="articles/'.$json['url'].'">'.$json['title'].'</a></h3>'.
                '<br />'.$text.'<br /><br />';
            if($i===50){break;}
        }

        //articles front page
        $page = new page('Craig Mayhew\'s Articles',$this->css,$this->generateForIPFS,$this->generateForIPFS?'../':'/');
        $content = $page->blogify(
            'articles/',
            '<span>&nbsp;</span>&nbsp;',
            'Latest articles',
            'by Craig Mayhew',
            nl2br($frontPage)
        );
        $page->setContent($content);
        $page->setSideNav($this->getSideNav('..'));
        $content = $page->build();
        $this->generateFile($this->destinationFolder.'articles/index.html',$content);
    }
  }

  private function getSideNav($depth = '..') {
    return
    '<div class="sidebar">'.
        '<aside class="widget">'.
            '<h3>Recent Posts</h3>'.
            '<ul>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/reprap-4-year-project/">3D Printer</a></li>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/usb-secure-eraser/">USB Eraser</a></li>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/dns-the-original-cdn/">DNS as a CDN</a></li>'.
            '</ul>'.
        '</aside>'.
        '<aside class="widget">'.
            '<h3>Category</h3>'.
            '<ul>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/cat/Astrothoughts/">Astrothoughts</a></li>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/cat/Code/">Code</a></li>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/cat/General/">General</a></li>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/cat/Reviews-Experience/">Reviews/Experience</a></li>'.
                '<li><a href="'.($this->generateForIPFS?$depth:'blog').'/cat/General-Techie/">Techie</a></li>'.
            '</ul>'.
        '</aside>'.
    ($this->generateForIPFS?'':'<a class="backHome" href="/">Back to home</a>').
    '</div>';
  }

  //build blog section
  private function buildBlog(){
    $jsonBlogPosts  = [];
    $jsonBlogCats   = [];
    $jsonBlogTags   = [];
    if($handle = opendir($this->dir.$this->blogposts)){
      while(false !== ($entry = readdir($handle))){
        if($entry=='.' || $entry=='..'){continue;}
        if(substr($entry, -5) !== '.json'){continue;}
        $json = json_decode(file_get_contents($this->dir.$this->blogposts.$entry),true);
        $jsonBlogPosts[$json['name']] = $json;
      }
    }

    //now order blog posts by date DESC
    uasort($jsonBlogPosts, function($a, $b) {
      if($a['date']==$b['date']){
        return 0;
      }
       
      return ($b['date'] < $a['date'] ? -1 : 1);
    });
    
    //now work out tags and categories 
    if(count($jsonBlogPosts)>0){
      $i=0;

      //build front page
      $frontPage = '';
      $textPreviewLength = 220;
      foreach($jsonBlogPosts as $json){
        $i++;
        $text = explode(' ', substr(strip_tags($json['content']), 0, $textPreviewLength));
        array_pop($text);
        $text = implode(' ', $text).'…';
        $frontPage .=
        '<br /><br /><br /><h3><a href="'.($this->generateForIPFS?'':'blog/').$json['name'].'/">'.$json['title'].'</a></h3>'.
        '<br />'.$text.'<br /><br />';
        if($i===50){break;}
      }
       
      //front page
      $page = new page('Craig Mayhew\'s Blog',$this->css,$this->generateForIPFS,$this->generateForIPFS?'../':'/');
      $content = $page->blogify(
        'blog/',
        '<span>&nbsp;</span>&nbsp;',
        'Latest blog posts',
        'by Craig Mayhew',
        nl2br($frontPage)
      );
      $page->setContent($content);
      $page->setSideNav($this->getSideNav('.'));
      $content = $page->build();
      $this->generateFile($this->destinationFolder.'blog/index.html',$content);
      
      foreach($jsonBlogPosts as $json){
        //tags
        if(isset($json['tags']) && is_array($json['tags'])){
          foreach($json['tags'] as $tag){
            if(isset($jsonBlogTags[$tag])){
              $jsonBlogTags[$tag][] = $json;
            }else{
              $jsonBlogTags[$tag] = [$json];
            }
          }
        }
        //category
        if(isset($json['categories'])){
          foreach($json['categories'] as $cat){
            if(isset($jsonBlogCats[$cat])){
              $jsonBlogCats[$cat][] = $json;
            }else{
              $jsonBlogCats[$cat] = [$json];
            }
          }
        }
        //create blog post file
        $page = new page($json['title'],$this->css,$this->generateForIPFS,$this->generateForIPFS?'../../':'/');
        $tags = '<br /><br />';
        if (isset($json['tags']) && is_array($json['tags'])) {
            foreach ($json['tags'] as $c) {
                $tags .= '<a href="'.($this->generateForIPFS?'../../':'/').'blog/tag/' . str_replace(' ','-',$c) . '">' . $c . '</a> &nbsp; ';
            }
        }

        $content = $page->blogify(
            'blog/'.$json['name'].'/',
            '<span>'.date('d',strtotime($json['date'])).'</span>'.date('M Y',strtotime($json['date'])),
            $json['title'],
            'by Craig Mayhew on '.date('D jS M Y',strtotime($json['date'])).' under '.implode(', ',$json['categories']),
            nl2br($json['content']).$tags
        );
        $page->setContent($content);
        $page->setSideNav($this->getSideNav('..'));
        $content = $page->build();
        $this->generateFile($this->destinationFolder.'blog/'.$json['name'].'/index.html',$content);
      }
    }
    unset($handle,$entry,$json);

    //create blog archive page
    $content = '';
    foreach($jsonBlogPosts as $json){
        //add blog post to the archive array
        $content .= substr($json['date'],0,10).' <a href="'.($this->generateForIPFS?'../':'blog/').$json['name'].'/">'.$json['title'].'</a><br>';
    }
    $page = new page('Blog Archive',$this->css,$this->generateForIPFS,$this->generateForIPFS?'../../':'/');
    $content = $page->blogify('blog/archive/','<span>&nbsp;</span>&nbsp;', 'Blog Archive', 'by Craig Mayhew', nl2br($content));
    $page->setContent($content);
    $page->setSideNav($this->getSideNav('..'));
    $content = $page->build();
    $this->generateFile($this->destinationFolder.'blog/archive/index.html',$content);

    //create tag pages
    foreach($jsonBlogTags as $tag=>$posts){
      $content = '';
      $i=0;
      foreach($posts as $json){
        $tags = '<br /><br />';
        if(isset($json['tags'])){
          foreach($json['tags'] as $c){
            $tags .= '<a href="'.($this->generateForIPFS?'../../../':'/').'blog/tag/'.str_replace(' ','-',$c) .'">'.$c.'</a> &nbsp; ';
          }
        }
        $text = explode(' ', substr(strip_tags($json['content']), 0, $textPreviewLength));
        array_pop($text);
        $content .=
        '<br /><br /><br /><h3><a href="'.($this->generateForIPFS?'../../../':'/').'blog/'.$json['name'].'">'.$json['title'].'</a></h3>'.
        '<br />'.implode(' ', $text).'…'.$tags.'<br /><br />';
        $i++;
        if($i===6){break;}
      }
      $page = new page($tag,$this->css,$this->generateForIPFS,$this->generateForIPFS?'../../../':'/');
      $url = 'blog/tag/'.str_replace(['/',' '],'-',$tag).'/index.html';
      $content = $page->blogify($url,'<span>&nbsp;</span>&nbsp;', $tag, 'by Craig Mayhew', $content);

      $page->setContent(nl2br($content));
      $page->setSideNav($this->getSideNav('../..'));
      $content = $page->build();
      $this->generateFile($this->destinationFolder.$url,$content);
    }

    //create category pages
    foreach($jsonBlogCats as $cat=>$posts){
      $content = '';
      $i=0;
      $tags = '<br /><br />';
      foreach($posts as $json){
        if(isset($json['tags'])){
          foreach($json['tags'] as $c){
            $tags .= '<a href="'.($this->generateForIPFS?'../../':'').'blog/tag/'.str_replace(' ','-',$c).'">'.$c.'</a> &nbsp; ';
          }
        }
        $text = explode(' ', substr(strip_tags($json['content']), 0, $textPreviewLength));
        array_pop($text);
        $content .=
        '<br /><br /><br /><h3><a href="'.($this->generateForIPFS?'../../':'/').'blog/'.$json['name'].'/">'.$json['title'].'</a></h3>'.
        '<br />'.implode(' ', $text).'…<br /><br />';
        $i++;
        if($i===6){break;}
      }
      $page = new page($cat,$this->css,$this->generateForIPFS,$this->generateForIPFS?'../../../':'/');
      $url = 'blog/cat/'.str_replace('/','-',$cat).'/index.html';
      $content = $page->blogify($url,'<span>&nbsp;</span>&nbsp;', $cat, 'by Craig Mayhew', $content.$tags);
      $page->setContent($content);
      $page->setSideNav($this->getSideNav('../..'));
      $content = $page->build();
      $this->generateFile($this->destinationFolder.$url,$content);
    }
  }
  private function generateFile($name,$content){
    $dir = dirname($name);
    if(!is_dir($dir)){
      mkdir($dir,0777,true);
    }
    file_put_contents($name,$content);
    echo 'Generated '.$name."\n";
  }
}

class page{
  private $content  = '';
  private $generateForIPFS = false;
  public  $navRight = '';
  private $title    = '';
  function __construct($title,$css='',$ipfs=false,$relativePath='/'){
    $this->title = $title;
    $this->generateForIPFS = $ipfs;

    $this->header =
      '<!DOCTYPE html>'.
      '<html lang="en">'.
        '<head>'.
            ($this->generateForIPFS?'':'<base href="/">').
            '<meta charset="utf-8">'.
            '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.
            '<meta name="viewport" content="width=device-width, initial-scale=1">'.
            '<title>'.$this->title.'</title>'.
            '<script src="//use.fontawesome.com/cefa967eb5.js"></script>'.
            '<script type="text/javascript" src="//platform-api.sharethis.com/js/sharethis.js#property=5a22f74f689cdf0012ad4b71&product=sticky-share-buttons"></script>'.
            '<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">'.
            '<link rel="stylesheet" href="'.$relativePath.'css/font-awesome.min.css">'.
            '<link rel="stylesheet" href="'.$relativePath.'css/style.css">'.
        '</head>'.
        '<body>'.
      '<div id="fb-root"></div>'.
          '<div class="wrapper overlay">'.
            '<div class="container">';
  }
  private function buildFooter(){
    $this->footer =
                 $this->navRight.
                 '<div class="row">'.
                   '<div class="col-12 text-center">'.
                     '<p class="copyText">&copy; 2005-'.date('Y').' Craig Mayhew'.($this->generateForIPFS?'<br>Delivered via IPFS':'').'</p>'.
                   '</div>'.
                 '</div>'.
               '</div>'.
            '</div>'.
          '</div>'.
          '<script src="//code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>'.
          '<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>'.
          '<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>'.
          //cookies!
          '<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.css" />'.
          '<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js"></script>'.
          '<script>'.
              'window.addEventListener("load", function(){'.
                  'window.cookieconsent.initialise({'.
                    '"palette": {'.
                      '"popup": {"background": "#000"},'.
                      '"button": {"background": "#36454F"}'.
                    '},'.
                    '"position": "top",'.
                    '"static": true'.
                  '})'.
              '});'.
          '</script>'.
        '</body>'.
      '</html>';
  }
  public function setContent($content){
    $this->content = $content;
  }
  public function setSideNav($nav){
    $this->navRight = $nav;
  }
  public function blogify($url, $date, $title, $author, $content, $social=false){
      $return =
      '<div class="col-sm-12 col-md-9 col-lg-9 col-xl-11">'.
          '<div class="postBox">'.
              '<div class="postHead">'.
                  '<div class="date">'.
                      $date.
                  '</div>'.
                  '<h2>'.$title.'</h2>'.
                  '<span>'.$author.'</span>'.
              '</div>'.
              '<div class="postBody">'.
                $content.
              '</div>';

              if ($social) {
                  $return .=
                  '<div class="sharePost"><div class="sharethis-inline-share-buttons"></div></div>';
              }
           $return .=
          '</div>'.
      '</div>';

      return $return;
  }
  public function pagify($date, $title, $author, $content){
    $return =
    '<div class="col-sm-12 col-md-9 col-lg-9 col-xl-11">'.
        '<div class="postBox">'.
            '<div class="postHead">'.
                '<div class="date">'.
                    $date.
                '</div>'.
                '<h2>'.$title.'</h2>'.
                '<span>'.$author.'</span>'.
            '</div>'.
            '<div class="postBody">'.
                $content.
            '</div>'.
        '</div>'.
    '</div>';

    return $return;
  }
  public function build()
  {
      $this->buildFooter();

      return
          $this->header .
          '<div class="box2">' .
          '<h1 class="page_title">' . $this->title . '</h1>' .
          $this->content . '<br><br>' .
          '</div>' .
          $this->footer;
  }

    private function buildRedirectPages(){
        $redirects = [
            'blog/2009/07/install-a-c-compiler-in-ubuntu-9-04-jaunty/' => 'blog/install-a-c-compiler-in-ubuntu-9-04-jaunty/',
            'blog/2010/03/converting-putty-ssh-keys-to-openssh/' => 'blog/converting-putty-ssh-keys-to-openssh/',
            'blog/tag/Linux/Ubuntu' => 'blog/tag/Ubuntu/',
            'blog/2009/08/stonehenge/stonehenge-panoramic-5/' => '',
            'blog/2009/08/stonehenge/stonehenge-panoramic-4/' => '',
            'blog/2009/08/stonehenge/stonehenge-panoramic-3/' => '',
            'blog/2009/08/stonehenge/stonehenge-panoramic-1/' => '',
            'blog/2009/08/stonehenge/stonehenge-panoramic-2/' => '',
            'blog/2010/08/notes-on-a-second-life/' => '',
            'blog/2012/03/intel-3-6ghz-core-i7-3820-with-32gbs-of-ram-and-7zip/' => '',
            'twister/' => '',
            'blog/2010/04/asus-aspire-one-network-manager-applet-disappeared/' => '',
            'blog/2009/11/slow' => '',
            'blog/2009/05/where-is-scanpstexe' => '',
            'blog/2010/01/ubuntu-cpugpu-temperature-sensor/' => '',
            'market/' => '',
            'blog/2009/11/setting-up-vpn-in-ubuntu-9-10-karmic-koala/' => '',
            'blog/2009/06/gnome-rdp-cant-read-gnome-rdpdb-after-ubuntu-904-upgrade/' => '',
            'blog/2011/02/foldinghome-growth-forecast/' => '',
            'blog/2009/06/microsoft-net-framework-assistant-10/' => '',
            'index.php' => '',
            'blog/2009/05/where-is-scanpstexe/' => '',
            'blog/2009/09/ubuntu' => '',
            'blog/2008/12/growing-a-crystal-tree/' => '',
            'blog/2009/09/ubuntu-error-sudo-etcsudoers-is-mode-0640-should-be-0440' => '',
            'blog/2010/12/mounting-a-windows-share-on-linux/' => '',
            'blog/2009/02/snow-day/' => '',
            'blog/2011/08/upgrading-ubuntu-to-10-10-blacklisted-blcr-dkms_0-8-2-13-error/' => '',
            'blog/2010/04/vpn-with-bethere-thomson-tg585v7/' => '',
            'blog/2009/11/installing-vmware-server-2-0-2-on-ubuntu-9-10-karmic-koala-64bit/' => '',
            'blog/2008/10/using-a-vigor-2900g-with-a-virgin-media-internet-connection/' => '',
            'blog/2011/01/php-email-validation-using-regex/' => '',
            'blog/2010/04/how-to-join-my-freelancer-server/' => '',
            'blog/tag/microsoft-freelancer/' => '',
            'blog/2009/11/installing-nightly-builds-of-firefox-on-windows-7/' => '',
            'blog/2009/09/ubuntu-error-sudo-etcsudoers-is-mode-0640-should-be-0440/' => '',
            'blog/2009/05/2560x1600-desktop-backgrounds/' => '',
            'blog/2011/04/opensim-0-7-1-rc1-and-the-shiny-new-media-on-a-prim/' => ''
        ];

        foreach($redirects as $from=>$to){
            $content =
                '<!DOCTYPE html>'.
                '<html lang="en">'.
                  '<head>'.
                    '<meta http-equiv="refresh" content="0; url=https://craig.mayhew.io'.$to.'">'.
                  '</head>'.
                  '<body>'.
                  '</body>'.
                '</html>';
            if(substr($from,-1) === '/'){
                $filename = $from.'index.html';
            }else{
                $filename = $from;
            }
            $this->generateFile($this->destinationFolder.$filename,$content);
        }
    }
}

// work out if we are in the tool directory or the root of the repo
if (file_exists(getcwd().'/composer.json')){
  $dir = '';
} else {
  $dir = '../';
}

//go build stuff!
$builder = new builder($dir);
$builder->build();
