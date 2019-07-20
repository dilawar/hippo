VERSION := $(shell date +"%Y.%m.%d")
PWD := $(shell pwd)
HIPPO_BASE_URL:= 127.0.0.1/hippo
TAG := dilawars/hippo:$(VERSION)
LATESTTAG := dilawars/hippo:latest

BUILD_ARGS= --build-arg http_proxy="http://172.16.223.222:3128" \
    --build-arg https_proxy="http://172.16.223.222:3128"

all : build

build : $(DOCKERFILE)
	docker build $(BUILD_ARGS) -t $(TAG) .
	docker build $(BUILD_ARGS) -t $(LATESTTAG)  .

# Alpine should run with -d switch (daemon)
run :  build
	docker rm -f "NCBS-Hippo" || echo "Failed to remove"
	docker run \
	    -it \
	    --net host \
	    --name "NCBS-Hippo"\
	    -e HIPPO_BASE_URL:$(HIPPO_BASE_URL) \
	    -v $(PWD)/.:/srv/www/htdocs/hippo:rw \
	    -v /tmp/apache2:/var/log/apache2:rw \
	    -v /etc/hipporc:/etc/hipporc:ro \
	    -v /tmp:/tmp \
	    $(LATESTTAG) $(CMD)

upload :
	docker push $(TAG)
	docker push $(LATESTTAG)
