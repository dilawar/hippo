PWD := $(shell pwd)

all : ./docker-compose.yml
	docker-compose build 

run:
	docker run -it \
	    --net host \
	    -v /tmp:/tmp \
	    -v $(PWD)/..:/srv/www/htdocs/hippo \
	    dilawars/hippo:2019.07.20
