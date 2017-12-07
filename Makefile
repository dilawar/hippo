export PATH:=/opt/bin/:$(PATH)
PYTHON=python2.7
DATA_FILE=/tmp/aws.txt
GPU=-1

all : train 

data.h5 data.json : $(DATA_FILE)
	$(PYTHON) ./scripts/preprocess.py --input_txt $(DATA_FILE) \
	    --output_h5 data.h5 --output_json data.json


train : data.json data.h5
	@echo "Training"
	th ./train.lua -input_h5 data.h5 -input_json data.json -gpu $(GPU)

sample : 
	th ./sample.lua put_h5 -gpu $(GPU)
