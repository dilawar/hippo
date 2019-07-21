FROM opensuse/leap:15.1
MAINTAINER Dilawar Singh <dilawars@ncbs.res.in>

# Install dependencies.
RUN zypper install -y apache2-mod_php7 \
    && rm -rf /var/cache/zypp/packages/*

RUN zypper install -y \
    php7-ldap \
    php7-mbstring \
    php7-pdo \
    php7-mysql \
    php7-imagick \
    php7-json \
    php7-dom \
    && rm -rf /var/cache/zypp/packages/*

RUN zypper install -y \
    python3-numpy python3-networkx \
    python3-regex python3-pip \
    python3-mysql-connector-python \
    python3-Pillow \
    vim \
    && rm -rf /var/cache/zypp/packages/*

RUN zypper install -y \
    pandoc \
    texlive-fontawesome texlive-pdftex-bin \
    texlive-xetex-bin \
    texlive-wrapfig \
    texlive-tcolorbox \
    texlive-nimbus15-fonts \
    tmux \
    && rm -rf /var/cache/zypp/packages/*

RUN zypper install -y \
    nodejs-common \
    && rm -rf /var/cache/zypp/packages/*

COPY requirements.txt /tmp
RUN python3 -m pip install -r /tmp/requirements.txt

# copy to vhost.
COPY hippo.conf /etc/apache2/vhosts.d/
COPY httpd-foreground /usr/local/bin/

RUN a2enmod php7 
RUN a2enmod rewrite
RUN a2enmod ldap
RUN a2enmod imap
RUN a2enmod -l
CMD ["httpd-foreground"]
