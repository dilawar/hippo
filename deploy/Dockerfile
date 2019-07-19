FROM opensuse/leap:15.1
MAINTAINER Dilawar Singh <dilawars@ncbs.res.in>

RUN zypper ref
RUN zypper in apache2-mod_php7
RUN zypper in php7-{ldap,imap,mbstring,pdo,mysql,imagick,xml,json}
RUN zypper install -y python3-numpy python3-networkx 
    python3-dateparser \
    python3-mysql-connector-python

RUN zypper in pandoc
RUN zypper in texlive-fontawesome texlive-pdftex-bin

RUN zypper install -y git

COPY hippo.conf /etc/httpd/conf.d/
COPY httpd-foreground /usr/local/bin/
COPY requirements.txt /tmp

RUN python3 -m pip install pip --upgrade
RUN python3 -m pip install -r /tmp/requirements.txt

RUN rm -rf $HOME/.cache/*

CMD ["httpd-foreground"]
