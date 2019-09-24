PYTHON=`which python3`
DATA_FILE=/tmp/data.txt
LAST_CP=$(shell ls -t ./cv/*.t7 | head -n1)
GPU=-1
export PATH := /usr/local/bin:$(PATH)

all : sample

$(DATA_FILE) : ./get_data_to_train.py
	$(PYTHON) $<

data.h5 data.json : $(DATA_FILE)
	$(PYTHON) ./scripts/preprocess.py --input_txt $(DATA_FILE) \
	    --output_h5 data.h5 --output_json data.json

train : data.json data.h5
	th ./train.lua -input_h5 data.h5 -input_json data.json -gpu $(GPU) \
	    -num_layers 3 -max_epochs 500

generate_sample : 
	docker run \
	    -v /tmp:/tmp:rw \
	    -v $(PWD):/home/torch:rw \
	    -w /home/torch \
	    -e TERM=xterm \
	    --rm crisbal/torch-rnn:base \
	    th ./sample.lua -gpu -1 -checkpoint $(LAST_CP) 


sample : generate_sample
