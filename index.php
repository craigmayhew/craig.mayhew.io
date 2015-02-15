<?php

class fw{
  private $buffer;
  private $cacheHash;
  private $config;
  private $page;
  private $requestVars;
  private $uri;
  public  $db;
  private $m;
  private $store;

  function __construct($uri,$requestVars=array()){
    $this->uri = trim(urldecode($uri),'/');
    if($this->uri == ''){
       $this->page = 'index';
    }elseif(substr($this->uri,0,4)==='blog'){
       $this->page = 'blog';
    }else{
       $this->page = urldecode($this->uri);
    }
    $this->requestVars = $requestVars; 
    $this->config = array(
                      'cacheLocation'=>'../cache/',
                      'cacheUriBlacklist'=>array('/contact/'=>'638fc45cbd75ad25856c129d74b4322d5e561aaf'),
                      'cacheAgeOut'=>604800
    );
  }
  private function mongoConnect(){
    try{
      $this->m = new MongoClient();
      $this->db = $this->m->craigmayhew;
      //$c = new MongoCollection($this->db, 'foo');
      //$c->ensureIndex(array('name'=>1,'status'=>1));
      //$c->ensureIndex(array('status'=>1,'date'=>-1));
      //$c->ensureIndex(array('tags'=>1));
    }
    catch(MongoConnectionException $e){
      //mongodb is down
      $this->m = false;
    }
  }

  public function go(){
    if($this->cacheRead()){
      $this->output();
    }else{
      try{ 
        $this->m = new MongoClient();
        $db = $this->m->selectDB("craigmayhew");
      }
      catch(MongoConnectionException $e){
        //mongodb is down 
        $this->m = false;
      }
      $html = $this->renderHTMLPage();
      $this->buffer = $html;
      $this->output();
      //TOT UNNOTE THIS
      //$this->cacheWrite($html);
    }
  }
  private function renderHTMLPage(){
    $css = file_get_contents('inc/s.css'); 
    $css = str_replace(array('  ',"\r","\n"),'',$css);
    $css = str_replace(': ',':',$css);
    $css = str_replace(', ',',',$css);
    $css = str_replace(' {','{',$css);
    $hdr = $page = $ftr = '';

    @include_once('pages/'.$this->page.'.php');
    require_once('inc/header.php');
    require_once('inc/footer.php');
    return $hdr.$page.$ftr;
  }
  private function cacheRead(){
    $this->cacheHash = sha1($this->uri); 
    $this->cacheFile = $this->config['cacheLocation'].$this->cacheHash;
    if(file_exists($this->cacheFile) && filectime($this->cacheFile) > time()-$this->config['cacheAgeOut']){
      $this->buffer = file_get_contents($this->cacheFile);
      return true;
    }
    return false;
  } 
  private function cacheWrite($data){
    file_put_contents($this->cacheFile,$data); 
    file_put_contents($this->cacheFile.'_gz',"\x1f\x8b\x08\x00\x00\x00\x00\x00".gzcompress($data,9)); 
  } 
  private function output(){
    echo $this->buffer;
    $this->buffer = '';
  }
  private function cleanse($dirty){
    return htmlentities($dirty, ENT_QUOTES, 'UTF-8');
  }
}

$fw = new fw($_SERVER['REQUEST_URI'],$_REQUEST);
$fw->go();

?>
