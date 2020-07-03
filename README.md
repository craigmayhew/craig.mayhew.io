# craig.mayhew.io

[![Build Status](https://travis-ci.org/craigmayhew/craig.mayhew.io.svg?branch=master)](https://travis-ci.org/craigmayhew/craig.mayhew.io)

Craig Mayhew's Personal website

Blog posts are stored in blogposts/ as json files. One file per post.
Pages are stored in pages/ as json files. One file per page.

PHP is used to generate static html in the htdocs directory, sync that to an S3 bucket and then wipe cache on cloudfront.

## deploy

Deployment is handled by TravisCI. See [.travis.yml](https://github.com/craigmayhew/craig.mayhew.io/blob/master/.travis.yml)

## partial local builds

You can specify what should be regenerated via arguments to build.php.  e.g. to deploy just the blog and pages
<pre>
php build.php blog pages 
</pre>

Or just regenerate the web pages and static content.
<pre>
php build.php pages static 
</pre>

Finally, deploy.
<pre>
php uploadeToS3.php
</pre>
