DATA_FILE=../../hippo/aws.txt
LAST_CP=$(shell ls -t ./cv/*.t7 | head -n1)
GPU=-1

all : train 

data.h5 data.json : $(DATA_FILE)
	python ./scripts/preprocess.py --input_txt $(DATA_FILE) \
	    --output_h5 data.h5 --output_json data.json


train : data.json data.h5
	@echo "Training"
	th ./train.lua -input_h5 data.h5 -input_json data.json -gpu $(GPU)

generate_sample : 
	echo "Using checkpoint $(LAST_CP)"
	th ./sample.lua -gpu $(GPU) -checkpoint $(LAST_CP)


sample : generate_samples 
