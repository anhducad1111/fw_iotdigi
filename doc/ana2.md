# Anomaly Detection & Data Processing Diagrams

## 1. Digit Sequence Validation (Odometer Logic)

This flowchart illustrates how the system resolves ambiguous "NaN" (half-state) digits by analyzing the status of the lower-significance digit (N-1).

```mermaid
graph TD
    Start([Raw Digit Analysis]) --> CheckNaN{Is Digit N 'NaN'<br/>or Ambiguous?}

    CheckNaN -- No --> Valid[Use Classified Value]

    CheckNaN -- Yes --> LookLower[Analyze Digit N-1]
    LookLower --> CheckLowerVal{Value of N-1?}

    CheckLowerVal -- ">= 5 (Late Transition)" --> RoundUp[Round N to X + 1]
    CheckLowerVal -- "< 5 (Early Transition)" --> RoundDown[Round N to X]

    RoundUp --> Finalize[Set Final Value]
    RoundDown --> Finalize
    Valid --> Finalize
```

## 2. Rule-Based Anomaly Detection Pipeline

This sequence details the strict physical validation rules applied to every new reading to prevent billing errors.

```mermaid
sequenceDiagram
    participant Process as ClassFlowPostProcessing
    participant Logic as Validation Logic
    participant File as prevalue.ini

    Process->>Logic: Validate(NewValue, Timestamp)
    activate Logic

    Logic->>File: Load PreValue (Last Good Reading)
    File-->>Logic: PreValue

    rect rgb(255, 240, 240)
        Note right of Logic: Check 1: Negative Rate
        alt NewValue < PreValue
            Logic-->>Process: REJECT (Error: Negative Rate)
        end
    end

    rect rgb(255, 250, 240)
        Note right of Logic: Check 2: Max Flow Rate
        Logic->>Logic: Calc FlowRate = (New - Pre) / TimeDelta
        alt FlowRate > MaxRateValue
            Logic-->>Process: REJECT (Error: Rate too high)
        end
    end

    alt Checks Passed
        Logic->>File: Save NewValue as PreValue
        Logic-->>Process: ACCEPT (Valid Reading)
    end
    deactivate Logic
```

## 3. Temporal Consistency & Imputation

This state diagram shows the system's lifecycle for maintaining data integrity and handling persistent read failures.

```mermaid
stateDiagram-v2
    [*] --> Idle

    Idle --> Reading: New Image Captured
    Reading --> Validation: Digits Recognized

    state Validation {
        CheckConsistency --> CheckNulls: Consistency OK
        CheckConsistency --> Error: Negative/MaxRate Fail

        CheckNulls --> Success: No Nulls
        CheckNulls --> Imputation: Has Null/NaN Digits
    }

    state Imputation {
        Estimate --> UseExpected: Use PreValue + AvgFlow
        UseExpected --> FlagEstimate: Mark as "Estimated"
    }

    Error --> Idle: Discard Reading
    Success --> UpdatePreValue: Valid Real Data
    FlagEstimate --> UpdatePreValue: Validated Estimate

    UpdatePreValue --> Idle: Persist to SD Card
```
