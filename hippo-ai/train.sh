#!/usr/bin/env bash

GPU=1
th ./train.lua -input_h5 data.h5 -input_json data.json -gpu $GPU \
    -num_layers 3 -max_epochs 500
