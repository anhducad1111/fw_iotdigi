import os
import sys
import glob
import math
import numpy as np
import pandas as pd
import tensorflow as tf
from pathlib import Path
from sklearn.utils import shuffle
from sklearn.model_selection import train_test_split
from tensorflow.keras.callbacks import ReduceLROnPlateau, EarlyStopping
from tensorflow.keras.preprocessing.image import ImageDataGenerator

# Note: You need the 'src' folder from the original repo for these imports
# If running standalone, you must define the model architecture manually.
from src.models.ana_cont import *
from src.utils.augmentation import random_invert_image, random_white_balance

# 1. PARAMETERS & CONFIGURATION
TFlite_MainType = 'ana-cont'
TFlite_Version = '1000' # Example version
TFlite_Size = 's0'      # Options: s0, s1, s2, s3
Input_Dir = 'data_raw_all'
Output_Dir = 'models/ana-cont'
Input_Shape = (32, 32, 3)

Path(Output_Dir).mkdir(parents=True, exist_ok=True)

# 2. DATA LOADING & PRE-PROCESSING
def load_and_preprocess_data():
    files = glob.glob(f"{Input_Dir}/*.jpg")
    num_files = len(files)
    
    x_data = np.empty((num_files, *Input_Shape), dtype="float32")
    y_data = np.empty((num_files, 2), dtype="float32") # sin, cos
    y_target = np.empty(num_files)
    f_data = np.array(files)

    for i, file in enumerate(files):
        # Read and Resize
        img = tf.io.read_file(file)
        img = tf.image.decode_image(img, channels=3, expand_animations=False)
        img = tf.image.resize(img, [Input_Shape[0], Input_Shape[1]], method='mitchellcubic')
        
        # Labels: extract value from filename (e.g., 4.6_... -> 0.46)
        # The notebook calculates sin/cos to handle periodic nature of the dial
        base = Path(file).name
        val = float(base[:3]) / 10.0
        
        x_data[i] = tf.clip_by_value(img, 0.0, 255.0).numpy()
        y_data[i] = [math.sin(val * math.pi * 2), math.cos(val * math.pi * 2)]
        y_target[i] = val
        
    return x_data, y_data, y_target, f_data

x_data, y_data, y_target, f_data = load_and_preprocess_data()
x_data, y_data, y_target, f_data = shuffle(x_data, y_data, y_target, f_data)

# 3. MODEL INITIALIZATION
model_map = {
    "s0": model_ana_cont_s0,
    "s1": model_ana_cont_s1,
    "s2": model_ana_cont_s2,
    "s3": model_ana_cont_s3
}
model = model_map[TFlite_Size](input_shape=Input_Shape, learning_rate=1e-3)

# 4. AUGMENTATION & GENERATORS
def custom_preprocessing(x):
    x = random_white_balance(x)
    x = random_invert_image(x)
    return np.clip(x, 0.0, 255.0).astype(np.float32)

x_train, x_val, y_train, y_val = train_test_split(x_data, y_data, test_size=0.2)

train_datagen = ImageDataGenerator(
    width_shift_range=[-1, 1], height_shift_range=[-1, 1],
    brightness_range=[0.8, 1.2], zoom_range=[0.95, 1.05],
    preprocessing_function=custom_preprocessing
)
train_it = train_datagen.flow(x_train, y_train, batch_size=8)
val_it = ImageDataGenerator().flow(x_val, y_val, batch_size=8)

# 5. TRAINING
callbacks = [
    ReduceLROnPlateau(monitor='val_loss', factor=0.9, patience=5, min_lr=1e-5),
    EarlyStopping(monitor='val_loss', patience=40, restore_best_weights=True)
]

model.fit(train_it, validation_data=val_it, epochs=600, callbacks=callbacks)

# 6. EXPORT TO TFLITE (Standard & Quantized)
base_name = f"{Output_Dir}/{TFlite_MainType}_{TFlite_Version}_{TFlite_Size}"

# Standard TFLite
converter = tf.lite.TFLiteConverter.from_keras_model(model)
with open(f"{base_name}.tflite", "wb") as f:
    f.write(converter.convert())

# Quantized TFLite (for ESP32-CAM / Edge devices)
def rep_dataset():
    for i in range(min(100, x_data.shape[0])):
        yield [np.expand_dims(x_data[i], axis=0).astype(np.float32)]

converter_q = tf.lite.TFLiteConverter.from_keras_model(model)
converter_q.optimizations = [tf.lite.Optimize.DEFAULT]
converter_q.representative_dataset = rep_dataset
converter_q.target_spec.supported_ops = [tf.lite.OpsSet.TFLITE_BUILTINS_INT8]
converter_q._experimental_disable_per_channel_quantization_for_dense_layers = True

with open(f"{base_name}_q.tflite", "wb") as f:
    f.write(converter_q.convert())

print(f"Training complete. Models saved in {Output_Dir}")