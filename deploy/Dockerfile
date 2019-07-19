FROM centos:latest
MAINTAINER Dilawar Singh
RUN yum update -y
RUN yum -y install epel-release
RUN yum install -y https://$(rpm -E '%{?centos:centos}%{!?centos:rhel}%{rhel}').iuscommunity.org/ius-release.rpm
RUN yum install -y python36u-pip python36u-devel gcc g++
RUN ln -s /usr/bin/python3.6 /usr/local/bin/python3

# Install pandoc/texlive
RUN yum install -y texlive-xetex texlive
RUN cd /opt && curl -sL https://github.com/jgm/pandoc/releases/download/2.7.3/pandoc-2.7.3-linux.tar.gz | tar zx
ENV PATH="/opt/pandoc-2.7.3/bin:$PATH"

RUN yum install -y git
RUN yum install -y php72u
RUN yum install -y php72u-pecl-imagick 
RUN yum install -y php72u-{imap,ldap,mbstring,pdo,mysqlnd}
RUN yum install -y php72u-{xml,json}

COPY hippo.conf /etc/httpd/conf.d/
COPY httpd-foreground /usr/local/bin/
COPY requirements.txt /tmp

RUN python3 -m pip install pip --upgrade
RUN python3 -m pip install -r /tmp/requirements.txt

RUN yum clean all
RUN rm -rf $HOME/.cache/*

CMD ["httpd-foreground"]
