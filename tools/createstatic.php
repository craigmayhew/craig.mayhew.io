<?php
date_default_timezone_set("Europe/London");

class builder{
  /*CONFIG START*/
  private $destinationFolder = '../htdocs/';
  private $blogposts = '../blogposts/';
  private $dirPages  = '../pages/';
  private $cssPath   = '../s.css';
  private $css       = '';
  private $justCopy  = array('favicon.ico','files','imgs','js','robots.txt','uploads');
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
    $this->css = file_get_contents($this->cssPath);
    echo "Copying Static Files\n";
    $this->copyStaticFiles();
    echo "Building Blog\n";
    $this->buildBlog();
    echo "Building Pages\n";
    $this->buildPages($this->dirPages);
  }

  private function copyStaticFiles(){
    //copy static files
    foreach($this->justCopy as $fileOrFolder){
      if(is_file('../'.$fileOrFolder)){
        echo 'copied '.$this->destinationFolder.$fileOrFolder."\n";
        copy('../'.$fileOrFolder,$this->destinationFolder.$fileOrFolder);
      }else{
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
   
    //now work out tags and categories 
    if(count($jsonBlogPosts)>0){
      $i=0;
      $this->sideNav = '';
      $frontPage = '';
      foreach($jsonBlogPosts as $json){
        $i++;
        $frontPage .= 
        '<h2>'.$json['title'].'</h2>'.
        'by Craig Mayhew on '.date('D dS M Y',strtotime($json['date'])).' under '.implode(', ',$json['categories']).
        '<br /><br /><br />'.$json['content'].'<br /><br /><br />';
        $this->sideNav .= '<li><a class="multiline" href="/blog/'.$json['name'].'">'.$json['title'].'</a></li>';
        if($i===6){break;}
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
        $comments = '<br /><br /> '.count($json['comments']).' Comments';
        foreach($json['comments'] as $c){
          $comments .= 
          '<div class="comment">'.
            '<img align="left" class="gravatar" height="80" width="80" src="//www.gravatar.com/avatar/'.md5(trim($c['authorEmail'])).'">'.
            '<div class="name">'.$c['author'].'</div>'.
            '<div class="time">'.$c['timestampGMT'].'</div>'.
            $c['comment'].
          '</div>';
        }
        $content =
          '<h2>'.$json['title'].'</h2>'.
          'by Craig Mayhew on '.date('D dS M Y',strtotime($json['date'])).' under '.implode(', ',$json['categories']).
          '<br /><br /><br />'.$json['content'].'<br /><br /><br />';
        $page->setContent($content.$tags.nl2br($comments));
        $page->setSideNav($this->sideNav);
        $content = $page->build();
        $this->generateFile($this->destinationFolder.'blog/'.$json['name'].'/index.html',$content);
      }
    }
    unset($handle,$entry,$json);

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
      $page = new page($cat,$this->css);
      $page->setContent(nl2br($content));
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
  private $navTop   = '';
  public  $navRight = '';
  private $title    = '';
  function __construct($title,$css=''){
    $this->title = $title;
    $this->navTop =
    '<div id="catnav">'
      .'<ul id="nav">'
        .'<li class="navli"><a href="/blog/cat/Astrothoughts/" title="View all posts filed under Astrothoughts">Astrothoughts</a></li>'
        .'<li class="navli"><a href="/blog/cat/Code/" title="View all posts filed under Code">Code</a></li>'
        .'<li class="navli"><a href="/blog/cat/Events/" title="View all posts filed under Events">Events</a></li>'
        .'<li class="navli"><a href="/blog/cat/Friends-Family/" title="View all posts filed under Friends/Family">Friends/Family</a></li>'
        .'<li class="navli"><a href="/blog/cat/General/" title="View all posts filed under General">General</a></li>'
        .'<li class="navli"><a href="/blog/cat/General-Techie/" title="View all posts filed under General/Techie">General/Techie</a></li>'
        .'<li class="navli"><a href="/blog/cat/Linux-Ubuntu/" title="View all posts filed under Linux/Ubuntu">Linux/Ubuntu</a></li>'
        .'<li class="navli"><a href="/blog/cat/News/" title="View all posts filed under News">News</a></li>'
        .'<li class="navli"><a href="/blog/cat/Reviews-Experience/" title="View all posts filed under Reviews/Experience">Reviews/Experience</a></li>'
      .'</ul>'
    .'</div>';

    $this->header =
    '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'.
    '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
      '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'.
        '<head>'.
          '<title>'.$this->title.'</title>'.
          '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.
          '<meta name="author" content="Craig Mayhew" />'.
          '<meta name="keywords" content="Craig Mayhew" />'.
          '<meta name="robots" content="follow, all" />'.
          '<script language="javascript" type="text/javascript" src="/js/js.js"></script>'.
          ($css?'<style type="text/css" media="screen">'.$css.'</style>':'').
        '</head>'.
        '<body>'.
          '<div id="content">'.
            '<div id="hdr">'.
              '<div id="hdrlinks">'.
                '<ul>'.
                  '<li><a class="whitelink hdrlnk" href="/">Home</a></li>'.
                  '<li><a class="whitelink hdrlnk" href="/blog/">Blog</a></li>'.
                  '<li><a class="whitelink hdrlnk" href="/games/">Games</a></li>'.
                  '<li><a class="whitelink hdrlnk" href="/tools/">Tools</a></li>'.
                '</ul>'.
              '</div>'.
              '<div id="logo">'.
                '<h1>'.$this->title.'</h1>'.
              '</div>'.
              $this->navTop.
            '</div>'.
            '<div id="main">';
  }
  private function buildFooter(){
    $this->footer = 
              '<br /><br /><br />'.
            '</div>'.
            '<div id="side">'.
              '<div class="sidebox" id="helpme">'.
                'If you found this site helpful then please return the favour and help me out with the <a href="/helpme/">things I\'m stuck on</a> or improve upon one of my questions on <a href="https://www.quora.com/Craig-Mayhew">qoura</a>'.
              '</div>'.
              '<div class="sidebox">'.
                '<h4>Follow Me</h4>'.
                  '<ul>'.
                    '<li><a href="https://plus.google.com/114394371414443857717">Google+</a></li>'.
                    '<li><a href="https://www.facebook.com/profile.php?id=682399345">Facebook</a></li>'.
                    '<li><a href="https://twitter.com/craigmayhew">Twitter</a></li>'.
                    '<li><a href="http://www.flickr.com/photos/39301866@N04/">Flickr</a></li>'.
                  '</ul>'.
                  '<br/><h4>Projects</h4>'.
                  '<ul>'.
                    '<li><a href="http://www.adire.co.uk/">Adire</a></li>'.
                    '<li><a href="http://www.bigprimes.net/">BigPrimes.net</a></li>'.
                  '</ul>'.
                  '<br/><h4 class="heading">Do Goods</h4>'.
                  '<ul>'.
                    '<li><a href="http://fah-web.stanford.edu/cgi-bin/main.py?qtype=userpage&username=Craig_Mayhew">Folding@Home</a></li>'.
                    '<li><a href="http://www.kickstarter.com/profile/craigmayhew">Kickstarter</a></li>'.
                    '<li><a href="http://en.wikipedia.org/wiki/User:Craig_Mayhew">Wikipedia</a></li>'.
                    '<li><a href="http://www.worldcommunitygrid.org/stat/viewMemberInfo.do?userName=Craig%20Mayhew">World Community Grid</a></li>'.
                  '</ul>'.
                  '<br/><h4 class="heading">Latest Blog Posts</h4>'.
                '<ul>'.
                  $this->navRight.
                '</ul>'.
              '</div>'.
            '</div>'.
            '<div id="ftr">'.
              '<div id="copyright"><em>&copy; Craig Mayhew 2003 - '.date('Y').'</em></div>'.
              '<div id="dtimer">'.time().'</div>'.
            '</div>'.
          '</div>'.
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
    return $this->header.$this->content.$this->footer;
  }
}

//go build stuff!
$builder = new builder();
$builder->build();
