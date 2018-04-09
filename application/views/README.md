#NCBS Hippo

AWS scheduler cum even manager for NCBS. 

# Dependencies 

- Requires PHP >= 7.x 
- php7, php7-imap, php7-ldap, php7-imagick
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


