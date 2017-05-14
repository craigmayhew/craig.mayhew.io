# craig.mayhew.io

Craig Mayhew's Personal website

Blog posts are stored in blogposts/ as json files. One file per post.
Pages are stored in pages/ as json files. One file per page.

PHP is used to generate static html in the htdocs directory and then optionally sync that to an S3 bucket.

## install

<pre>
php composer.phar selfupdate
php composer.phar install
</pre>

## deploy

<pre>
cd tools
php createstatic.php
php uploadeToS3.php
</pre>
