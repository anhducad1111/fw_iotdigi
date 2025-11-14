import tensorflow as tf
import numpy as np

# Load model
interpreter = tf.lite.Interpreter(model_path="dig-class11_2000_s2_q.tflite")
interpreter.allocate_tensors()

# Lấy input/output details
input_details = interpreter.get_input_details()
output_details = interpreter.get_output_details()

print("=" * 60)
print("=== INPUT DETAILS ===")
print("=" * 60)
for i, inp in enumerate(input_details):
    print(f"\nInput {i}:")
    print(f"  Name: {inp['name']}")
    print(f"  Shape: {inp['shape']}")
    print(f"  Type: {inp['dtype']}")
    print(f"  Index: {inp['index']}")
    print(f"  Quantization: {inp['quantization']}")
    quant = inp['quantization']
    print(f"    - Scale: {quant[0]}")
    print(f"    - Zero Point: {quant[1]}")

print("\n" + "=" * 60)
print("=== OUTPUT DETAILS ===")
print("=" * 60)
for i, out in enumerate(output_details):
    print(f"\nOutput {i}:")
    print(f"  Name: {out['name']}")
    print(f"  Shape: {out['shape']}")
    print(f"  Type: {out['dtype']}")
    print(f"  Index: {out['index']}")
    print(f"  Quantization: {out['quantization']}")
    quant = out['quantization']
    print(f"    - Scale: {quant[0]}")
    print(f"    - Zero Point: {quant[1]}")

print("\n" + "=" * 60)
print("=== MODEL SUMMARY ===")
print("=" * 60)
tensor_details = interpreter.get_tensor_details()
print(f"Total tensors: {len(tensor_details)}")

# Test inference với input dummy
print("\n" + "=" * 60)
print("=== TEST INFERENCE ===")
print("=" * 60)
input_shape = input_details[0]['shape']
input_data = np.random.randn(*input_shape).astype(np.float32)
interpreter.set_tensor(input_details[0]['index'], input_data)
interpreter.invoke()
output_data = interpreter.get_tensor(output_details[0]['index'])

print(f"Input shape: {input_data.shape}")
print(f"Input dtype: {input_data.dtype}")
print(f"Input range: [{input_data.min():.4f}, {input_data.max():.4f}]")
print(f"\nOutput shape: {output_data.shape}")
print(f"Output dtype: {output_data.dtype}")
print(f"Output range: [{output_data.min():.4f}, {output_data.max():.4f}]")
print(f"Output values:\n{output_data}")

# Lấy số lượng classes
num_classes = output_details[0]['shape'][-1]
print(f"\nNumber of classes: {num_classes}")
print(f"\nClass probabilities (softmax):")
softmax_output = tf.nn.softmax(output_data[0]).numpy()
for i, prob in enumerate(softmax_output):
    print(f"  Class {i}: {prob:.4f}")