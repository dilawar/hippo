PWD := $(shell pwd)

all : run


run : 
	docker run \
	    -it \
	    --net host \
	    -e APACHE_SERVER_NAME=127.0.0.1 \
	    -v $(PWD)/../../:/app/public/hippo:rw \
	    -v /etc/hipporc:/etc/hipporc:ro \
	    ulsmith/alpine-apache-php7 
