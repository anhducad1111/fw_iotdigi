---
trigger: always_on
glob: "**/*"
description: Rules for building the project
---

# Build Process Rules

1.  **Output Logging**: When running any build command (e.g., `pio run`, `make`, etc.), you MUST pipe both standard output and standard error to a file named `build_log.txt`.
    *   Example: `pio run > build_log.txt 2>&1`
    *   **DO NOT** run the build command without piping to this file.

2.  **Reading Results**:
    *   **NEVER** attempt to read the build output directly from the terminal or the `run_command` output. The build process is long, and the output will likely be truncated or unavailable.
    *   **DO NOT** read `build_log.txt` while the build is running.
    *   **DO NOT** automatically read `build_log.txt` immediately after the build completes.
    *   **ALWAYS** wait for explicit user permission or instruction before reading `build_log.txt`.
