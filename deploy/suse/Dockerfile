FROM opensuse/leap:15.1
MAINTAINER Dilawar Singh <dilawars@ncbs.res.in>

# RUN zypper ref
RUN zypper in -y apache2-mod_php7
RUN zypper in -y php7-{ldap,mbstring,pdo,mysql,imagick,json}
RUN zypper install -y python3-numpy python3-networkx \
    python3-pip \
    python3-mysql-connector-python

RUN zypper in -y pandoc
RUN zypper in -y python3-regex
COPY requirements.txt /tmp
RUN python3 -m pip install -r /tmp/requirements.txt
RUN zypper in -y texlive-fontawesome texlive-pdftex-bin

# copy to vhost.
COPY hippo.conf /etc/apache2/vhost.d/
COPY httpd-foreground /usr/local/bin/

RUN a2enmod php7 rewrite
# CMD rcapache2 restart && tail -f /var/log/apache2/*log
CMD ["httpd-foreground"]
