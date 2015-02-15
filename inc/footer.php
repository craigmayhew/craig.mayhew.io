<?php
$ftr =
'  </div>
  <div id="side">
    <div class="sidebox" id="helpme">If you found this site helpful then please return the favour and help me out with the <a href="/helpme/">things I\'m stuck on</a> or improve upon one of my questions on <a href="https://www.quora.com/Craig-Mayhew">qoura</a></div>
    <div class="sidebox">
                <h4>Follow Me</h4>
                    <ul>
                        <li><a href="https://plus.google.com/114394371414443857717">Google+</a></li>
                        <li><a href="https://www.facebook.com/profile.php?id=682399345">Facebook</a></li>
                        <li><a href="https://twitter.com/craigmayhew">Twitter</a></li>
                        <li><a href="http://www.flickr.com/photos/39301866@N04/">Flickr</a></li>
                    </ul>
                <br/><h4>Projects</h4>
                    <ul>
                        <li><a href="http://www.adire.co.uk/">Adire</a></li>
                        <li><a href="http://www.bigprimes.net/">BigPrimes.net</a></li>
                    </ul>
                <br/><h4 class="heading">Do Goods</h4>
                    <ul>
                        <li><a href="http://fah-web.stanford.edu/cgi-bin/main.py?qtype=userpage&username=Craig_Mayhew">Folding@Home</a></li>
                        <li><a href="http://www.kickstarter.com/profile/craigmayhew">Kickstarter</a></li>
                        <li><a href="http://en.wikipedia.org/wiki/User:Craig_Mayhew">Wikipedia</a></li>
                        <li><a href="http://www.worldcommunitygrid.org/stat/viewMemberInfo.do?userName=Craig%20Mayhew">World Community Grid</a></li>
                    </ul>
                <br/><h4 class="heading">Latest Blog Posts</h4>
                    <ul>';

$collection = $this->m->craigmayhew->craigMayhewBlog;
$cursor = $collection->find(array('status'=>'publish'),array('name','title'));
$cursor->sort(array('date'=>-1));
//$cursor->batchSize(105)->limit(555555);
for($i=0;$i<5;$i++){
  $result = $cursor->getNext();
  $ftr .= '<li><a class="multiline" href="/blog/'.$result['name'].'">'.$result['title'].'</a></li>';
}

$ftr .=
		    '</ul>
    </div>
  </div>
  <div id="ftr">
    <div id="copyright"><em>&copy; Craig Mayhew 2003 - '.date('Y').'</em></div>
    <div id="dtimer"></div>
  </div>
 </div>
</body>
</html>';
