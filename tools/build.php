<?php
date_default_timezone_set("Europe/London");

class builder{
  /*CONFIG START*/
  private $destinationFolder = '../htdocs/';
  private $blogposts = '../blogposts/';
  private $dirPages  = '../pages/';
  private $cssPath   = '../css/style.css';
  private $css       = '';
  private $justCopy  = array('favicon.ico','files','imgs','css','js','robots.txt','uploads');
  private $sideNav   = '';
  /*CONFIG END*/

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
    if(isset($GLOBALS['argv']) && is_array($GLOBALS['argv']) && count($GLOBALS['argv'])>1){
      foreach($GLOBALS['argv'] as $v){
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
            $build['blog'] = 'blog';
            $build['pages'] = 'pages';
            break; 
        }
      }
    } else {
      $build = ['static'=>1,'blog'=>1,'pages'=>1];
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
    if(isset($build['pages'])){
      echo "Building Pages\n";
      $this->buildPages($this->dirPages);
    }
  }

  private function copyStaticFiles(){
    mkdir($this->destinationFolder);
    //copy static files
    foreach($this->justCopy as $fileOrFolder){
      //if we are copying a file
      if(is_file('../'.$fileOrFolder)){
        echo 'copied '.$this->destinationFolder.$fileOrFolder."\n";
        copy('../'.$fileOrFolder,$this->destinationFolder.$fileOrFolder);
      }else{ //else we are copying a folder
        //check if the folder needs creating in the destination
        if(!is_dir($this->destinationFolder.$fileOrFolder)){
          mkdir($this->destinationFolder.$fileOrFolder);
        }
        //copy the contents over
        $this->recurse_copy('../'.$fileOrFolder,$this->destinationFolder.$fileOrFolder);
      }
    }
  }
  
  //build pages
  private function buildPages($dir){
    if($handle = opendir($dir)){
      while(false !== ($entry = readdir($handle))){
        if(is_dir($dir.$entry) && $entry != '.' && $entry != '..'){
          $this->buildPages($dir.$entry.'/');
        }
        if(substr($entry,-5) != '.json'){continue;}
        $json = json_decode(file_get_contents($dir.$entry),true);
        $page = new page($json['title'],$this->css);
        $page->setContent(file_get_contents(substr($dir.$entry,0,-5).'.html'));
        $page->setSideNav($this->sideNav);
        $content = $page->build();
        $this->generateFile($this->destinationFolder.$json['url'].'/index.html',$content);
      }
    }
  }

  //build blog section
  private function buildBlog(){
    $jsonBlogPosts  = array();
    $jsonBlogCats   = array();
    $jsonBlogTags   = array();
    if($handle = opendir($this->blogposts)){
      while(false !== ($entry = readdir($handle))){
        if($entry=='.' || $entry=='..'){continue;}
        $json = json_decode(file_get_contents($this->blogposts.$entry),true);
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

    $this->sideNav =
    '<div class="sidebar">'.
        '<aside class="widget">'.
            '<h3>Recent Posts</h3>'.
            '<ul>'.
                '<li><a href="/blog/reprap-4-year-project/">3D Printer</a></li>'.
                '<li><a href="/blog/usb-secure-eraser/">USB Eraser</a></li>'.
                '<li><a href="/blog/dns-the-original-cdn/">DNS as a CDN</a></li>'.
            '</ul>'.
        '</aside>'.
        '<aside class="widget">'.
            '<h3>Category</h3>'.
            '<ul>'.
                '<li><a href="/blog/cat/Astrothoughts/">Astrothoughts</a></li>'.
                '<li><a href="/blog/cat/Code/">Code</a></li>'.
                '<li><a href="/blog/cat/General/">General</a></li>'.
                '<li><a href="/blog/cat/Reviews-Experience/">Reviews/Experience</a></li>'.
                '<li><a href="/blog/cat/General-Techie/">Techie</a></li>'.
            '</ul>'.
        '</aside>'.
        '<a class="backHome" href="/">Back to home</a>'.
    '</div>';
    //now work out tags and categories 
    if(count($jsonBlogPosts)>0){
      $i=0;

      //build front page
      $frontPage = '';
      foreach($jsonBlogPosts as $json){
        $i++;
        $frontPage .= 
        '<h2>'.$json['title'].'</h2>'.
        'by Craig Mayhew on '.date('D dS M Y',strtotime($json['date'])).' under '.implode(', ',$json['categories']).
        '<br /><br /><br />'.$json['content'].'<br /><br /><br />';
        if($i===4){break;}
      }
       
      //front page
      $page = new page('Craig Mayhew\'s Blog',$this->css);
      $page->setContent(nl2br($frontPage));
      $page->setSideNav($this->sideNav);
      $content = $page->build();
      $this->generateFile($this->destinationFolder.'blog/index.html',$content);
      
      foreach($jsonBlogPosts as $json){
        //tags
        if(isset($json['tags']) && is_array($json['tags'])){
          foreach($json['tags'] as $tag){
            if(isset($jsonBlogTags[$tag])){
              $jsonBlogTags[$tag][] = $json['name'];
            }else{
              $jsonBlogTags[$tag] = array($json['name']);
            }
          }
        }
        //category
        if(isset($json['categories'])){
          foreach($json['categories'] as $cat){
            if(isset($jsonBlogCats[$cat])){
              $jsonBlogCats[$cat][] = $json['name'];
            }else{
              $jsonBlogCats[$cat] = array($json['name']);
            }
          }
        }
        //create blog post file
        $page = new page($json['title'],$this->css);
        $tags = '<br /><br />';
        foreach($json['tags'] as $c){
          $tags .= '<a href="/blog/tag/'.$c.'">'.$c.'</a> &nbsp; ';
        }

        $content =
        '<div class="col-sm-12 col-md-9 col-lg-9 col-xl-11">'.
            '<div class="postBox">'.
                '<div class="postHead">'.
                    '<div class="date">'.
                        '<span>'.date('d',strtotime($json['date'])).'</span>'.date('M',strtotime($json['date'])).
                    '</div>'.
                    '<h2>'.$json['title'].'</h2>'.
                    '<span>by Craig Mayhew on '.date('D jS M Y',strtotime($json['date'])).' under '.implode(', ',$json['categories']).'</span>'.
                '</div>'.
                '<div class="postBody">'.
                    nl2br($json['content']).
                    $tags.
                '</div>'.
                '<div class="sharePost">'.
                    '<ul>'.
                        '<li>'.
                            '<a href="#"><i class="fa fa-heart-o"></i>Like</a>'.
                        '</li>'.
                        '<li>'.
                            '<a href="#"><i class="fa fa-clock-o"></i>Later</a>'.
                        '</li>'.
                        '<li>'.
                            '<a href="#"><i class="fa fa-share-square-o"></i>Share</a>'.
                        '</li>'.
                    '</ul>'.
                '</div>'.
            '</div>'.
        '</div>';
        $page->setContent($content);
        $page->setSideNav($this->sideNav);
        $content = $page->build();
        $this->generateFile($this->destinationFolder.'blog/'.$json['name'].'/index.html',$content);
      }
    }
    unset($handle,$entry,$json);

    //create archive page
    $content = '';
    foreach($jsonBlogPosts as $json){ 
        //add blog post to the archive array
        $content .= substr($json['date'],0,10).' <a href="/blog/'.$json['name'].'/index.html'.'">'.$json['title'].'</a><br>';
    }
    $page = new page('Blog Archive',$this->css);
    $page->setContent($content);
    $page->setSideNav($this->sideNav);
    $content = $page->build();
    $this->generateFile($this->destinationFolder.'blog/archive/index.html',$content);

    //create tag pages
    foreach($jsonBlogTags as $tag=>$posts){
      $content = '';
      $i=0;
      foreach($posts as $postname){
        $tags = '<br /><br />';
        if(isset($jsonBlogPosts[$postname]['tags'])){
          foreach($jsonBlogPosts[$postname]['tags'] as $c){
            $tags .= '<a href="/blog/tag/'.$c.'">'.$c.'</a> &nbsp; ';
          }
        }
        
        $content .= 
            '<h2>'.$jsonBlogPosts[$postname]['title'].'</h2>'.
            'by Craig Mayhew on '.date('D dS M Y',strtotime($jsonBlogPosts[$postname]['date'])).' under '.implode(', ',$jsonBlogPosts[$postname]['categories']).
            '<br /><br /><br />'.$jsonBlogPosts[$postname]['content'].$tags.'<br /><br /><br />';
        $i++;
        if($i===6){break;}
      }
      $page = new page($tag,$this->css);
      $page->setContent(nl2br($content));
      $page->setSideNav($this->sideNav);
      $content = $page->build();
      $this->generateFile($this->destinationFolder.'blog/tag/'.str_replace('/','-',$tag).'/index.html',$content.$tags);
    }

    //create category pages
    foreach($jsonBlogCats as $cat=>$posts){
      $content = '';
      $i=0;
      foreach($posts as $postname){
        $content .=
            '<h2>'.$jsonBlogPosts[$postname]['title'].'</h2>'.
            'by Craig Mayhew on '.date('D dS M Y',strtotime($jsonBlogPosts[$postname]['date'])).' under '.implode(', ',$jsonBlogPosts[$postname]['categories']).
            '<br /><br /><br />'.$jsonBlogPosts[$postname]['content'].'<br /><br /><br />';
        $i++;
        if($i===6){break;}
      }
      $page = new page($cat,$this->css);
        $content =
        '<div class="col-sm-12 col-md-9 col-lg-9 col-xl-11">'.
            '<div class="postBox">'.
                '<div class="postHead">'.
                    '<div class="date">'.
                        '<span>&nbsp;</span>&nbsp;'.
                    '</div>'.
                    '<h2>'.$cat.'</h2>'.
                    '<span>by Craig Mayhew</span>'.
                '</div>'.
                '<div class="postBody">'.
                    nl2br($content).
                '</div>'.
            '</div>'.
        '</div>';
      $page->setContent($content);
      $page->setSideNav($this->sideNav);
      $content = $page->build();
      $this->generateFile($this->destinationFolder.'blog/cat/'.str_replace('/','-',$cat).'/index.html',$content);
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
  public  $navRight = '';
  private $title    = '';
  function __construct($title,$css=''){
    $this->title = $title;

    $this->header =
      '<!DOCTYPE html>'.
      '<html lang="en">'.
        '<head>'.
            '<meta charset="utf-8">'.
            '<meta http-equiv="X-UA-Compatible" content="IE=edge">'.
            '<meta name="viewport" content="width=device-width, initial-scale=1">'.
            '<title>'.$this->title.'</title>'.
            '<script src="https://use.fontawesome.com/cefa967eb5.js"></script>'.
            '<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">'.
            '<link rel="stylesheet" href="/css/font-awesome.min.css">'.
            '<link rel="stylesheet" href="/css/style.css">'.
        '</head>'.
        '<body>'.
          '<div class="wrapper overlay">'.
            '<div class="container">';
  }
  private function buildFooter(){
    $this->footer =
                  $this->navRight.
                 '<div class="row">'.
                   '<div class="col-12 text-center">'.
                     '<p class="copyText">&copy; 2017 Craig Mayhew</p>'.
                   '</div>'.
                 '</div>'.
               '</div>'.
            '</div>'.
          '</div>'.
          '<script src="//code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>'.
          '<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js" integrity="sha384-vFJXuSJphROIrBnz7yo7oB41mKfc8JzQZiCq4NCceLEaO4IHwicKwpJf9c9IpFgh" crossorigin="anonymous"></script>'.
          '<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/js/bootstrap.min.js" integrity="sha384-alpBpkh1PFOepccYVYDB4do5UnbKysX5WZXm3XxPqe5iKTfUKjNkCk9SaVuEZflJ" crossorigin="anonymous"></script>'.
        '</body>'.
      '</html>';
  }
  public function setContent($content){
    $this->content = $content;
  }
  public function setSideNav($nav){
    $this->navRight = $nav;
  }
  public function build(){
    $this->buildFooter();

    return 
    $this->header.
    '<div class="box2">'.
      '<h1 class="page_title"><a href="/">'.$this->title.'</a></h1>'.
      $this->content.'<br><br>'.
    '</div>'.
    $this->footer;
  }
}

//go build stuff!
$builder = new builder();
$builder->build();
