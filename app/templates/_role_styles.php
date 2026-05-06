            <style>
                .top-search { position: relative; }
                .top-search input {
                    width: min(360px, 42vw);
                    height: 42px;
                    border: 1px solid #d8e4ef;
                    border-radius: 12px;
                    background: #ffffff;
                    color: #2a4258;
                    font-size: 13px;
                    padding: 0 12px;
                }
                .top-search input:focus { outline: 2px solid var(--theme-focus, #bdd0e3); outline-offset: 1px; }
                .sr-only {
                    position: absolute;
                    width: 1px;
                    height: 1px;
                    padding: 0;
                    margin: -1px;
                    overflow: hidden;
                    clip: rect(0, 0, 0, 0);
                    white-space: nowrap;
                    border: 0;
                }
                :root,
                html[data-theme="light"],
                body[data-theme="light"] {
                    --queue-soft-surface: #f7fbff;
                    --queue-soft-surface-alt: #fbfdff;
                    --queue-soft-border: #d5e2ef;
                    --queue-soft-border-strong: #b9cce0;
                    --queue-link-surface: #eaf4ff;
                    --queue-link-surface-hover: #dcecff;
                    --queue-link-border: #b6cbe2;
                    --queue-link-border-hover: #a7c2df;
                    --queue-link-text: #205483;
                    --queue-danger-surface: color-mix(in srgb, var(--color-danger, #d92d20) 12%, #ffffff);
                    --queue-danger-border: color-mix(in srgb, var(--color-danger, #d92d20) 32%, #d6a8ac);
                    --queue-danger-text: color-mix(in srgb, var(--color-danger, #d92d20) 78%, #3f0d11);
                    --queue-row-selected-bg: color-mix(in srgb, var(--edats-accent-1, var(--theme-link, #2f7de1)) 20%, transparent);
                    --queue-row-hover-bg: color-mix(in srgb, var(--edats-accent-1, var(--theme-link, #2f7de1)) 13%, transparent);
                }
                :root[data-theme="dark"],
                html[data-theme="dark"],
                body[data-theme="dark"] {
                    --queue-soft-surface: #0a0f18;
                    --queue-soft-surface-alt: #05080f;
                    --queue-soft-border: #1a2533;
                    --queue-soft-border-strong: #243447;
                    --queue-link-surface: #0a1626;
                    --queue-link-surface-hover: #0f1f35;
                    --queue-link-border: #1b324d;
                    --queue-link-border-hover: #26466b;
                    --queue-link-text: #60a5fa;
                    --queue-danger-surface: color-mix(in srgb, var(--color-danger, #f87171) 18%, #0c0f16);
                    --queue-danger-border: color-mix(in srgb, var(--color-danger, #f87171) 34%, #4d1c21);
                    --queue-danger-text: color-mix(in srgb, var(--color-danger, #f87171) 82%, #ffd7dc);
                    --queue-row-selected-bg: color-mix(in srgb, var(--edats-accent-1, var(--theme-link, #60a5fa)) 24%, transparent);
                    --queue-row-hover-bg: color-mix(in srgb, var(--edats-accent-1, var(--theme-link, #60a5fa)) 15%, transparent);
                    
                    /* Flow specific variables */
                    --flow-bg: #030711;
                    --flow-grid: rgba(96, 165, 250, 0.04);
                    --flow-node-bg: #0f172a;
                    --flow-node-border: #1e293b;
                    --flow-active: #3b82f6;
                    --flow-completed: #10b981;
                    --flow-pending: #475569;
                }
                .action-chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
                .action-chip {
                    border: 1px solid #d7e4ef;
                    border-radius: 999px;
                    background: #f7fbff;
                    color: #35516f;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 5px 10px;
                }
                .live-flow-tracker {
                    padding: 14px;
                    background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
                    border-color: #d9e5f1;
                    box-shadow: 0 10px 26px rgba(27, 57, 87, 0.08);
                    margin-bottom: clamp(12px, 2vw, 24px);
                }
                .live-flow-head {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: space-between;
                    gap: 10px;
                    margin-bottom: 12px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #deebf7;
                }
                .live-flow-head h2 {
                    margin: 0;
                    font-size: 16px;
                    letter-spacing: 0.01em;
                    text-transform: uppercase;
                    color: #203a53;
                }
                .live-flow-head p {
                    margin: 4px 0 0;
                    color: #6f859a;
                    font-size: 12px;
                }
                .live-flow-meta {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    align-items: center;
                    justify-content: flex-end;
                    padding: 6px;
                    border: 1px solid #dbe8f4;
                    border-radius: 12px;
                    background: rgba(247, 251, 255, 0.92);
                    backdrop-filter: blur(2px);
                }
                .live-flow-pill {
                    display: inline-flex;
                    align-items: center;
                    border: 1px solid #d4e1ed;
                    border-radius: 999px;
                    background: #f8fbff;
                    color: #34516b;
                    font-size: 11px;
                    font-weight: 700;
                    padding: 5px 10px;
                    white-space: nowrap;
                }
                .live-flow-pill.is-status {
                    background: #eef5ff;
                    border-color: #caddf8;
                    color: #274f7b;
                }
                .live-flow-tools {
                    display: inline-flex;
                    gap: 6px;
                    margin-left: 6px;
                }
                .live-flow-tool-btn {
                    width: 30px;
                    height: 30px;
                    border: 1px solid #ccdae7;
                    border-radius: 8px;
                    background: linear-gradient(180deg, #ffffff 0%, #f1f7fd 100%);
                    color: #2f4f6d;
                    font-size: 16px;
                    font-weight: 700;
                    line-height: 1;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    box-shadow: 0 4px 10px rgba(41, 73, 104, 0.1);
                }
                .live-flow-tool-btn:hover {
                    background: linear-gradient(180deg, #f8fcff 0%, #eaf3fc 100%);
                    border-color: #bcd0e3;
                }
                .live-flow-tool-btn:focus-visible {
                    outline: 2px solid var(--theme-focus, #2f7de1);
                    outline-offset: 1px;
                }
                .live-flow-tool-btn.reset {
                    font-size: 11px;
                    width: auto;
                    min-width: 50px;
                    padding: 0 10px;
                }
                .live-flow-viewport {
                    position: relative;
                    height: 420px;
                    border: 1px solid #d7e2ee;
                    border-radius: 14px;
                    background:
                        radial-gradient(circle at 8% 10%, rgba(249, 252, 255, 0.98) 0%, rgba(241, 247, 253, 0.98) 50%, rgba(238, 245, 251, 0.98) 100%),
                        repeating-linear-gradient(0deg, rgba(167, 187, 208, 0.12) 0 1px, transparent 1px 28px),
                        repeating-linear-gradient(90deg, rgba(167, 187, 208, 0.12) 0 1px, transparent 1px 28px);
                    overflow: hidden;
                    cursor: grab;
                    user-select: none;
                    touch-action: none;
                }
                .live-flow-viewport::before {
                    content: 'LIVE ROUTING MAP';
                    position: absolute;
                    top: 10px;
                    left: 12px;
                    z-index: 2;
                    font-size: 10px;
                    font-weight: 800;
                    letter-spacing: 0.08em;
                    color: #49637d;
                    background: rgba(255, 255, 255, 0.74);
                    border: 1px solid #d7e3ef;
                    border-radius: 999px;
                    padding: 4px 10px;
                }
                .live-flow-viewport::after {
                    content: '';
                    position: absolute;
                    right: -120px;
                    bottom: -140px;
                    width: 340px;
                    height: 340px;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(95, 158, 228, 0.2) 0%, rgba(95, 158, 228, 0.05) 48%, transparent 70%);
                    pointer-events: none;
                }
                .live-flow-viewport.is-panning {
                    cursor: grabbing;
                }
                .live-flow-canvas {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 2200px;
                    height: 640px;
                    transform-origin: 0 0;
                    transition: box-shadow 180ms ease;
                }
                .live-flow-svg {
                    position: absolute;
                    inset: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                }
                .live-flow-line-outer {
                    fill: none;
                    stroke: #8f9fb3;
                    stroke-width: 3;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                    filter: drop-shadow(0 1px 1px rgba(66, 93, 121, 0.2));
                }
                .live-flow-line-guide {
                    fill: none;
                    stroke: #aab8c7;
                    stroke-width: 3;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                    stroke-dasharray: 1 15;
                    opacity: 0.88;
                }
                .live-flow-line-main {
                    fill: none;
                    stroke: #9ab2cc;
                    stroke-width: 4;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                    filter: drop-shadow(0 0 5px rgba(112, 156, 204, 0.22));
                }
                .live-flow-marker .marker-label {
                    font-size: 11px;
                    font-weight: 800;
                    letter-spacing: 0.08em;
                    text-anchor: middle;
                    fill: #4c6680;
                }
                .live-flow-marker .marker-halo {
                    fill: rgba(255, 255, 255, 0.86);
                    stroke: #cad9e8;
                    stroke-width: 2;
                }
                .live-flow-marker .marker-core {
                    fill: #2f7de1;
                    stroke: rgba(255, 255, 255, 0.9);
                    stroke-width: 2;
                }
                .live-flow-marker.start .marker-core {
                    fill: #3ea96f;
                }
                .live-flow-marker .marker-icon {
                    fill: #ffffff;
                }
                .live-flow-marker.end .marker-icon {
                    fill: none;
                    stroke: #ffffff;
                    stroke-width: 2;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                }
                .live-flow-marker .marker-glow {
                    fill: none;
                    stroke: rgba(80, 132, 190, 0.34);
                    stroke-width: 1.6;
                    stroke-dasharray: 3 5;
                    animation: liveFlowMarkerPulse 3.2s linear infinite;
                }
                .live-flow-step {
                    position: absolute;
                    left: var(--x);
                    top: var(--y);
                    width: var(--w, 180px);
                    min-height: var(--h, 145px);
                    border: 2px solid #cfd9e3;
                    border-radius: 18px;
                    background: linear-gradient(180deg, rgba(255, 255, 255, 0.97) 0%, rgba(244, 249, 255, 0.95) 100%);
                    box-shadow: 0 8px 24px rgba(34, 58, 82, 0.12);
                    padding: 12px 12px 10px;
                    display: flex;
                    flex-direction: column;
                    gap: 9px;
                    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease, background 180ms ease;
                }
                .live-flow-step::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 36%;
                    border-radius: 16px 16px 0 0;
                    background: linear-gradient(180deg, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0) 100%);
                    pointer-events: none;
                }
                .live-flow-step:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 14px 30px rgba(31, 56, 81, 0.18);
                }
                .live-flow-step:nth-child(1) { --x: 46px; --y: 292px; --w: 210px; --h: 170px; }
                .live-flow-step:nth-child(2) { --x: 300px; --y: 335px; --w: 190px; }
                .live-flow-step:nth-child(3) { --x: 535px; --y: 335px; --w: 190px; }
                .live-flow-step:nth-child(4) { --x: 770px; --y: 254px; --w: 198px; --h: 252px; }
                .live-flow-step:nth-child(5) { --x: 1000px; --y: 335px; --w: 190px; }
                .live-flow-step:nth-child(6) { --x: 1230px; --y: 335px; --w: 190px; }
                .live-flow-step:nth-child(7) { --x: 1460px; --y: 335px; --w: 190px; }
                .live-flow-step:nth-child(8) { --x: 1690px; --y: 335px; --w: 190px; }
                .live-flow-step:nth-child(9) { --x: 1920px; --y: 335px; --w: 210px; }
                .live-flow-step-head {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                    position: relative;
                    z-index: 1;
                }
                .live-flow-step-dot {
                    width: 14px;
                    height: 14px;
                    border-radius: 50%;
                    border: 2px solid #c6d3df;
                    background: #e9eff6;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }
                .live-flow-step-dot::after {
                    content: '';
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    background: #98aabd;
                }
                .live-flow-step-glyph {
                    width: 30px;
                    height: 30px;
                    border-radius: 9px;
                    border: 1px solid #c8d7e5;
                    background: linear-gradient(180deg, #ffffff 0%, #eef4fb 100%);
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    color: #45617d;
                    box-shadow: 0 2px 6px rgba(47, 79, 109, 0.14);
                }
                .live-flow-step-glyph svg {
                    width: 17px;
                    height: 17px;
                    stroke: currentColor;
                    fill: none;
                    stroke-width: 2;
                    stroke-linecap: round;
                    stroke-linejoin: round;
                }
                .live-flow-step-glyph svg.fill-icon {
                    fill: currentColor;
                    stroke: none;
                }
                .live-flow-step-title {
                    margin: 0;
                    font-size: 18px;
                    line-height: 1.05;
                    letter-spacing: 0.01em;
                    color: #1d3349;
                    font-weight: 800;
                    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.7);
                    position: relative;
                    z-index: 1;
                }
                .live-flow-step-state {
                    margin-top: auto;
                    display: inline-flex;
                    width: fit-content;
                    border-radius: 999px;
                    background: #d3d9df;
                    color: #445566;
                    font-size: 12px;
                    font-weight: 700;
                    padding: 5px 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.03em;
                }
                .live-flow-spinner {
                    display: none;
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    border: 4px dotted #9ec0eb;
                    border-top-color: #2f7de1;
                    animation: liveFlowSpin 1.1s linear infinite;
                }
                .live-flow-step.is-completed {
                    border-color: #71bf95;
                    background: linear-gradient(180deg, #e3f6eb 0%, #f3fbf7 100%);
                    box-shadow: 0 10px 24px rgba(63, 160, 110, 0.2);
                }
                .live-flow-step.is-completed .live-flow-step-dot {
                    border-color: #43aa72;
                    background: #43aa72;
                }
                .live-flow-step.is-completed .live-flow-step-glyph {
                    border-color: #5fbd8a;
                    background: linear-gradient(180deg, #f2fbf6 0%, #def3e7 100%);
                    color: #2c8b58;
                }
                .live-flow-step.is-completed .live-flow-step-dot::after {
                    width: 8px;
                    height: 4px;
                    border-radius: 0;
                    background: transparent;
                    border-left: 2px solid #ffffff;
                    border-bottom: 2px solid #ffffff;
                    transform: rotate(-45deg);
                }
                .live-flow-step.is-completed .live-flow-step-state {
                    background: #43aa72;
                    color: #ffffff;
                }
                .live-flow-step.is-active {
                    border-color: #7fafea;
                    background: linear-gradient(180deg, #ecf4ff 0%, #f3f8ff 100%);
                    box-shadow: 0 10px 24px rgba(61, 126, 206, 0.25);
                }
                .live-flow-step.is-active .live-flow-step-dot {
                    border-color: #2f7de1;
                    background: #e9f1ff;
                }
                .live-flow-step.is-active .live-flow-step-glyph {
                    border-color: #8ab5ef;
                    background: linear-gradient(180deg, #f7fbff 0%, #e2efff 100%);
                    color: #2c70c5;
                }
                .live-flow-step.is-active .live-flow-step-dot::after {
                    background: #2f7de1;
                }
                .live-flow-step.is-active .live-flow-step-state {
                    background: #2f7de1;
                    color: #ffffff;
                }
                .live-flow-step.is-active .live-flow-spinner {
                    display: inline-block;
                }
                .live-flow-step.is-pending {
                    border-color: #cfd8e2;
                    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
                }
                .live-flow-step.is-pending .live-flow-step-glyph {
                    border-color: #cad5e0;
                    background: linear-gradient(180deg, #f7fafc 0%, #edf2f7 100%);
                    color: #5b738b;
                }
                .live-flow-step:not(.is-active) .live-flow-spinner {
                    display: none;
                }
                .live-flow-legend {
                    margin-top: 10px;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 10px;
                    align-items: center;
                    justify-content: flex-end;
                    color: #4c657e;
                    font-size: 12px;
                    border-top: 1px solid #deebf7;
                    padding-top: 10px;
                }
                .live-flow-legend span {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 4px 9px;
                    border-radius: 999px;
                    background: #f4f9ff;
                    border: 1px solid #dae7f4;
                }
                .live-flow-legend i {
                    width: 11px;
                    height: 11px;
                    border-radius: 50%;
                    display: inline-block;
                }
                .live-flow-legend i.is-completed { background: #43aa72; }
                .live-flow-legend i.is-active { background: #2f7de1; }
                .live-flow-legend i.is-pending { background: #c0c8d2; }
                @keyframes liveFlowSpin {
                    to { transform: rotate(360deg); }
                }
                @keyframes liveFlowMarkerPulse {
                    0% { transform: scale(0.92); opacity: 0.45; }
                    50% { transform: scale(1.05); opacity: 0.9; }
                    100% { transform: scale(0.92); opacity: 0.45; }
                }
                .stat-card.stat-card-clickable {
                    cursor: pointer;
                    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
                }
                .stat-card.stat-card-clickable:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 12px 28px rgba(18, 57, 94, 0.12);
                    border-color: rgba(47, 125, 225, 0.25);
                }
                .stat-card.stat-card-clickable:focus-visible {
                    outline: 2px solid var(--theme-focus, #2f7de1);
                    outline-offset: 2px;
                }
                .table-panel-intro {
                    margin: 0 0 10px;
                    color: var(--muted);
                    font-size: 12px;
                }
                .table-panel-controls {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    align-items: center;
                    margin: 0 0 10px;
                }
                .table-selected-count {
                    margin-left: auto;
                    font-size: 12px;
                    color: #58718a;
                    font-weight: 600;
                }
                .bulk-select-col {
                    width: 44px;
                    min-width: 44px;
                    text-align: center;
                }
                .bulk-select-col input[type="checkbox"] {
                    width: 14px;
                    height: 14px;
                    cursor: pointer;
                }
                .table-panel-controls .sticky-search {
                    flex: 1 1 320px;
                    max-width: 520px;
                }
                .table-panel-controls .sticky-search input {
                    width: 100%;
                }
                .table-panel-controls .status-filter-select {
                    min-width: 220px;
                    width: 100%;
                    height: 38px;
                }
                .queue-live-status {
                    color: #3f5f7a;
                }
                .queue-live-status.error {
                    color: #b42318;
                }
                .queue-live-meta {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px 12px;
                    margin: 0 0 10px;
                }
                .queue-live-meta .queue-live-status {
                    margin: 0;
                }
                .queue-live-indicator {
                    display: inline-flex;
                    align-items: center;
                    border-radius: 999px;
                    border: 1px solid transparent;
                    padding: 4px 10px;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.01em;
                    white-space: nowrap;
                }
                .queue-live-indicator.is-pending {
                    color: #3f5f7a;
                    border-color: #c7d9ea;
                    background: #eef4fa;
                }
                .queue-live-indicator.is-internet {
                    color: #0a5b2e;
                    border-color: #a7dfbc;
                    background: #eaf8ef;
                }
                .queue-live-indicator.is-localhost {
                    color: #0e4a6e;
                    border-color: #9ed3f3;
                    background: #e7f4fc;
                }
                .queue-live-indicator.is-cache {
                    color: #7a4d02;
                    border-color: #f3d184;
                    background: #fff4dd;
                }
                .queue-live-indicator.is-offline {
                    color: #8f1d2c;
                    border-color: #f1b5bd;
                    background: #fdecef;
                }
                .page-sticky-actions {
                    position: static;
                    z-index: 1;
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    width: 100%;
                    margin: 2px 0 14px;
                    justify-content: flex-end;
                }
                .page-sticky-actions .sticky-status-filter {
                    margin-left: 0;
                    margin-right: auto;
                }
                .page-sticky-actions .sticky-search {
                    flex: 1 1 320px;
                    max-width: 520px;
                }
                .page-sticky-actions .sticky-search input {
                    width: 100%;
                }
                .page-sticky-actions .status-filter-select {
                    min-width: 240px;
                    height: 38px;
                }
                .page-sticky-actions button {
                    border: none;
                    border-radius: 10px;
                    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
                    color: #fff;
                    font-size: 12px;
                    font-weight: 700;
                    padding: 8px 12px;
                    cursor: pointer;
                }
                .qr-receive-panel {
                    display: grid;
                    gap: 10px;
                    margin: 10px 0 14px;
                    padding: 12px;
                    border: 1px solid #d6e4f0;
                    border-radius: 12px;
                    background: #f7fbff;
                }
                .qr-receive-head h3 {
                    margin: 0;
                    font-size: 14px;
                    color: #20415f;
                }
                .qr-receive-head p {
                    margin: 4px 0 0;
                    font-size: 12px;
                    color: #58718a;
                }
                .qr-receive-row {
                    display: grid;
                    gap: 8px;
                    grid-template-columns: minmax(240px, 1fr) auto auto auto;
                    align-items: center;
                }
                .qr-receive-input {
                    height: 38px;
                    border: 1px solid #c6d9ea;
                    border-radius: 10px;
                    padding: 0 10px;
                    font-size: 13px;
                    color: #23374a;
                    background: #fff;
                }
                .qr-receive-btn {
                    height: 38px;
                    border: 0;
                    border-radius: 10px;
                    padding: 0 12px;
                    font-size: 12px;
                    font-weight: 700;
                    cursor: pointer;
                    color: #fff;
                    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
                }
                .qr-receive-btn.secondary {
                    background: color-mix(in srgb, var(--semantic-info, #3ba7ff) 45%, #2a3340);
                }
                .qr-receive-btn.danger {
                    background: var(--semantic-danger, #c23b3b);
                }
                .qr-receive-btn:disabled {
                    opacity: .65;
                    cursor: not-allowed;
                }
                .qr-receive-viewport {
                    position: relative;
                    width: 100%;
                    max-width: 420px;
                    border: 1px solid #c6d9ea;
                    border-radius: 10px;
                    overflow: hidden;
                    background: #0f1720;
                    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
                }
                .qr-receive-video {
                    width: 100%;
                    aspect-ratio: 4 / 3;
                    object-fit: cover;
                    display: block;
                    background: #0f1720;
                }
                .qr-receive-overlay {
                    position: absolute;
                    inset: 0;
                    pointer-events: none;
                    --frame-size: 62%;
                    --frame-top: calc((100% - var(--frame-size)) / 2);
                    --frame-left: calc((100% - var(--frame-size)) / 2);
                }
                .qr-mask {
                    position: absolute;
                    background: rgba(5, 11, 18, 0.5);
                    backdrop-filter: blur(1.5px);
                }
                .qr-mask.top {
                    top: 0;
                    left: 0;
                    right: 0;
                    height: var(--frame-top);
                }
                .qr-mask.bottom {
                    left: 0;
                    right: 0;
                    bottom: 0;
                    height: var(--frame-top);
                }
                .qr-mask.left {
                    top: var(--frame-top);
                    bottom: var(--frame-top);
                    left: 0;
                    width: var(--frame-left);
                }
                .qr-mask.right {
                    top: var(--frame-top);
                    bottom: var(--frame-top);
                    right: 0;
                    width: var(--frame-left);
                }
                .qr-focus-frame {
                    position: absolute;
                    top: var(--frame-top);
                    left: var(--frame-left);
                    width: var(--frame-size);
                    height: var(--frame-size);
                    border: 1px solid rgba(255, 255, 255, 0.35);
                    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.22) inset;
                }
                .qr-corner {
                    position: absolute;
                    width: 22px;
                    height: 22px;
                    border-color: #64c9ff;
                    border-style: solid;
                    border-width: 0;
                    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.35));
                }
                .qr-corner.tl {
                    top: -2px;
                    left: -2px;
                    border-top-width: 3px;
                    border-left-width: 3px;
                }
                .qr-corner.tr {
                    top: -2px;
                    right: -2px;
                    border-top-width: 3px;
                    border-right-width: 3px;
                }
                .qr-corner.bl {
                    bottom: -2px;
                    left: -2px;
                    border-bottom-width: 3px;
                    border-left-width: 3px;
                }
                .qr-corner.br {
                    bottom: -2px;
                    right: -2px;
                    border-bottom-width: 3px;
                    border-right-width: 3px;
                }
                .qr-focus-hint {
                    position: absolute;
                    left: 50%;
                    bottom: 12px;
                    transform: translateX(-50%);
                    margin: 0;
                    padding: 4px 8px;
                    border-radius: 999px;
                    font-size: 11px;
                    font-weight: 600;
                    letter-spacing: 0.15px;
                    color: #eef7ff;
                    background: rgba(7, 18, 31, 0.58);
                    text-align: center;
                    white-space: nowrap;
                    text-shadow: 0 1px 1px rgba(0, 0, 0, 0.35);
                }
                .qr-receive-status {
                    margin: 0;
                    font-size: 12px;
                    color: #3f5f7a;
                }
                .qr-receive-status.error {
                    color: #b42318;
                }
                .document-tools {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    margin: 10px 0 12px;
                    padding: 10px 12px;
                    border: 1px solid var(--queue-soft-border);
                    border-radius: 12px;
                    background: var(--queue-soft-surface);
                    color: var(--text);
                }
                .document-tools[hidden] {
                    display: none !important;
                }
                .document-tools-meta {
                    margin: 0;
                    font-size: 12px;
                    color: var(--muted);
                }
                .document-tools-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .document-tools-btn {
                    border: 1px solid var(--queue-link-border);
                    border-radius: 10px;
                    padding: 8px 12px;
                    font-size: 12px;
                    font-weight: 700;
                    color: var(--queue-link-text);
                    background: var(--queue-link-surface);
                    cursor: pointer;
                }
                .document-tools-btn:hover {
                    background: var(--queue-link-surface-hover);
                    border-color: var(--queue-link-border-hover);
                }
                .document-tools-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                .action-modal-dialog.details-modal-dialog {
                    width: min(1000px, 96vw);
                    max-height: calc(100vh - 44px);
                    display: flex;
                    flex-direction: column;
                }
                .edit-intake-modal-dialog {
                    width: min(1120px, 100%);
                }
                .details-modal-form {
                    gap: 14px;
                    max-height: calc(100vh - 64px);
                    overflow-y: auto;
                    overflow-x: hidden;
                }
                .edit-intake-fields-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 12px 16px;
                    align-items: start;
                }
                .edit-intake-field-block {
                    display: grid;
                    gap: 8px;
                    align-content: start;
                }
                .edit-intake-field-wide {
                    grid-column: 1 / -1;
                    max-width: 50%;
                }
                .details-modal-content {
                    display: grid;
                    gap: 12px;
                    min-height: 200px;
                }
                .details-loading {
                    margin: 0;
                    border: 1px dashed var(--queue-soft-border-strong);
                    border-radius: 10px;
                    background: var(--queue-soft-surface);
                    color: var(--text);
                    font-size: 13px;
                    padding: 12px;
                }
                .details-loading.error {
                    border-color: var(--queue-danger-border);
                    background: var(--queue-danger-surface);
                    color: var(--queue-danger-text);
                }
                .details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 10px;
                }
                .details-item {
                    border: 1px solid var(--queue-soft-border);
                    border-radius: 10px;
                    padding: 8px 10px;
                    background: var(--queue-soft-surface-alt);
                }
                .details-item-label {
                    display: block;
                    margin: 0 0 4px;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.04em;
                    color: var(--muted);
                    text-transform: uppercase;
                }
                .details-item-value {
                    margin: 0;
                    font-size: 14px;
                    color: var(--text);
                    white-space: pre-wrap;
                    word-break: break-word;
                }
                .details-item-full {
                    grid-column: 1 / -1;
                }
                .details-attachments {
                    border: 1px solid var(--queue-soft-border);
                    border-radius: 12px;
                    padding: 10px;
                    background: var(--queue-soft-surface);
                    display: grid;
                    gap: 8px;
                }
                .details-attachments-head {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    align-items: center;
                    justify-content: space-between;
                }
                .details-attachments-head h4 {
                    margin: 0;
                    font-size: 14px;
                    color: var(--text);
                }
                .details-attachment-select {
                    width: 100%;
                    border: 1px solid var(--queue-soft-border-strong);
                    border-radius: 10px;
                    padding: 8px 10px;
                    font-size: 13px;
                    color: var(--text);
                    background: var(--surface);
                }
                .details-attachment-filter {
                    display: grid;
                    gap: 8px;
                    margin-bottom: 2px;
                }
                .details-attachment-filter[hidden] {
                    display: none !important;
                }
                .details-attachment-filter-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .details-attachment-filter-btn {
                    border: 1px solid var(--queue-soft-border-strong);
                    border-radius: 999px;
                    background: var(--queue-soft-surface);
                    color: var(--text);
                    font-size: 12px;
                    font-weight: 700;
                    padding: 6px 12px;
                    cursor: pointer;
                }
                .details-attachment-filter-btn:hover {
                    background: var(--queue-link-surface);
                    border-color: var(--queue-link-border);
                }
                .details-attachment-filter-btn.is-active {
                    background: var(--theme-link);
                    border-color: var(--theme-link);
                    color: #ffffff;
                }
                .details-attachment-filter-btn:disabled {
                    opacity: .6;
                    cursor: not-allowed;
                }
                .details-open-file {
                    border: 1px solid var(--queue-link-border);
                    border-radius: 9px;
                    padding: 7px 11px;
                    font-size: 12px;
                    font-weight: 700;
                    text-decoration: none;
                    color: var(--queue-link-text);
                    background: var(--queue-link-surface);
                }
                .details-open-file:hover {
                    background: var(--queue-link-surface-hover);
                    border-color: var(--queue-link-border-hover);
                }
                .details-open-file[hidden] {
                    display: none !important;
                }
                .details-preview {
                    border: 1px solid var(--queue-soft-border-strong);
                    border-radius: 10px;
                    background: var(--surface);
                    min-height: 320px;
                    max-height: min(56vh, 540px);
                    overflow: auto;
                    display: grid;
                    place-items: center;
                    padding: 8px;
                }
                .details-preview iframe,
                .details-preview img {
                    width: 100%;
                    max-width: 100%;
                    border: 0;
                }
                .details-preview iframe {
                    min-height: 300px;
                    height: min(54vh, 500px);
                }
                .details-preview img {
                    width: auto;
                    max-height: min(52vh, 500px);
                    object-fit: contain;
                }
                .details-preview-empty {
                    margin: 0;
                    font-size: 13px;
                    color: var(--muted);
                    text-align: center;
                }
                .edit-intake-attachments-list {
                    margin: 0;
                    padding-left: 18px;
                    display: grid;
                    gap: 6px;
                    color: var(--text);
                    font-size: 12px;
                }
                .route-existing-attachments {
                    margin: 0;
                    padding-left: 18px;
                    display: grid;
                    gap: 6px;
                    color: var(--text);
                    font-size: 12px;
                }
                .route-existing-attachments li {
                    line-height: 1.35;
                }
                .route-existing-attachment-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    width: 100%;
                }
                .route-existing-attachment-text {
                    min-width: 0;
                    flex: 1 1 auto;
                    overflow-wrap: anywhere;
                }
                .route-existing-attachment-delete {
                    appearance: none;
                    border: 1px solid rgba(255, 255, 255, 0.16);
                    background: rgba(255, 255, 255, 0.06);
                    color: var(--danger, #f28b82);
                    border-radius: 999px;
                    padding: 4px 10px;
                    font-size: 11px;
                    font-weight: 700;
                    cursor: pointer;
                    flex: 0 0 auto;
                }
                .route-existing-attachment-delete:disabled {
                    opacity: 0.6;
                    cursor: wait;
                }
                .route-existing-attachments a {
                    color: var(--queue-link-text);
                    text-decoration: underline;
                }
                .route-destination-filter {
                    display: grid;
                    gap: 8px;
                    margin-bottom: 2px;
                }
                .route-destination-filter[hidden] {
                    display: none !important;
                }
                .route-destination-filter-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .route-destination-filter-btn {
                    border: 1px solid var(--queue-soft-border-strong);
                    border-radius: 999px;
                    background: var(--queue-soft-surface);
                    color: var(--text);
                    font-size: 12px;
                    font-weight: 700;
                    padding: 6px 12px;
                    cursor: pointer;
                }
                .route-destination-filter-btn:hover {
                    background: var(--queue-link-surface);
                    border-color: var(--queue-link-border);
                }
                .route-destination-filter-btn.is-active {
                    background: var(--theme-link);
                    border-color: var(--theme-link);
                    color: #ffffff;
                }
                .edit-intake-attachment-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 8px;
                }
                .edit-intake-attachment-name {
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                .edit-intake-attachment-delete {
                    border: 1px solid var(--queue-danger-border);
                    border-radius: 8px;
                    background: var(--queue-danger-surface);
                    color: var(--queue-danger-text);
                    font-size: 11px;
                    font-weight: 700;
                    padding: 4px 8px;
                    cursor: pointer;
                }
                .edit-intake-attachment-delete:disabled {
                    opacity: .6;
                    cursor: not-allowed;
                }
                .queue-row-selectable {
                    cursor: pointer;
                }
                .queue-row-hover-live-flow td {
                    background: var(--queue-row-hover-bg);
                }
                .queue-row-selected td {
                    background: var(--queue-row-selected-bg);
                }
                .queue-flow-hover-popover {
                    position: fixed;
                    z-index: 10012;
                    pointer-events: none;
                    width: min(470px, calc(100vw - 24px));
                    border-radius: 14px;
                    border: 1px solid #cfe0ef;
                    background: rgba(255, 255, 255, 0.98);
                    box-shadow: 0 14px 34px rgba(20, 45, 72, 0.22);
                    backdrop-filter: blur(4px);
                    -webkit-backdrop-filter: blur(4px);
                    padding: 12px 12px 11px;
                    opacity: 0;
                    transform: translateY(6px) scale(0.985);
                    transition: opacity 140ms ease, transform 140ms ease;
                }
                .queue-flow-hover-popover.is-visible {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
                .queue-flow-hover-title {
                    margin: 0;
                    font-size: 12px;
                    font-weight: 800;
                    letter-spacing: 0.045em;
                    text-transform: uppercase;
                    color: #1f3a57;
                }
                .queue-flow-hover-stage {
                    margin: 4px 0 0;
                    font-size: 12px;
                    color: #365474;
                    font-weight: 700;
                }
                .queue-flow-hover-meta {
                    margin-top: 8px;
                    display: grid;
                    gap: 4px;
                    padding: 8px;
                    border: 1px solid #d7e5f2;
                    border-radius: 10px;
                    background: #f7fbff;
                }
                .queue-flow-hover-meta-item {
                    font-size: 11px;
                    color: #415d7b;
                    line-height: 1.35;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .queue-flow-hover-track {
                    margin-top: 9px;
                    position: relative;
                    display: grid;
                    gap: 5px;
                    align-items: center;
                }
                .queue-flow-hover-track::before {
                    content: '';
                    position: absolute;
                    left: 16px;
                    right: 16px;
                    top: 50%;
                    height: 2px;
                    transform: translateY(-50%);
                    background: #d2dce7;
                    border-radius: 999px;
                }
                .queue-flow-hover-step {
                    position: relative;
                    z-index: 1;
                    width: 22px;
                    height: 22px;
                    border-radius: 999px;
                    margin: 0 auto;
                    border: 2px solid #c7d4e0;
                    background: #edf3f8;
                    color: #5f768f;
                    font-size: 11px;
                    font-weight: 800;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 1px 5px rgba(31, 56, 81, 0.16);
                }
                .queue-flow-hover-step.is-completed {
                    border-color: #3ea96f;
                    background: #3ea96f;
                    color: #ffffff;
                }
                .queue-flow-hover-step.is-active {
                    border-color: #2f7de1;
                    background: #2f7de1;
                    color: #ffffff;
                    box-shadow: 0 0 0 4px rgba(47, 125, 225, 0.2);
                }
                .queue-flow-hover-step.is-pending {
                    border-color: #c7d4e0;
                    background: #edf3f8;
                    color: #5f768f;
                }
                body[data-theme="dark"] .queue-flow-hover-popover {
                    border-color: #2f4053;
                    background: rgba(16, 24, 36, 0.96);
                    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.52);
                }
                body[data-theme="dark"] .queue-flow-hover-title {
                    color: #dce8f5;
                }
                body[data-theme="dark"] .queue-flow-hover-stage {
                    color: #8fb6e2;
                }
                body[data-theme="dark"] .queue-flow-hover-meta {
                    border-color: #2b3b4f;
                    background: #162334;
                }
                body[data-theme="dark"] .queue-flow-hover-meta-item {
                    color: #bbcedf;
                }
                body[data-theme="dark"] .queue-flow-hover-track::before {
                    background: #3a4c61;
                }
                body[data-theme="dark"] .queue-flow-hover-step {
                    border-color: #425a73;
                    background: #213349;
                    color: #c2d6ea;
                }
                body[data-theme="dark"] .queue-flow-hover-step.is-completed {
                    border-color: #34b676;
                    background: #34b676;
                    color: #ffffff;
                    box-shadow: 0 0 0 4px rgba(52, 182, 118, 0.2);
                }
                body[data-theme="dark"] .queue-flow-hover-step.is-active {
                    border-color: #2f7de1;
                    background: #2f7de1;
                    color: #ffffff;
                    box-shadow: 0 0 0 4px rgba(47, 125, 225, 0.25);
                }
                body[data-theme="dark"] .queue-flow-hover-step.is-pending {
                    border-color: #425a73;
                    background: #213349;
                    color: #c2d6ea;
                }

                body[data-theme="dark"] .top-search input,
                body[data-theme="dark"] .table-panel-controls .status-filter-select,
                body[data-theme="dark"] .qr-receive-input,
                body[data-theme="dark"] .details-attachment-select {
                    background: #0f1b2a;
                    border-color: #36506a;
                    color: var(--text);
                }

                body[data-theme="dark"] .action-chip,
                body[data-theme="dark"] .live-flow-pill,
                body[data-theme="dark"] .live-flow-meta,
                body[data-theme="dark"] .qr-receive-panel,
                body[data-theme="dark"] .document-tools {
                    background: #1c2d40;
                    border-color: var(--line);
                    color: var(--text);
                }

                body[data-theme="dark"] .live-flow-pill.is-status {
                    background: rgba(59, 130, 246, 0.1);
                    border-color: rgba(59, 130, 246, 0.2);
                    color: #60a5fa;
                }

                body[data-theme="dark"] .live-flow-tracker {
                    background: #0f172a;
                    border-color: #1e293b;
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
                }

                body[data-theme="dark"] .live-flow-head {
                    border-bottom-color: #1e293b;
                }

                body[data-theme="dark"] .live-flow-meta {
                    background: rgba(15, 23, 42, 0.8);
                    border-color: #1e293b;
                    backdrop-filter: blur(8px);
                }

                body[data-theme="dark"] .live-flow-pill {
                    background: #1e293b;
                    border-color: #334155;
                    color: #94a3b8;
                }

                body[data-theme="dark"] .live-flow-pill.is-status {
                    background: rgba(59, 130, 246, 0.1);
                    border-color: rgba(59, 130, 246, 0.2);
                    color: #60a5fa;
                }

                body[data-theme="dark"] .live-flow-viewport {
                    border-color: #1e293b;
                    background-color: var(--flow-bg);
                    background-image: 
                        radial-gradient(circle at 2px 2px, var(--flow-grid) 1px, transparent 0);
                    background-size: 28px 28px;
                }

                body[data-theme="dark"] .live-flow-viewport::before {
                    background: rgba(15, 23, 42, 0.9);
                    border-color: #334155;
                    color: #94a3b8;
                    backdrop-filter: blur(4px);
                }

                body[data-theme="dark"] .live-flow-viewport::after {
                    background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
                }

                body[data-theme="dark"] .live-flow-step {
                    background: #0f172a;
                    border-color: #1e293b;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
                    color: #f8fafc;
                }

                body[data-theme="dark"] .live-flow-step::before {
                    display: none;
                }

                body[data-theme="dark"] .live-flow-step.is-active {
                    background: rgba(59, 130, 246, 0.05);
                    border-color: #3b82f6;
                    box-shadow: 0 0 20px rgba(59, 130, 246, 0.15);
                }

                body[data-theme="dark"] .live-flow-step.is-completed {
                    background: rgba(16, 185, 129, 0.05);
                    border-color: #10b981;
                    box-shadow: 0 0 20px rgba(16, 185, 129, 0.1);
                }

                body[data-theme="dark"] .live-flow-step-dot {
                    border-color: #334155;
                    background: #1e293b;
                }

                body[data-theme="dark"] .live-flow-step.is-active .live-flow-step-dot {
                    border-color: #3b82f6;
                    background: #3b82f6;
                    box-shadow: 0 0 8px #3b82f6;
                }

                body[data-theme="dark"] .live-flow-step.is-completed .live-flow-step-dot {
                    border-color: #10b981;
                    background: #10b981;
                }

                body[data-theme="dark"] .live-flow-step-glyph {
                    background: #1e293b;
                    border-color: #334155;
                    color: #94a3b8;
                }

                body[data-theme="dark"] .live-flow-step.is-active .live-flow-step-glyph {
                    color: #60a5fa;
                    border-color: rgba(59, 130, 246, 0.3);
                    background: rgba(59, 130, 246, 0.1);
                }

                body[data-theme="dark"] .live-flow-step.is-completed .live-flow-step-glyph {
                    color: #34d399;
                    border-color: rgba(16, 185, 129, 0.3);
                    background: rgba(16, 185, 129, 0.1);
                }

                body[data-theme="dark"] .live-flow-step-state {
                    background: #1e293b;
                    color: #94a3b8;
                }

                body[data-theme="dark"] .live-flow-step.is-active .live-flow-step-state {
                    background: #3b82f6;
                    color: #ffffff;
                }

                body[data-theme="dark"] .live-flow-step.is-completed .live-flow-step-state {
                    background: #10b981;
                    color: #ffffff;
                }

                body[data-theme="dark"] .live-flow-tool-btn {
                    background: #1e293b;
                    border-color: #334155;
                    color: #94a3b8;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
                }

                body[data-theme="dark"] .live-flow-tool-btn:hover {
                    background: #27374d;
                    border-color: #43607d;
                    color: #f8fafc;
                }

                body[data-theme="dark"] .live-flow-legend {
                    color: var(--muted);
                    border-top-color: var(--line);
                }

                body[data-theme="dark"] .live-flow-legend span {
                    background: #1e293b;
                    border-color: #334155;
                    color: #94a3b8;
                }

                body[data-theme="dark"] .live-flow-line-outer {
                    stroke: #334155;
                    filter: none;
                }

                body[data-theme="dark"] .live-flow-line-main {
                    stroke: #475569;
                    filter: drop-shadow(0 0 8px rgba(71, 85, 105, 0.3));
                }

                body[data-theme="dark"] .live-flow-line-guide {
                    stroke: #1e293b;
                }

                body[data-theme="dark"] .live-flow-marker .marker-halo {
                    fill: #0f172a;
                    stroke: #334155;
                }

                body[data-theme="dark"] .live-flow-marker .marker-label {
                    fill: #94a3b8;
                }

                body[data-theme="dark"] .live-flow-marker .marker-glow {
                    stroke: rgba(59, 130, 246, 0.4);
                }

                body[data-theme="dark"] .live-flow-marker .marker-core {
                    stroke: rgba(15, 23, 42, 0.9);
                }

                body[data-theme="dark"] .live-flow-marker.start .marker-core {
                    fill: #10b981;
                }

                body[data-theme="dark"] .live-flow-marker.end .marker-core {
                    fill: #3b82f6;
                }

                body[data-theme="dark"] .details-modal-dialog,
                body[data-theme="dark"] .details-modal-form,
                body[data-theme="dark"] .details-preview {
                    background: #0a0f18;
                    border-color: #1e293b;
                    color: #f8fafc;
                }

                @media (max-width: 900px) {
                    .top-search { width: 100%; }
                    .top-search input { width: 100%; }
                    .live-flow-head {
                        flex-direction: column;
                        align-items: flex-start;
                    }
                    .live-flow-meta {
                        justify-content: flex-start;
                    }
                    .live-flow-tools {
                        margin-left: 0;
                    }
                    .live-flow-viewport {
                        height: 340px;
                    }
                    .page-sticky-actions { justify-content: flex-start; }
                    .page-sticky-actions .sticky-search {
                        flex-basis: 100%;
                        max-width: none;
                    }
                    .table-panel-controls .sticky-search,
                    .table-panel-controls .sticky-status-filter {
                        flex-basis: 100%;
                        max-width: none;
                    }
                    .table-panel-controls .status-filter-select {
                        min-width: 0;
                    }
                    .qr-receive-row { grid-template-columns: 1fr; }
                    .document-tools {
                        align-items: flex-start;
                    }
                    .details-modal-form {
                        max-height: calc(100vh - 36px);
                    }
                    .details-grid {
                        grid-template-columns: 1fr;
                    }
                }
                            @media (max-width: 640px) {
                    .intake-grid {
                        grid-template-columns: 1fr;
                    }
                    .live-flow-viewport {
                        height: 280px;
                    }
                }

                @media (max-width: 480px) {
                    .details-modal-dialog {
                        width: 100%;
                        max-width: 100%;
                        border-radius: 20px 20px 0 0;
                        margin: 0;
                        max-height: 90vh;
                        overflow-y: auto;
                        transform: translateY(100%);
                        animation: slideUpModal 0.3s forwards;
                    }
                    .edit-intake-fields-grid {
                        grid-template-columns: 1fr;
                    }
                    .edit-intake-field-wide {
                        max-width: 100%;
                    }
                }
</style>
