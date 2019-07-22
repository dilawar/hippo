# Deploying Hippo

## Using docker

First, create `/etc/hipporc` file with following details.

```
[global]
ldap_ip = ldap.example.in
ldap_port = 8862
log_file = /var/log/hippo.log

[email]
send_emails = true
smtp_server = mail.example.in
smtp_port = 581

[mysql]
host = 127.0.0.1
user = hippouser
; Use the default port
port = -1
; Escape the special characters in password by enclosing it in " "
password = "m!#ypassword"
database = hippo

[data]
user_imagedir = /srv/hippo/userimages

[google calendar]
calendar_id = d2jud2r7bsj0i820k0f6j702qo@group.calendar.google.com
service_account_email = hippo-588@hippo-179605.iam.gserviceaccount.com 
service_account_secret = /etc/hippo/hippo-f1811b036a3f.json
```

## Manually

Hippo is written in `php7` and `python3`. You must have at least `php7.1` and
`python3.6` installed. Some dependencies may not be available in your package
manager, you can install them using `pip` (for python) or `pecl` (for php).
Some essential dependencies are in source code.

### PHP dependencies 

- imap
- ldap
- imagick
- gd
- mbstring
- oauth
- mailparse

### Python dependencies
- pypandoc (available via `pip`)
- mysql-connector-python (available via `pip`)
- networkx 
- numpy
- python-PIL (for background image processing).

### javascript dependencies

Numerous. After checking out the source code. Do a `npm ci`.

### Other

- mariadb (>= 10.0) 
- pandoc (>= 1.19.2.1)

### Optional 

To train the NN with AWS abstract.

- torch-rnn 

# Apache behind proxy

To communicate to google-calendar, apache needs to know proxy server. Write
following in `httpd.conf` file

```bash
SetEnv HTTP_PROXY 172.16.223.223:3128
SetEnv HTTPS_PROXY 172.16.223.223:3128
```

To make sure that server accepts API requests from android app.

```
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Headers "content-type"
```

# How to setup google-calendar.

0. Go to google-api console, and setup an API key. Download the key and store it
   in `/etc/hippo/hippo-f1811b036a3f.json`.
1. Go to google calendar and add google-service account email in `share
   calendar` settings. Grant all permissions to new account.
2. Following is the snippet to construct API.


```php
$secFile = '/etc/hippo/hippo-f1811b036a3f.json';
putenv( 'GOOGLE_APPLICATION_CREDENTIALS=' . $secFile );
$this->client = new Google_Client( );
$this->client->useApplicationDefaultCredentials( );
// Mimic user (service account).
$this->client->setSubject( 'google-service_account@gservice.com' );
$this->client->setScopes( 'https://www.googleapis.com/auth/calendar');
```

# Notes

- For rewrite rule to work: see this post
  https://stackoverflow.com/a/8260985/1805129
```bash
$ sudo a2enmod rewrite
$ sudo systemctl restart apache2
```

- To enable ssl
```bash
$ sudo a2enmod ssl
$ sudo a2ensite default-ssl
```

# Hippo AI

Hippo AI is an optional module to train a neural network to write Annual Work
Seminar. It's source code is hosted on
[github](https://github.com/dilawar/hippo-ai). See the instructions there how to
install it.

This repository is a `git subtree` prefixed to `hippo-ai` folder. That is, a
snapshot of `hippo-ai` repository is kept in this repository as `hippo-ai`. 

??? todo "More to follow"
