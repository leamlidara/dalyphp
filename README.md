# Daly PHP
**Daly PHP** ជា PHP Framework មួយដែលត្រូវបានរៀបរៀងឡើងដោយកូនខ្មែរ។ Framework នេះផ្តោតសំខាន់លើ៖
- ប្រពន្ធ័សុវត្ថិភាព
- ទំហំតូច ដំណើរការលឿន
- ភាពងាយស្រួលក្នុងការប្រើប្រាស់

# វិធីដំឡើង
សូមធ្វើការចម្លង ថតឯកសារ Lib ទៅដាក់ក្នុងទីតាំងជាមួយ index.php បន្ទាប់មក សូមធ្វើការបង្កើតឯកសារ និងថតឯកសារឲ្យមានទម្រង់ដូចខាងកក្រោម៖
```
|-- index.php
|-- .htaccess
|-- lib
    |-- ....
    |-- ....
|-- application
    |-- Model
    |-- View
    |-- Controller
```
**ចំណាំ៖** ថតឯកសារ Model, View និង Controller ត្រូវមានអក្សរធំផ្តើម។_
បន្ទាប់មកសូម ចម្លងឃ្លារបញ្ជារខាងក្រោមទៅដាក់ក្នុងឯកសារឈ្មោះ .htaccess
```
AddDefaultCharset UTF-8
Options +FollowSymlinks
Options All -Indexes

RewriteEngine on

RewriteCond %{REQUEST_URI} !^\.(css|jpg|gif|zip|png|js|rar|mp4|mp3|wave)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php?dara=$1 [L,QSA]

#php_value upload_max_filesize 100M
#php_value post_max_size 102M

# compress text, html, javascript, css, xml:
AddOutputFilterByType DEFLATE text/plain
AddOutputFilterByType DEFLATE text/html
AddOutputFilterByType DEFLATE text/xml
AddOutputFilterByType DEFLATE application/x-httpd-php
AddOutputFilterByType DEFLATE text/css
AddOutputFilterByType DEFLATE application/xml
AddOutputFilterByType DEFLATE application/xhtml+xml
AddOutputFilterByType DEFLATE application/rss+xml
AddOutputFilterByType DEFLATE application/javascript
AddOutputFilterByType DEFLATE application/x-javascript
AddOutputFilterByType DEFLATE font/otf
AddOutputFilterByType DEFLATE font/ttf

# Or, compress certain file types by extension:
<files *.html>
SetOutputFilter DEFLATE
</files>

#to reduce the number of HTTP requests
<IfModule mod_expires.c>
    ExpiresActive on
 
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
</IfModule>

#---------> Enable Cache <------------
<ifModule mod_headers.c>
# Turn on Expires and set default expires to 3 days
ExpiresActive On
ExpiresDefault A259200
# Set up caching on media files for 1 month
<filesMatch ".(ico|gif|jpg|jpeg|png|flv|pdf|swf|mov|mp3|wmv|ppt)$">
ExpiresDefault A2419200
Header append Cache-Control "public"
</filesMatch>

# Set up 2 Hour caching on commonly updated files
<filesMatch ".(xml|txt|html|js|css)$">
ExpiresDefault A7200
Header append Cache-Control "private, must-revalidate"
</filesMatch>

# Force no caching for dynamic files
<filesMatch ".(php|cgi|pl|htm)$">
ExpiresDefault A0
Header set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
Header set Pragma "no-cache"
</filesMatch>
</ifModule>
#----------> End Cache <-----------

#protect log file
<Files "errorlog">
Order Allow,Deny
Deny from all
</Files>
<Files "error-log">
Order Allow,Deny
Deny from all
</Files>
```
សូមធ្វើការសរសេរ ឃ្លារបញ្ជារឲ្យស្គាល់ រវាង index.php, lib និង application ព្រមទាំង Configuration ខ្លះនៅក្នុងឯកសារ index.php
```php
<?php
    $dara_path = dirname(__FILE__);
    $dirMVCPath = $dara_path . '/application/'; //កំណត់ថតឯកសារ MVC
    $publicPath = $dara_path . '/'; //កំណត់ថតឯកសារ Public (សម្រាប់ផ្ទុកឯកសារផ្សេងៗ ដូចជា *.css, *.js, *.png, *.jpg...)
    $showError = TRUE; //កំណត់ឲ្យបង្ហាញ ឬមិនបង្ហាញ error
    
    $dirLibPath = dirname($dara_path) . '/lib/';
    require_once $dirLibPath.'core.php';
?>
```
ពត៌មានបន្ថែម សូមចូលទៅកាន់ https://www.dalyphp.com/
