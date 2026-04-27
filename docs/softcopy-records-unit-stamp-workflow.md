# `softcopy-records-unit-stamp.php` Workflow Diagram

```mermaid
flowchart TD
    A[Request softcopy-records-unit-stamp.php] --> B[Load config/app.php]
    B --> C{Session active?}
    C -->|No| D[session_start()]
    C -->|Yes| E{$_SESSION user_id exists?}
    D --> E
    E -->|No| F[Redirect to auth/login.php]
    E -->|Yes| G[Read GET params: tracking_id, embedded, autoprint, stamp_type, theme]
    G --> H[Normalize inputs and apply fallbacks]
    H --> I[Build defaults: stamp label, signer, receivedBy, theme]
    I --> J[Render HTML/CSS stamp workspace]
    J --> K[Run JS IIFE]

    K --> L{stage/block/grid elements found?}
    L -->|No| M[Stop JS execution]
    L -->|Yes| N[Initialize paperSizes + state object]
    N --> O[Define utility and UI sync functions]
    O --> P[Attach event listeners]
    P --> Q[Initial boot sequence]

    Q --> Q1[syncRealtimeDateTime()]
    Q1 --> Q2[syncGrid()]
    Q2 --> Q3[setMode grid]
    Q3 --> Q4[updateBlockSize()]
    Q4 --> Q5[syncStampText()]
    Q5 --> Q6[applyPaperSize A4 -> centerStamp()]
    Q6 --> R[setInterval each 1s: syncRealtimeDateTime()]
    R --> S{autoprint=1?}
    S -->|Yes| T[After 250ms: window.print()]
    S -->|No| U[Wait for user actions]
    T --> U

    U --> V[Stamp type/sign/grid size/paper size change]
    V --> W[Sync text/grid/layout and reposition as needed]
    U --> X[Signature image upload/remove]
    X --> Y[Update signature preview/text mode]
    U --> Z[Pointer drag on stamp block]
    Z --> ZA[beginDrag -> moveDrag -> endDrag]
    ZA --> ZB[setPosition with clamp and optional grid snap]
    U --> RC[Pointer resize via handles]
    RC --> RD[beginResize -> moveResize -> endResize]
    RD --> RE[Clamp size/position + optional grid snap]
    U --> PF[Print button]
    PF --> PG[window.print()]
    U --> CS[Center Stamp button]
    CS --> CT[centerStamp + flash message]
```

