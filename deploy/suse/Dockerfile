FROM dilawars/leap151-apache2:latest

MAINTAINER Dilawar Singh <dilawars@ncbs.res.in>

# python3
RUN zypper install -y \
    python3-numpy python3-networkx \
    python3-regex python3-pip \
    python3-mysql-connector-python \
    python3-Pillow \
    vim \
    && rm -rf /var/cache/zypp/packages/*

# texlive and pandoc.
RUN zypper install -y \
    pandoc \
    texlive-fontawesome texlive-pdftex-bin \
    texlive-xetex-bin \
    texlive-wrapfig \
    texlive-tcolorbox \
    texlive-nimbus15-fonts \
    tmux \
    && rm -rf /var/cache/zypp/packages/*

# cron
RUN zypper install -y cronie \
    && rm -rf /var/cache/zypp/packages/*

# Other dependencies.

COPY requirements.txt /tmp
RUN python3 -m pip install -r /tmp/requirements.txt

# copy to vhost.
COPY hippo.conf /etc/apache2/vhosts.d/
COPY httpd-foreground /usr/local/bin/
COPY hippo-cron /etc/cron.d/hippo-cron
RUN touch /var/log/cron.log

RUN a2enmod php7 
RUN a2enmod rewrite
RUN a2enmod ldap
RUN a2enmod imap
RUN a2enmod -l
CMD ["httpd-foreground"]
