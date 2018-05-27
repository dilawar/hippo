#!/usr/bin/env python
"""train_keras.py: 

"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import sys
import os
from keras.models import Sequential
from keras.layers import Dense, Activation
from keras import backend as K

model = Sequential( [
        Dense(32, input_shape=(784,)),
        Activation('relu'),
        Dense(10),
        Activation('softmax'),
        ])

def main():
    global model
    model.compile( optimizer='rmsprop'
            , loss='categorical_crossentropy'
            , metrices=['accuracy']
            )


if __name__ == '__main__':
    main()

