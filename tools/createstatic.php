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
        $this->sideNav .= '<li><a href="/blog/'.$json['name'].'">'.$json['title'].'</a></li>';
        if($i===5){break;}
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
          'by Craig Mayhew on '.date('D dS M Y',strtotime($json['date'])).' under '.implode(', ',$json['categories']).
          '<br /><br /><br />'.$json['content'].'<br /><br /><br />';
        $page->setContent(nl2br($content).$tags.nl2br($comments));
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
    '<a href="/blog/cat/Astrothoughts/" title="View all posts filed under Astrothoughts">Astrothoughts</a> '
    .'<a href="/blog/cat/Code/" title="View all posts filed under Code">Code</a> '
    .'<a href="/blog/cat/Events/" title="View all posts filed under Events">Events</a> '
    .'<a href="/blog/cat/Friends-Family/" title="View all posts filed under Friends/Family">Friends/Family</a> '
    .'<a href="/blog/cat/General/" title="View all posts filed under General">General</a> '
    .'<a href="/blog/cat/General-Techie/" title="View all posts filed under General/Techie">General/Techie</a> '
    .'<a href="/blog/cat/Linux-Ubuntu/" title="View all posts filed under Linux/Ubuntu">Linux/Ubuntu</a> '
    .'<a href="/blog/cat/News/" title="View all posts filed under News">News</a> '
    .'<a href="/blog/cat/Reviews-Experience/" title="View all posts filed under Reviews/Experience">Reviews/Experience</a>';

    $this->header =
      '<!DOCTYPE html>'.
      '<html lang="en">'.
        '<head>'.
          '<title>'.$this->title.'</title>'.
          '<meta charset="UTF-8">'.
          '<meta name="author" content="Craig Mayhew">'.
          '<meta name="keywords" content="Craig Mayhew">'.
          '<meta name="robots" content="follow, all">'.
          '<link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet" type="text/css">'.
          '<script language="javascript" type="text/javascript" src="/js/js.js"></script>'.
          ($css?'<style type="text/css" media="screen">'.$css.'</style>':'').
          '<script src="https://cdnjs.cloudflare.com/ajax/libs/masonry/3.3.2/masonry.pkgd.min.js"></script>'.
        '</head>'.
        '<body>'.
         '<div class="grid js-masonry" data-masonry-options=\'{ "itemSelector": ".grid-item", "columWidth": "box5" }\'>'.
          '<div class="grid-item box5">'.
              '<a href="https://plus.google.com/114394371414443857717"><img height="16" width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACxElEQVQ4jW2TTWhcVRTHf+e+13kzmc/QopLQLgRjU6u0ItXUD2yLqNSVIkgFFyqtyxrERYsboWZhoCgoonEjuJAsFC0VWlwoqGA2ASnWxqgVk2bIJJM3M5l5X/ceF8+Goj3bw+/cyzm/vywenVAFUEWMhzqLpgkiBoyA5j18HxHhv+UDiCoYg4v6SCGgMLoLF0dokiC+j/g+NtxAnfvfEB9V1Bg0GhCM7WH78ZMkf/5GcfweCrtux3ZDet9eZG3mXcT3t0BVRUQwiECWYYbKjEy9R7JwmeaZUzSnTqNpQraynMMiCKDWbsFAPkCjAaV7H8Cr1Yl+vYRXb5D8vkDn6y8o3b2fwuhO1FowBlOuIMZs/cQAqBjcoA9AMDaO7XVxSYzthihgO2G+2ELA6NmPkSDARRGqio9zmFKJwfwcmz99z/CzL5Bc/YPB/ByVg4dovT+NXW/h77gFr9bAVGp49WFQ0DhCFo9O5Fe0FikWqTx0hNL+A5QfPET45Wc0z5yiuHsvI29/gBmqYKo1XLeD7YYsvXYc//pCxPOw7XU2Zj/BRX2qh59gaN8BhiYeJlm4zNLky3i1OiPTH7H8+gnsRhviCCMiqLW4LKPx3IvsnJml8shjdC6co3DHbkanPyQY20PWvIbthLheBxu2cy9U8RXQLOXW01MEd97FyhsnSa/9jev16F48x21vnqXxzPOs/PIzGscsvfoSGseYYjG/gmYpXq1B9dHH6Z7/nM0fv8sb1Rqdr2bpfXMeUx/OFVeH2+yhzt1gojG4NCFdW6X+9DHS1SbRpXlQZfsrk5T23cfqO28h3r8Weh6oonmCkMWnDqrGMcH4XnacmMw9CDdAIV3+i/anM/TnfsBUqnDDy9dLrjx5vyKCiwaIvw1TriK+B4Btr6NZiilXbgoD+Fda64CC8cD10dZaHmHIw2MMbLZuCgP8A634UW1oE1wuAAAAAElFTkSuQmCC"></a>'.
          '</div>'.
          '<div class="grid-item box5">'.
                '<a href="https://www.facebook.com/profile.php?id=682399345"><img height="16" width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAA8ElEQVQ4jc2PvWrCYBSGH+1XtRFChE5KBy+ghYKI0ngJ4gW0S4cMbtmLKJReRpdCL6A6KDiJiqCLi466OFhwkhoKJtpBECLiZ3Tp2d4Dz/sDZ54PIJktNYXPr3sB7fWq1a2UMgJA1WK6uAx5SraXvzqAADgEq+Eg5nOKxG2UhbXke/aD+VbbMkKWlH9MkL6/AUBTL9BUd5jUIHkXA8AsftLrjzatruPHGyhXAQAGY8sFSg2q708uXf8wAJhM5xgv5e3fL2uwe43O8LgGD7lXANpfBZcOKBohJSI32N27bz+cMOGfGjiO0/JMrmifGw7AHw9PMw4bXIoqAAAAAElFTkSuQmCC"></a>'.
          '</div>'.
          '<div class="grid-item box4 hidden">'.
            '<div>'.
            '</div>'.
          '</div>';
  }
  private function buildFooter(){
    $this->footer = 
          '<div class="grid-item box5">'.
                '<a href="https://twitter.com/craigmayhew"><img height="16" width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB10lEQVQ4jcWRO2hUQRSGv3Nm7twrwQiJsCAugthIUCwtxFILMQYkiy+ixEelnY21hZ2YUgwJsRB0fZDCKiCIFnYagxYqq0SLWKikMJG9d+ZYaNAkC2kE/26G+b6fOQf+dwTg0Ni7+uTwto+dHpwaf1/Ma7yMuGHAS7KmS/FSqbpXhBntv9GqqdPnA+NvdnUSzCtjmhUXNRQ9LqzrlpCfjiH/LE4biutSl2uP+tCrWfF0YKJ1HjNZgvtvtWqaZUfE56gPiM+QLEezQpwLe5JUpQzeeRUs9c4iUrOqJMVyhhRH0TSl5jMpuqbF+aXf/ooZVVnte3CsNqXV4ob1iF0V9W3JcjQUOzQUI6LFa7IwjehyGDBLeGvPA3hfmqXAFVFU1CGimDPEEiCIaofJmC2Q3gJo80z9KyKjf/YiiCiiHlG3qv234MXD41u+ASiA6pcLltI1sB+dNrGiHKp4femoAM1GX9us2o1RrMUnsw/i481lAgCr4iAWn63RHiW2h5qN+uIqwf0Tmz+FttufiLfBOtExpXju7tFNT/6+lcMTrZ1JOIhJn2b+gLi8W9yK4Zm1MDvbbGx8tFKr905ufQnusWThO+rnUF0AKrBZjElSOYTNbe8E/5P8BGddrrqInXD7AAAAAElFTkSuQmCC"></a>'.
          '</div>'.
          '<div class="grid-item box5 hidden">'.
            '<div>'.
            '</div>'.
          '</div>'.
          '<div class="grid-item box4 hidden">'.
            '<div>'.
            '</div>'.
          '</div>'.
          '<div class="grid-item box4 hidden">'.
            '<div>'.
            '</div>'.
          '</div>'.
           '<div class="grid-item box4">'.
             '<div><a href="/">Home</a></div>'.
           '</div>'.
           '<div class="grid-item box4">'.
             '<div><a href="/blog/">Blog</a></div>'.
           '</div>'.
           '<div class="grid-item box4">'.
             '<div><a href="/games/">Games</a></div>'.
           '</div>'.
           '<div class="grid-item box4">'.
             '<div><a href="/tools/">Tools</a></div>'.
           '</div>'.
          '<div class="grid-item box" id="helpme">'.
            '<br><br>If you found this site helpful then please return the favour and help me out with the <a href="/helpme/">things I\'m stuck on</a> or improve upon one of my questions over at <a href="https://www.quora.com/Craig-Mayhew">qoura</a>'.
          '</div>'.

          '<div class="grid-item box">'.
            '<div>'.
              '<h4>Projects</h4>'.
              '<ul>'.
                '<li><a href="http://www.adire.co.uk/">Adire</a></li>'.
                '<li><a href="http://www.bigprimes.net/">BigPrimes.net</a></li>'.
              '</ul>'.
            '</div>'.
           '</div>'.
           '<div class="grid-item box">'.
             '<div>'.
              '<h4>Do Goods</h4>'.
              '<ul>'.
                '<li><a href="http://fah-web.stanford.edu/cgi-bin/main.py?qtype=userpage&username=Craig_Mayhew">Folding@Home</a></li>'.
                '<li><a href="https://github.com/craigmayhew">GitHub</a></li>'.
                '<li><a href="https://en.wikipedia.org/wiki/User:Craig_Mayhew">Wikipedia</a></li>'.
                '<li><a href="http://www.worldcommunitygrid.org/stat/viewMemberInfo.do?userName=Craig%20Mayhew">World Community Grid</a></li>'.
              '</ul>'.
            '</div>'.
           '</div>'.
           '<div class="grid-item box">'.
             '<div>'.
               '<h4>Latest Blog Posts</h4>'.
               '<ul>'.
                 $this->navRight.
               '</ul>'.
             '</div>'.
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

    return 
    $this->header.
    '<div class="grid-item box2">'.
      '<h1>'.$this->title.'</h1>'.
      $this->content.'<br><br>'.
      '<div id="copyright"><em>&copy; Craig Mayhew 2003 - '.date('Y').'</em></div>'.
      '<div id="dtimer">'.time().'</div>'.
    '</div>'.
    $this->footer;
  }
}

//go build stuff!
$builder = new builder();
$builder->build();
