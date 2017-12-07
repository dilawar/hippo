DATA_FILE=../../hippo/aws.txt
GPU=-1

all : train 


data.json : $(DATA_FILE)
	python ./scripts/preprocess.py --input_txt $(DATA_FILE) \
	    --output_json $@

data.h5 : $(DATA_FILE)
	python ./scripts/preprocess.py --input_txt $(DATA_FILE) \
	    --output_h5 $@


train : data.json data.h5
	@echo "Training"
	th ./train.lua -input_h5 data.h5 -input_json data.json -gpu $(GPU)

sample : 
	th ./sample.lua put_h5 -gpu $(GPU)
