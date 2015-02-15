<?php

$m  = new MongoClient();
$db = $m->craigmayhew;

$collection = $m->craigmayhew->craigMayhewBlog;
$cursor = $collection->find(array('status'=>'publish'),array('author','content','title','date','name','type','tags','categories','comments','status'));
$cursor->sort(array('date'=>-1));
$cursor->limit(99999);

$i=0;
while($i<300){
  if($cursor){$result = $cursor->getNext();}
  if(!$result){break;}
  if($result['name'] == ''){
    $result['name'] = $i;
  }
  file_put_contents('../blogposts/'.$result['name'].'.json',json_encode($result));
  $i++;
}
