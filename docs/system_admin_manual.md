# Deploying Hippo 

I recommend to use `docker`. It will save you a lot of troubles. Like most other
websites, this website also have tons of dependencies. All of which have been
put into a [docker
image](https://cloud.docker.com/u/dilawars/repository/docker/dilawars/hippo).

## Using docker

### Create `/etc/hipporc` file.

This file contains all sensitive parameters which must be kept in isolation.
Make sure default values works.  Hippo will not launch without this file. And
example script should be available in the repository `deploy/hipporc`.

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
    port = 3306
    ; Escape the special characters in password by enclosing it in " "
    password = "m!#ypassword"
    database = hippo

    [data]
    ; Users/speaker photos are stored here.
    user_imagedir = /srv/hippo/userimages

    [google calendar]
    ; More on this in appropriate section.
    calendar_id = d2jud2r7bsj0i820k0f6j702qo@group.calendar.google.com
    service_account_email = hippo-588@hippo-179605.iam.gserviceaccount.com 
    service_account_secret = /etc/hippo/hippo-f1811b036a3f.json
    ```

### Install `docker` and `docker-compose`

Official docker documentation is pretty good: https://docs.docker.com/install/

On my system

```bash
[dilawars@chamcham ~]$ docker -v
Docker version 18.09.7, build 2d0083d657f8
[dilawars@chamcham ~]$ docker-compose -v
docker-compose version 1.24.0, build 0aa5906
```

### Install nodejs 10+

https://nodejs.org/en/download/package-manager/

On my system:
```bash
[dilawars@chamcham ~]$ npm -v 
6.9.0
[dilawars@chamcham ~]$ node -v
v10.16.0
```

### Download Hippo

```bash
git clone {{repo_url}} --depth 10 --recursive
```

and install `node` dependencies.

```
$ cd hippo
$ npm ci
```


### Launch Hippo using `docker`

```bash
[dilawars@ghevar deploy (master)]$ cd hippo/deploy && docker-compose up
Creating deploy_hippo_1 ...
Creating deploy_hippo_1 ... done
Attaching to deploy_hippo_1
hippo_1  | + export http_proxy=http://proxy.ncbs.res.in:3128/
hippo_1  | + http_proxy=http://proxy.ncbs.res.in:3128/
hippo_1  | + export https_proxy=http://proxy.ncbs.res.in:3128/
hippo_1  | + https_proxy=http://proxy.ncbs.res.in:3128/
hippo_1  | + cron -n
hippo_1  | + exec apache2ctl -DFOREGROUND
hippo_1  | which: no w3m in (/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin)
hippo_1  | which: no lynx in (/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin)

```

It will download the image and launch the website inside the container. The
website is shared between host and docker. Any change made to the local website
will reflect in docker as well.

Point to `127.0.0.1/hippo` and you should hippo is alive.

???+ info "docker-compose"
    Command `docker-compose` read `docker.compose.yml` file which is in `deploy`
    directory. This is the base directory you should be in when calling
    `docker-compose`.

## Manually on CentOS

Hippo is written in `php7` and `python3`. You must have at least `php7.1` and
`python3.6` installed. Some dependencies may not be available in your package
manager, you can install them using `pip` (for python) or `pecl` (for php).
Some essential dependencies are in source code.

### Server configurations

```bash
$ sudo a2enmod rewrite
$ sudo a2enmod php
```

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

### Hippo AI (optional)

Hippo AI is an optional module to train a neural network to write Annual Work
Seminar. It's source code is hosted on
[github](https://github.com/dilawar/hippo-ai). See the instructions there how to
install it.

This repository is a `git subtree` prefixed to `hippo-ai` folder. That is, a
snapshot of `hippo-ai` repository is kept in this repository as `hippo-ai`. 

To train the NN with AWS abstract.

- torch-rnn 


## Notes

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

### Apache behind proxy

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

### How to setup google-calendar.

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


## Temporary migration to other server

This section deals with migrating Hippo to a temporary server.

0. Copy the `/var/www/html/hippo` from old sever to new server.
1. Dump the database to a `sql` file on the current hippo server
   `hippo_server`.

    ```bash
    $ mysqldump -u hippo -h hippo_server -p hippo > _mysqldump.sql 
    ```

2. Use this dump to replicated database on the new temporary server `new_hippo`.
   ```bash
   $ mysql -h new_hippo -u hippo -p < _mysqldump.sql
   ```

    !!! note "Check username/password and permissions"
        Check database section in `/etc/hipporc` configuration file. The mariadb
        sever on `new_hippo` should be configured for given credentials.

3. Setup apache2 and php. See dependencies above.

4. `new_hippo` should be given permission to access NCBS Ldap server. Without
  it, Hoppo would not be able to authenticate the users.

5. Copy directory `/srv/hippo`. apache user (e.g., `apache` or `www-data` or
   `wwwrun`) should be able to write in this directory.

       ```bash
       $ rsync -azv hippo@hippo_server:/srv/hippo hippo@new_hippo@/srv/hippo
       $ chown apache:apache /src/hippo # or apache 
       ```

6. Create a bogus booking request and check if you get an email. If not, check
   the email settings in `/etc/hipporc`. Also check `php7-imap` is installed.

???+ info "phpinfo"
    url `https://ncbs.res.in/hippo/info/phpinfo` will dump the output of
    `phpinfo()`.

Since it is a temporary migration, we are not setting up the cron jobs. I.e.,
this server will not send out automatic emails and notifications.

7. Make sure that SELinux does not restrict ports (see
   https://stackoverflow.com/a/39468939/1805129)

   ```
   setsebool -P httpd_can_network_connect 1
   ```

   Or, it does not restrict running bash scripts (see
   https://superuser.com/a/455990/81509) 
   ```
   $ sudo /usr/sbin/setenforce Permissive
   ```

8. Disable `PrivateTmp` for apache using systemd. Else temporary files will be
   created into
   `/tmp/systemd-private-7bfc885d8c04469f8bf9cf6931d53c87-httpd.service-Vwz3sj/`
   etc.

    ```
    mkdir /etc/systemd/system/httpd.service.d
    echo "[Service]" >  /etc/systemd/system/httpd.service.d/nopt.conf
    echo "PrivateTmp=false" >> /etc/systemd/system/httpd.service.d/nopt.conf
    ```
