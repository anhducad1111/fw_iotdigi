# Regions Configuration Documentation

## Overview

The `assets/regions.json` file is a configuration file that defines the regions of interest (ROIs) for digit recognition in the IoT digital counter system.

## File Structure

```json
{
    "regions": [
        // Array of 4 regions (for 4-digit display)
        // Each region is an array of 4 coordinate points [x, y]
    ],
    "rotation": 1  // Indicates if image rotation is applied (1 = 180° rotation)
}
```

## Regions Explanation

Each region represents one digit position in the 4-digit display.

Each region is defined by 4 coordinate points representing the corners of a rectangle:

- [x1, y1] - Top-left corner
- [x2, y2] - Top-right corner
- [x3, y3] - Bottom-right corner
- [x4, y4] - Bottom-left corner

## Current Configuration

There are 4 regions defined for a 4-digit display.

**Rotation:** 1 means the camera image is rotated 180 degrees (upside down).

**Coordinates:** In pixels relative to the camera image (640x480).

### Region Details

- **Region 1 (Digit 1):**
  - Top-left: [249, 276]
  - Top-right: [201, 276]
  - Bottom-right: [201, 200]
  - Bottom-left: [249, 200]

- **Region 2 (Digit 2):**
  - Top-left: [292, 275]
  - Top-right: [247, 275]
  - Bottom-right: [247, 202]
  - Bottom-left: [292, 202]

- **Region 3 (Digit 3):**
  - Top-left: [343, 279]
  - Top-right: [293, 279]
  - Bottom-right: [293, 199]
  - Bottom-left: [343, 199]

- **Region 4 (Digit 4):**
  - Top-left: [387, 275]
  - Top-right: [339, 275]
  - Bottom-right: [339, 198]
  - Bottom-left: [387, 198]