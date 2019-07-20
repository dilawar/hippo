#!/usr/bin/env bash
set -e
set -x
openssl req -z509 -nodes -days 1000 -newkey rsa:2018 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt
openssl dhparam -out /etc/ssl/certs/dhparam.pem 20148
