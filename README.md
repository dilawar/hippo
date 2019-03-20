<p align="center">
<img src="https://user-images.githubusercontent.com/34600369/41531871-d050fa18-72ec-11e8-82e8-9d6067b1a59d.png" width="450">

[![Codacy Badge](https://api.codacy.com/project/badge/Grade/209e3bc107ba462c99d6342ea15ece70)](https://www.codacy.com/app/dilawar/HippoIgnited?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=dilawar/HippoIgnited&amp;utm_campaign=Badge_Grade)

# NCBS Hippo

# Dependencies 

- Requires PHP >= 7.x 
- php7, php7-imap, php7-ldap, php7-imagick
- php-gd, php-mbstring, php-zip
- php-oauth
- sudo -E pecl install mailparse
- mysql 
- python-pypandoc
- sudo pip install mysql-connector-python-rf
- pandoc >= 1.19.2.1
- python-PIL (for background image processing).
- python > 3.6 (Photography club scripts require it)

## Optional 

To train the NN with AWS abstract.

- torch-rnn 

# Apache behind proxy

To communicate to google-calendar, apache needs to know proxy server. Write
following in `httpd.conf` file

    SetEnv HTTP_PROXY 172.16.223.223:3128
    SetEnv HTTPS_PROXY 172.16.223.223:3128

To make sure that server accepts API requests from android app.

    header set access-control-allow-origin "*"
    header set access-control-allow-headers "content-type"

# How to setup google-calendar.

0. Go to google-api console, and setup an API key. Download the key and store it
   in `/etc/hippo/hippo-f1811b036a3f.json`.
1. Go to google calendar and add google-service account email in `share
   calendar` settings. Grant all permissions to new account.

2. Following is the snippet to construct API.


```
$secFile = '/etc/hippo/hippo-f1811b036a3f.json';
putenv( 'GOOGLE_APPLICATION_CREDENTIALS=' . $secFile );
$this->client = new Google_Client( );
$this->client->useApplicationDefaultCredentials( );
// Mimic user (service account).
$this->client->setSubject( 'google-service_account@gservice.com' );
$this->client->setScopes( 'https://www.googleapis.com/auth/calendar');
```

# Notes

For rewrite rule to work: see this post https://stackoverflow.com/a/8260985/1805129

    $ sudo a2enmod rewrite
    $ sudo systemctl restart apache2

## To enable ssl
  
    $ sudo a2enmod ssl
    $ sudo a2ensite default-ssl
