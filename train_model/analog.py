"""
CNN Training Script for Analog Needle Readout (Model Type: ana-cont)
Train a CNN network to extract needle position using sin/cos encoding and angle calculation
"""

import os
import sys
import glob
from pathlib import Path
import math
import numpy as np
import pandas as pd

import tensorflow as tf
from tensorflow import keras
from tensorflow.keras.callbacks import ReduceLROnPlateau, EarlyStopping
from tensorflow.keras.preprocessing.image import ImageDataGenerator

from sklearn.utils import shuffle
from sklearn.model_selection import train_test_split

import matplotlib.pyplot as plt


# ============================================================================
# CONFIGURATION PARAMETERS
# ============================================================================

# Model Configuration
TFLITE_MAINTYPE = 'ana-cont'
TFLITE_VERSION = '1111'  # e.g.  '0100' for v1.00
TFLITE_SIZE = 's1'  # Options: 's0', 's1', 's2', 's3'
AVAILABLE_MODEL_SIZES = {'s0', 's1', 's2', 's3'}

# Directory Configuration
INPUT_DIR = 'data_raw_all'
OUTPUT_DIR = 'models/ana-cont'

# Input Image Configuration
INPUT_SHAPE = (32, 32, 3)  # (width, height, channels)

# Training Configuration
VALIDATION_PERCENTAGE = 0.2  # 0.0 = no validation, use all for training
BATCH_SIZE = 8
TRAINING_EPOCHS = 600

# Data Augmentation Configuration
SHIFT_RANGE = 1
BRIGHTNESS_RANGE = 0.2
ZOOM_RANGE = 0.05
CHANNEL_SHIFT_RANGE = 5
SHEAR_RANGE = 1

# Evaluation Configuration
ACCEPTED_DEVIATION = 0.1  # Allowed deviation for accuracy calculation


# ============================================================================
# AUGMENTATION HELPER FUNCTIONS
# ============================================================================

def random_white_balance(image):
    """Apply random white balance adjustment"""
    if np.random.random() > 0.5:
        factors = np.random.uniform(0.8, 1.2, 3)
        image = image * factors
    return image


def random_invert_image(image):
    """Randomly invert image colors"""
    if np.random. random() > 0.5:
        image = 255. 0 - image
    return image


def preprocessing(x):
    """Preprocessing function for data augmentation"""
    x = random_white_balance(x)
    x = random_invert_image(x)
    x = np.clip(x, 0.0, 255.0)
    return x. astype(np.float32)


# ============================================================================
# MODEL DEFINITIONS
# ============================================================================

def model_ana_cont_s0(input_shape=(32, 32, 3), learning_rate=1e-3):
    """Smallest model variant"""
    from tensorflow.keras.models import Model
    from tensorflow.keras.layers import (Input, BatchNormalization, Conv2D, MaxPool2D,
                                         Dropout, Flatten, Dense)
    from tensorflow.keras.regularizers import l2
    from tensorflow.keras.optimizers import Adam

    input_layer = Input(shape=input_shape)

    x = BatchNormalization()(input_layer)
    x = Conv2D(16, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = BatchNormalization()(x)
    x = Conv2D(32, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = BatchNormalization()(x)
    x = Conv2D(64, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = BatchNormalization()(x)
    x = Conv2D(128, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = Flatten()(x)

    x = BatchNormalization()(x)
    x = Dense(128, activation="relu", kernel_regularizer=l2(1e-4))(x)
    x = Dropout(0.3)(x)

    x = Dense(64, activation="relu", kernel_regularizer=l2(1e-4))(x)

    output = Dense(2)(x)  # sin and cos output

    model = Model(inputs=input_layer, outputs=output)

    model.compile(
        loss="mse",
        optimizer=Adam(learning_rate=learning_rate),
        metrics=["mse"]
    )

    return model


def model_ana_cont_s1(input_shape=(32, 32, 3), learning_rate=1e-3):
    """Standard model variant"""
    from tensorflow.keras. models import Model
    from tensorflow. keras.layers import (Input, BatchNormalization, Conv2D, MaxPool2D,
                                         Dropout, Flatten, Dense)
    from tensorflow.keras. regularizers import l2
    from tensorflow.keras.optimizers import Adam

    input_layer = Input(shape=input_shape)

    x = BatchNormalization()(input_layer)
    x = Conv2D(16, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = BatchNormalization()(x)
    x = Conv2D(32, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = BatchNormalization()(x)
    x = Conv2D(64, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = BatchNormalization()(x)
    x = Conv2D(64, (3, 3), padding='same', activation="relu")(x)
    x = MaxPool2D(pool_size=(2, 2))(x)

    x = Flatten()(x)

    x = BatchNormalization()(x)
    x = Dense(128, activation="relu", kernel_regularizer=l2(1e-4))(x)
    x = Dropout(0.3)(x)

    x = Dense(64, activation="relu", kernel_regularizer=l2(1e-4))(x)

    output = Dense(2)(x)

    model = Model(inputs=input_layer, outputs=output)

    model.compile(
        loss="mse",
        optimizer=Adam(learning_rate=learning_rate),
        metrics=["mse"]
    )

    return model


def model_ana_cont_s2(input_shape=(32, 32, 3), learning_rate=1e-3):
    """Medium model variant - implement based on repository"""
    # Similar structure to s1 but with different layer sizes
    return model_ana_cont_s1(input_shape, learning_rate)  # Placeholder


def model_ana_cont_s3(input_shape=(32, 32, 3), learning_rate=1e-3):
    """Large model variant - implement based on repository"""
    # Similar structure but larger
    return model_ana_cont_s1(input_shape, learning_rate)  # Placeholder


# ============================================================================
# EVALUATION FUNCTIONS
# ============================================================================

def predict_and_evaluate(model, x_data, y_data, y_data_target):
    """
    Predict and compute deviation for ana-cont model
    
    Args:
        model: Trained model
        x_data: Image data
        y_data: Sin/cos values
        y_data_target:  Real target values
    
    Returns: 
        predicted_val, expected_val, deviation_val
    """
    # Predict sin/cos
    classes = model.predict(x_data. astype(np.float32), verbose=0)
    
    predicted_sin = classes[: , 0]
    predicted_cos = classes[:, 1]
    
    # Convert sin/cos back to angle value
    predicted_val = (np.arctan2(predicted_sin, predicted_cos) / (2 * math.pi) % 1) * 10.0
    expected_val = np.array(y_data_target * 10.0)
    
    # Compute deviation with wrap-around logic
    deviation_val = np. minimum(
        np.abs(predicted_val - expected_val),
        np.abs(predicted_val - (10.0 - expected_val))
    )
    
    # Round values
    predicted_val = np. round(predicted_val, 1)
    expected_val = np.round(expected_val, 1)
    deviation_val = np.round(deviation_val, 1)
    
    return predicted_val, expected_val, deviation_val


def get_false_predictions(expected_val, predicted_val, deviation_val, 
                         x_data, f_data, accepted_deviation=0.1):
    """
    Filter and return false predictions sorted by deviation
    
    Returns:
        dict with keys:  'dev', 'exp', 'pred', 'img', 'file', 'lbl'
    """
    false_predicted_mask = deviation_val > accepted_deviation
    
    # Filter arrays
    false_predicted_dev = deviation_val[false_predicted_mask]
    false_predicted_exp = expected_val[false_predicted_mask]
    false_predicted_pred = predicted_val[false_predicted_mask]
    false_predicted_img = x_data[false_predicted_mask]
    false_predicted_files = [Path(f).name for i, f in enumerate(f_data) 
                            if false_predicted_mask[i]]
    
    false_predicted_labels = [
        f"Expected: {float(e):.1f}\nPredicted: {float(p):.1f}\n{Path(f).name[:-4][: 20]}"
        for e, p, f in zip(false_predicted_exp, false_predicted_pred,
                          np.array(f_data)[false_predicted_mask])
    ]
    
    # Sort by deviation (descending)
    sorted_indices = np.argsort(false_predicted_dev)[::-1]
    
    return {
        "dev": false_predicted_dev[sorted_indices],
        "exp": false_predicted_exp[sorted_indices],
        "pred":  false_predicted_pred[sorted_indices],
        "img": false_predicted_img[sorted_indices],
        "file": np.array(false_predicted_files)[sorted_indices],
        "lbl": np.array(false_predicted_labels)[sorted_indices]
    }


# ============================================================================
# PLOTTING FUNCTIONS
# ============================================================================

def plot_loss(history, validation=True):
    """Plot training history"""
    plt.figure(figsize=(12, 4))
    
    plt.subplot(1, 2, 1)
    plt.plot(history.history['loss'], label='Training Loss')
    if validation:
        plt.plot(history.history['val_loss'], label='Validation Loss')
    plt.xlabel('Epoch')
    plt.ylabel('Loss')
    plt.legend()
    plt.title('Model Loss')
    plt.grid(True)
    
    plt.tight_layout()
    plt.show()


def plot_dataset_distribution(y_data):
    """Plot distribution of dataset values"""
    plt.figure(figsize=(12, 4))
    plt.hist(y_data * 10, bins=100, edgecolor='black')
    plt.xlabel('Value')
    plt.ylabel('Count')
    plt.title('Dataset Distribution')
    plt.grid(True)
    plt.show()


def plot_divergence(binned_data, title):
    """Plot divergence histogram"""
    plt.figure(figsize=(12, 4))
    plt.bar(range(len(binned_data)), binned_data)
    plt.xlabel('Deviation (x0. 1)')
    plt.ylabel('Count')
    plt.title(title)
    plt.grid(True)
    plt.show()


def plot_dataset_analog_result(images, labels, columns=7, rows=7, figsize=(18, 18)):
    """Plot false predictions"""
    fig, axes = plt.subplots(rows, columns, figsize=figsize)
    axes = axes.flatten()
    
    for i in range(min(len(images), rows * columns)):
        axes[i].imshow(images[i]. astype(np.uint8))
        axes[i].set_title(labels[i], fontsize=8)
        axes[i].axis('off')
    
    # Hide unused subplots
    for i in range(len(images), rows * columns):
        axes[i].axis('off')
    
    plt.tight_layout()
    plt.show()


# ============================================================================
# MAIN TRAINING PIPELINE
# ============================================================================

def main():
    """Main training pipeline"""
    
    print("="*80)
    print("CNN Training for Analog Needle Readout (ana-cont)")
    print("="*80)
    
    # Validate configuration
    if str(TFLITE_VERSION).isdigit() and len(str(TFLITE_VERSION)) < 4:
        tflite_version = str(TFLITE_VERSION).zfill(4)
    else:
        tflite_version = TFLITE_VERSION
    
    if TFLITE_SIZE not in AVAILABLE_MODEL_SIZES:
        raise ValueError(f"Invalid TFlite_Size '{TFLITE_SIZE}'.  "
                        f"Must be one of: {', '.join(AVAILABLE_MODEL_SIZES)}")
    
    # Prepare folders
    if not (Path(INPUT_DIR).exists() and Path(INPUT_DIR).is_dir()):
        sys.exit(f"Folder '{INPUT_DIR}' does not exist.")
    
    Path(OUTPUT_DIR).mkdir(parents=True, exist_ok=True)
    
    # Disable GPUs (train on CPU)
    try:
        tf.config.set_visible_devices([], 'GPU')
    except: 
        pass
    
    print(f"\n{'='*80}")
    print("STEP 1: Loading Images")
    print(f"{'='*80}")
    
    # Load images
    files = glob.glob(f"{INPUT_DIR}/*.jpg")
    num_files = len(files)
    print(f"Found {num_files} images")
    
    # Prepare data containers
    f_data = np.empty(num_files, dtype="<U250")
    x_data = np.empty((num_files, INPUT_SHAPE[0], INPUT_SHAPE[1], INPUT_SHAPE[2]), 
                     dtype="float32")
    y_data = np.empty((num_files, 2), dtype="float32")
    y_data_target = np.empty(num_files)
    
    # Process files
    for i, file in enumerate(files):
        # Read image
        image_bytes = tf.io.read_file(file)
        image = tf.image.decode_image(image_bytes, channels=INPUT_SHAPE[2], 
                                      expand_animations=False)
        
        # Resize if needed
        if image.shape[0] != INPUT_SHAPE[0] or image. shape[1] != INPUT_SHAPE[1]:
            image = tf.image.resize(image, [INPUT_SHAPE[0], INPUT_SHAPE[1]], 
                                   method=tf.image.ResizeMethod. MITCHELLCUBIC)
            image = tf.clip_by_value(tf.cast(image, tf.float32), 0.0, 255.0)
        else:
            image = tf.cast(image, tf.float32)
        
        # Extract truth value and calculate sin/cos
        base = Path(file).name
        target_number = float(base[: 3]) / 10
        target_sin = math.sin(target_number * math.pi * 2)
        target_cos = math.cos(target_number * math.pi * 2)
        
        # Save data
        f_data[i] = file
        x_data[i] = image. numpy()
        y_data[i] = [target_sin, target_cos]
        y_data_target[i] = target_number
        
        if i % 500 == 0:
            print(f"  {i} files processed...")
    
    print(f"Loaded {len(y_data)} images")
    print(f"Image shape: {x_data. shape}")
    print(f"Label shape: {y_data.shape}")
    
    # Plot dataset distribution
    print("\nDataset Distribution:")
    plot_dataset_distribution(y_data_target)
    
    print(f"\n{'='*80}")
    print("STEP 2: Building Model")
    print(f"{'='*80}")
    
    # Build model
    if TFLITE_SIZE == "s0":
        model = model_ana_cont_s0(input_shape=INPUT_SHAPE, learning_rate=1e-3)
    elif TFLITE_SIZE == "s1":
        model = model_ana_cont_s1(input_shape=INPUT_SHAPE, learning_rate=1e-3)
    elif TFLITE_SIZE == "s2": 
        model = model_ana_cont_s2(input_shape=INPUT_SHAPE, learning_rate=1e-3)
    elif TFLITE_SIZE == "s3":
        model = model_ana_cont_s3(input_shape=INPUT_SHAPE, learning_rate=1e-3)
    else:
        raise ValueError(f"TFlite_Size:  '{TFLITE_SIZE}' is not supported.")
    
    model.summary()
    
    print(f"\n{'='*80}")
    print("STEP 3: Preparing Data")
    print(f"{'='*80}")
    
    # Shuffle dataset
    x_data, y_data, y_data_target, f_data = shuffle(x_data, y_data, y_data_target, f_data)
    
    # Split into train/validation
    x_train, x_test, y_train, y_test = train_test_split(
        x_data, y_data, test_size=VALIDATION_PERCENTAGE
    )
    
    print(f"Training samples: {len(x_train)}")
    print(f"Validation samples: {len(x_test)}")
    
    # Prepare training data generator with augmentation
    datagen = ImageDataGenerator(
        width_shift_range=[-SHIFT_RANGE, SHIFT_RANGE],
        height_shift_range=[-SHIFT_RANGE, SHIFT_RANGE],
        brightness_range=[1 - BRIGHTNESS_RANGE, 1 + BRIGHTNESS_RANGE],
        zoom_range=[1 - ZOOM_RANGE, 1 + ZOOM_RANGE],
        channel_shift_range=CHANNEL_SHIFT_RANGE,
        shear_range=SHEAR_RANGE,
        preprocessing_function=preprocessing
    )
    
    train_iterator = datagen.flow(x_train, y_train, batch_size=BATCH_SIZE)
    
    # Prepare validation data generator (no augmentation)
    if VALIDATION_PERCENTAGE > 0:
        datagen_val = ImageDataGenerator()
        validation_iterator = datagen_val. flow(x_test, y_test, batch_size=BATCH_SIZE)
    
    print(f"\n{'='*80}")
    print("STEP 4: Training Model")
    print(f"{'='*80}")
    
    # Callbacks
    lr_scheduler = ReduceLROnPlateau(
        monitor='val_loss', factor=0.9, patience=5, min_lr=1e-5, verbose=1
    )
    
    early_stopping = EarlyStopping(
        monitor='val_loss', mode='min', patience=40, 
        restore_best_weights=True, verbose=1
    )
    
    # Train
    if VALIDATION_PERCENTAGE > 0:
        history = model.fit(
            train_iterator,
            validation_data=validation_iterator,
            epochs=TRAINING_EPOCHS,
            callbacks=[lr_scheduler, early_stopping],
            verbose=1
        )
    else:
        history = model.fit(
            train_iterator,
            epochs=TRAINING_EPOCHS,
            callbacks=[lr_scheduler, early_stopping],
            verbose=1
        )
    
    # Plot training history
    print("\nTraining History:")
    plot_loss(history, validation=VALIDATION_PERCENTAGE > 0)
    
    print(f"\n{'='*80}")
    print("STEP 5: Model Evaluation")
    print(f"{'='*80}")
    
    # Predict and evaluate
    predicted_val, expected_val, deviation_val = predict_and_evaluate(
        model=model, x_data=x_data, y_data=y_data, y_data_target=y_data_target
    )
    
    # Get false predictions
    false_predictions_result = get_false_predictions(
        expected_val=expected_val,
        predicted_val=predicted_val,
        deviation_val=deviation_val,
        x_data=x_data,
        f_data=f_data,
        accepted_deviation=ACCEPTED_DEVIATION
    )
    
    # Calculate and print accuracy
    accuracy = (1 - len(false_predictions_result['dev']) / len(y_data)) * 100.0
    print(f"\nAccuracy: {accuracy:.2f}%")
    print(f"Total Images: {len(y_data)}")
    print(f"False Predicted: {len(false_predictions_result['dev'])}")
    print(f"Accepted Deviation: ±{ACCEPTED_DEVIATION}")
    
    # Plot divergence
    title = (f"False Predicted Divergation | Images: {len(y_data)}\n"
            f"Accuracy: {accuracy:.2f}% "
            f"(False Predicted: {len(false_predictions_result['dev'])})")
    
    binned_divergence = np.bincount(
        np.array(np.round((np.abs(false_predictions_result['dev']) * 10) % 51).astype(int)),
        minlength=51
    )
    plot_divergence(binned_divergence, title)
    
    # Plot false predictions
    if len(false_predictions_result['img']) > 0:
        print("\nFalse Predictions (First 49 images):")
        plot_dataset_analog_result(
            false_predictions_result["img"],
            false_predictions_result["lbl"],
            columns=7, rows=7, figsize=(18, 18)
        )
    
    # Save false predictions to CSV
    csv_dir = f"{OUTPUT_DIR}/training_details/{TFLITE_MAINTYPE}_{tflite_version}_{TFLITE_SIZE}/"
    Path(csv_dir).mkdir(parents=True, exist_ok=True)
    
    false_predicted_df = pd.DataFrame(
        list(zip(
            false_predictions_result["file"],
            false_predictions_result["pred"],
            false_predictions_result["exp"],
            false_predictions_result["dev"]
        )),
        columns=["File", "Predicted", "Expected", "Deviation"]
    )
    
    csv_file = f"{csv_dir}/{TFLITE_MAINTYPE}_{tflite_version}_{TFLITE_SIZE}_false_predicted.csv"
    false_predicted_df.to_csv(csv_file, index=True, index_label="Index")
    print(f"\nFalse predictions saved to: {csv_file}")
    
    print(f"\n{'='*80}")
    print("STEP 6: Saving Models")
    print(f"{'='*80}")
    
    # Save regular TFLite model
    filename = f"{OUTPUT_DIR}/{TFLITE_MAINTYPE}_{tflite_version}_{TFLITE_SIZE}. tflite"
    converter = tf.lite.TFLiteConverter.from_keras_model(model)
    tflite_model = converter.convert()
    
    with open(filename, "wb") as f:
        f.write(tflite_model)
    
    print(f"Model saved:  {filename}")
    print(f"File size: {Path(filename).stat().st_size: ,} bytes")
    
    # Save quantized TFLite model
    filename_q = f"{OUTPUT_DIR}/{TFLITE_MAINTYPE}_{tflite_version}_{TFLITE_SIZE}_q.tflite"
    
    def representative_dataset():
        for n in range(x_data.shape[0]):
            data = np.expand_dims(x_data[n], axis=0)
            yield [data. astype(np.float32)]
    
    converter_q = tf.lite.TFLiteConverter.from_keras_model(model)
    converter_q.optimizations = [tf.lite.Optimize.DEFAULT]
    converter_q.representative_dataset = representative_dataset
    converter_q.target_spec.supported_ops = [tf. lite.OpsSet.TFLITE_BUILTINS_INT8]
    converter_q._experimental_disable_per_channel_quantization_for_dense_layers = True
    
    tflite_quant_model = converter_q. convert()
    
    with open(filename_q, "wb") as f:
        f.write(tflite_quant_model)
    
    print(f"Quantized model saved:  {filename_q}")
    print(f"File size: {Path(filename_q).stat().st_size:,} bytes")
    
    print(f"\n{'='*80}")
    print("Training Complete!")
    print(f"{'='*80}")


if __name__ == "__main__":
    main()