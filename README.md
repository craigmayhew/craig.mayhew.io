# craig.mayhew.io

Craig Mayhew's Personal website

Blog posts are stored in blogposts/ as json files. One file per post.
Articles are stored in articles/ as json files. One file per page.
Pages are stored in pages/ as json files. One file per page.

## deploy

IPFS: https://app.fleek.co/ builds and hosts the IPFS version whenever it detects changes on the "main" github branch.
Web 2.0 builds run on github actions and deploy to AWS S3 + Cloudfront.

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
