export PATH:=/opt/bin/:$(PATH)
PYTHON=`which python3`
DATA_FILE=/tmp/data.txt
LAST_CP=$(shell ls -t ./cv/*.t7 | head -n1)
GPU=-1

all : sample

$(DATA_FILE) : ./get_data_to_train.py
	$(PYTHON) $<

data.h5 data.json : $(DATA_FILE)
	$(PYTHON) ./scripts/preprocess.py --input_txt $(DATA_FILE) \
	    --output_h5 data.h5 --output_json data.json


train : data.json data.h5
	th ./train.lua -input_h5 data.h5 -input_json data.json -gpu $(GPU) \
	    -num_layers 3 -max_epochs 200

generate_sample : 
	th ./sample.lua -gpu $(GPU) -checkpoint $(LAST_CP) | tee _sample.txt


sample : generate_sample
