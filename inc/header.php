<?php
if(!isset($this->store['cm_title'])){
  $this->store['cm_title'] = 'Craig Mayhew';
}

$cats = 
'<div id="catnav">
  <ul id="nav">
    <li class="navli"><a href="/blog/cat/Astrothoughts/" title="View all posts filed under Astrothoughts">Astrothoughts</a>
    </li>
    <li class="navli"><a href="/blog/cat/Code/" title="View all posts filed under Code">Code</a>
    </li>
    <li class="navli"><a href="/blog/cat/Events/" title="View all posts filed under Events">Events</a>
    </li>
    <li class="navli"><a href="/blog/cat/Friends-Family/" title="View all posts filed under Friends/Family">Friends/Family</a>
    </li>
    <li class="navli"><a href="/blog/cat/General/" title="View all posts filed under General">General</a>
    </li>
    <li class="navli"><a href="/blog/cat/General-Techie/" title="View all posts filed under General/Techie">General/Techie</a>
    </li>
    <li class="navli"><a href="/blog/cat/Linux-Ubuntu/" title="View all posts filed under Linux/Ubuntu">Linux/Ubuntu</a>
    </li>
    <li class="navli"><a href="/blog/cat/News/" title="View all posts filed under News">News</a>
    </li>
    <li class="navli"><a href="/blog/cat/Reviews-Experience/" title="View all posts filed under Reviews/Experience">Reviews/Experience</a>
    </li>
  </ul>
</div>';

$hdr =
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>'.$this->store['cm_title'].'</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="author" content="Craig Mayhew" />
<meta name="keywords" content="Craig Mayhew" />
<meta name="robots" content="follow, all" />
<script language="javascript" type="text/javascript" src="/j/"></script>
<style type="text/css" media="screen">'.$css.'</style>
</head>

<body>
 <div id="content">
  <div id="hdr">
        <div id="hdrlinks">
        <ul>
                <li><a class="whitelink hdrlnk" href="/">Home</a></li>
                <li><a class="whitelink hdrlnk" href="/blog/">Blog</a></li>
                <li><a class="whitelink hdrlnk" href="/games/">Games</a></li>
                <li><a class="whitelink hdrlnk" href="/tools/">Tools</a></li>
        </ul>
        </div>'.
        '<div id="logo">'.
          '<h1>'.$this->store['cm_title'].'</h1>'.
        '</div>'. 
	$cats.
  '</div>
  <div id="main">';
