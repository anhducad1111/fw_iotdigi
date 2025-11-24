---
trigger: always_on
description: Rules for building the project
---

# Build Process Rules

## 1. Output Logging
All build commands (e.g., `pio run`, `make`, etc.) must pipe both standard output and standard error into a file named `build_log.txt`.

Example:
pio run -s > build_log.txt 2>&1
Do not run any build command without redirecting output to this file.

## 2. Reading Results
- Never read build output directly from the terminal or from any command execution result.
- Do not read `build_log.txt` while the build is running.
- Do not automatically read `build_log.txt` after a build completes.
- Only read `build_log.txt` when explicitly instructed by the user.

## 3. Terminal Command Restrictions
- All terminal commands must be provided strictly as plain copyable text.
- No command may be executed automatically by the agent.
- The user is solely responsible for running all terminal commands.