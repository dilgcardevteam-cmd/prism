@extends('layouts.dashboard')

@section('title', 'Project Details')
@section('page-title', 'Project Details')

@section('styles')
    <style>
        .lfp-summary-card {
            margin-bottom: 24px;
            padding: 18px 20px 16px;
            background: linear-gradient(180deg, #f4f6f8 0%, #ffffff 100%);
            border: 1px solid #00267c;
            border-radius: 10px;
            color: #00267c;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(0, 44, 118, 0.14);
        }

        .lfp-summary-row {
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 2px solid rgba(17, 24, 39, 0.14);
        }

        .lfp-summary-row:last-child {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: none;
        }

        .lfp-summary-label {
            display: block;
            margin-bottom: 4px;
            color: inherit;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .lfp-summary-code {
            color: #111827;
            font-size: 18px;
            font-weight: inherit;
            line-height: 1.08;
            text-decoration: none;
            word-break: break-word;
        }

        .lfp-summary-name {
            color: #111827;
            font-size: 20px;
            font-weight: inherit;
            line-height: 1.05;
            word-break: break-word;
        }

        .lfp-summary-funding {
            display: grid;
            grid-template-columns: repeat(2, max-content);
            gap: 16px 24px;
            align-items: start;
            justify-content: start;
        }

        .lfp-summary-value {
            color: #111827;
            font-size: 18px;
            font-weight: inherit;
            line-height: 1.05;
        }

        .lfp-mobile-shell,
        .lfp-mobile-canvas {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        #mainContent .lfp-mobile-shell {
            overflow-x: clip;
        }

        #mainContent .lfp-mobile-canvas > * {
            min-width: 0;
            max-width: 100%;
        }

        @media (min-width: 769px) {
            #mainContent.with-sidebar .lfp-mobile-shell {
                max-width: calc(100vw - 310px);
            }

            #mainContent:not(.with-sidebar) .lfp-mobile-shell {
                max-width: calc(100vw - 60px);
            }
        }

        .lfp-inline-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.56);
            backdrop-filter: blur(2px);
            z-index: 1290;
        }

        .lfp-inline-modal-backdrop.is-visible {
            display: block;
        }

        .lfp-inline-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            width: min(1180px, calc(100vw - 32px));
            max-height: min(90vh, 960px);
            transform: translate(-50%, -50%);
            border-radius: 16px;
            background-color: #ffffff;
            border: 1px solid #dbeafe;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.24);
            z-index: 1300;
        }

        .lfp-inline-modal.is-visible {
            display: flex !important;
            flex-direction: column;
            overflow: hidden;
        }

        .lfp-inline-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
        }

        .lfp-inline-modal-body {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none;
            overflow-y: auto;
            padding: 20px;
            background-color: #ffffff;
        }

        .lfp-inline-modal-close {
            border: none;
            background: #e2e8f0;
            color: #0f172a;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
        }

        .lfp-inline-modal-section-close {
            display: none;
        }

        .lfp-inline-edit-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 12px;
            background-color: #002C76;
            color: #ffffff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
            cursor: pointer;
        }

        .lfp-inline-edit-trigger i {
            margin-right: 0 !important;
        }

        .lfp-physical-section-title {
            flex: 0 1 75%;
            max-width: 75%;
            min-width: 0;
            line-height: 1.2;
        }

        .lfp-physical-hero {
            display: grid;
            grid-template-columns: minmax(240px, 1.2fr) minmax(320px, 1fr);
            gap: 18px;
            margin-bottom: 18px;
            padding: 18px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 58%, #f8fafc 100%);
        }

        .lfp-physical-eyebrow {
            display: inline-block;
            margin-bottom: 10px;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .lfp-physical-hero-title {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 24px;
            line-height: 1.1;
        }

        .lfp-physical-hero-text {
            margin: 0;
            color: #475569;
            font-size: 13px;
            line-height: 1.6;
            max-width: 58ch;
        }

        .lfp-physical-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .lfp-physical-summary-card {
            padding: 14px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        .lfp-physical-summary-label {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .lfp-physical-summary-value {
            color: #0f172a;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.3;
        }

        .lfp-physical-timeline {
            position: relative;
            display: grid;
            gap: 18px;
            margin-bottom: 18px;
        }

        .lfp-physical-timeline-details {
            margin-bottom: 18px;
            padding: 12px 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .lfp-physical-timeline-details[open] {
            border-color: #bfdbfe;
            background-color: #eff6ff;
            box-shadow: 0 10px 24px rgba(29, 78, 216, 0.1);
        }

        .lfp-physical-timeline-year-groups {
            display: grid;
            gap: 14px;
            margin-top: 16px;
        }

        .lfp-physical-year-accordion {
            border: 1px solid #dbeafe;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.85);
            overflow: hidden;
        }

        .lfp-physical-year-accordion.is-static {
            border: none;
            background: transparent;
        }

        .lfp-physical-year-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            list-style: none;
            cursor: pointer;
        }

        .lfp-physical-year-summary::-webkit-details-marker {
            display: none;
        }

        .lfp-physical-year-summary::after {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 14px;
            font-weight: 700;
            line-height: 1;
            transition: transform 0.2s ease;
        }

        .lfp-physical-year-accordion[open] > .lfp-physical-year-summary::after {
            transform: rotate(45deg);
        }

        .lfp-physical-year-accordion.is-static > .lfp-physical-year-summary {
            padding: 0 0 4px;
            cursor: default;
            pointer-events: none;
        }

        .lfp-physical-year-accordion.is-static > .lfp-physical-year-summary::after {
            display: none;
        }

        .lfp-physical-year-heading {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .lfp-physical-year-label {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .lfp-physical-year-range {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .lfp-physical-year-count {
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .lfp-physical-year-body {
            padding: 0 16px 16px;
        }

        .lfp-physical-timeline-details > summary {
            list-style: none;
        }

        .lfp-physical-timeline-summary::marker {
            content: '';
        }

        .lfp-physical-timeline-summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 700;
        }

        .lfp-physical-timeline-summary::after {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background-color: #dbeafe;
            color: #1d4ed8;
            font-size: 13px;
            line-height: 1;
            transition: transform 0.2s ease;
        }

        .lfp-physical-timeline-details[open] > .lfp-physical-timeline-summary::after {
            transform: rotate(45deg);
        }

        .lfp-physical-timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 19px;
            width: 2px;
            background: linear-gradient(180deg, #93c5fd 0%, #1d4ed8 100%);
        }

        .lfp-physical-timeline-item {
            position: relative;
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .lfp-physical-timeline-node {
            position: relative;
            z-index: 1;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: #002c76;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(0, 44, 118, 0.22);
        }

        .lfp-physical-timeline-card {
            padding: 16px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .lfp-physical-timeline-node.is-empty {
            background: #94a3b8;
            box-shadow: none;
        }

        .lfp-physical-timeline-card.is-empty {
            border-style: dashed;
            background: #f8fafc;
            box-shadow: none;
        }

        .lfp-physical-timeline-card-header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
            margin-bottom: 14px;
        }

        .lfp-physical-timeline-card-header h4 {
            margin: 0;
            color: #0f172a;
            font-size: 18px;
            font-weight: 700;
        }

        .lfp-physical-timeline-kicker {
            margin: 0 0 4px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .lfp-physical-timeline-note {
            margin: 0 0 12px;
            padding: 10px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .lfp-physical-timeline-month {
            padding: 5px 9px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
        }

        .lfp-physical-timeline-metrics {
            display: grid;
            grid-template-columns: minmax(0, 30%) minmax(0, 70%);
            gap: 12px;
        }

        .lfp-physical-timeline-columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .lfp-physical-compare-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 0 0 12px;
            padding: 8px 12px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            cursor: pointer;
        }

        .lfp-physical-compare-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .lfp-physical-compare-modal-column {
            display: grid;
            gap: 12px;
        }

        .lfp-physical-compare-modal-heading {
            margin: 0;
            color: #002c76;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .lfp-physical-timeline-metric {
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .lfp-physical-timeline-metric span {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .lfp-physical-timeline-metric strong {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
        }

        .lfp-physical-trend {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .lfp-physical-trend-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
            cursor: help;
            flex: 0 0 auto;
        }

        .lfp-physical-trend-indicator.is-up {
            background: #dcfce7;
            color: #166534;
        }

        .lfp-physical-trend-indicator.is-down {
            background: #fee2e2;
            color: #991b1b;
        }

        .lfp-physical-timeline-remarks {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed #cbd5e1;
        }

        .lfp-physical-timeline-remarks span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .lfp-physical-timeline-remarks p {
            margin: 0;
            color: #334155;
            font-size: 13px;
            line-height: 1.6;
            white-space: pre-line;
        }

        .lfp-physical-footer-meta {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .lfp-physical-footer-meta div {
            padding: 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .lfp-physical-footer-meta span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .lfp-physical-footer-meta strong {
            color: #0f172a;
            font-size: 14px;
            line-height: 1.4;
        }

        .lfp-physical-empty-state {
            padding: 18px;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            background: #f8fafc;
        }

        .lfp-physical-modal-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(300px, 1fr));
            gap: 16px;
        }

        .lfp-financial-view-stack {
            display: grid;
            gap: 18px;
            margin-bottom: 20px;
        }

        .lfp-financial-edit-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
        }

        .lfp-financial-edit-card {
            padding: 16px;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .lfp-financial-edit-card--full {
            grid-column: 1 / -1;
        }

        .lfp-financial-edit-summary {
            margin: 0 0 8px;
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
        }

        .lfp-financial-edit-summary span {
            color: #1d4ed8;
        }

        .lfp-financial-timeline-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        @media (max-width: 1200px) {
            .lfp-financial-timeline-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .lfp-financial-timeline-metrics {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .lfp-summary-card {
                padding: 16px;
            }

            .lfp-summary-funding {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .lfp-summary-name {
                font-size: clamp(1.75rem, 7vw, 2.4rem);
            }

            .lfp-summary-value {
                font-size: clamp(1.45rem, 6vw, 2rem);
            }

            .lfp-inline-modal-body {
                padding: 16px;
            }

            .lfp-physical-hero {
                grid-template-columns: 1fr;
            }

            .lfp-physical-summary-grid,
            .lfp-physical-timeline-metrics,
            .lfp-physical-modal-grid,
            .lfp-physical-footer-meta {
                grid-template-columns: 1fr;
                margin-bottom: 4px;
            }

            .lfp-physical-compare-toggle {
                display: inline-flex;
            }

            .lfp-physical-timeline-columns {
                display: none;
            }

            .lfp-physical-timeline-card-header {
                flex-direction: column;
            }

            .lfp-physical-timeline::before {
                left: 15px;
            }

            .lfp-physical-timeline-item {
                grid-template-columns: 32px minmax(0, 1fr);
                gap: 12px;
            }

            .lfp-physical-timeline-node {
                width: 32px;
                height: 32px;
                font-size: 10px;
            }

            .lfp-physical-timeline-card {
                padding: 14px;
            }

            .lfp-physical-trend {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            #editPhysicalFormWrapper .monthly-details {
                width: 100%;
                min-width: 0;
            }

            #editPhysicalFormWrapper .monthly-summary {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            #editPhysicalFormWrapper .monthly-details > div[style*="margin-top: 10px;"] {
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 6px;
                -webkit-overflow-scrolling: touch;
            }

            #editPhysicalFormWrapper div[style*="grid-template-columns: 120px 1fr 180px 140px"] {
                min-width: 640px;
                grid-template-columns: 110px minmax(220px, 1fr) 170px 130px !important;
                gap: 8px !important;
            }

            #editPhysicalFormWrapper input[type="date"],
            #editPhysicalFormWrapper input[type="number"],
            #editPhysicalFormWrapper select,
            #editPhysicalFormWrapper textarea {
                max-width: 100%;
            }

            #physicalCompareModalWrapper {
                margin-top: 0;
                top: auto;
                left: auto;
                width: auto;
                max-height: none;
                transform: none;
                background-color: #ffffff;
                border: 1px solid #dbeafe;
                border-radius: 16px;
                box-shadow: 0 24px 48px rgba(15, 23, 42, 0.24);
            }

            #physicalCompareModalWrapper.is-visible {
                position: fixed;
                top: max(12px, env(safe-area-inset-top));
                right: 12px;
                bottom: max(12px, env(safe-area-inset-bottom));
                left: 12px;
                display: flex !important;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
                z-index: 1300;
            }

            #physicalCompareModalWrapper .lfp-inline-modal-body {
                padding: 16px;
            }

            .lfp-physical-compare-modal-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .lfp-mobile-canvas .lfp-inline-modal-backdrop.is-visible {
                display: block !important;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.56);
                z-index: 1290;
            }

            #editProfileFormBackdrop.is-visible,
            #editContractFormBackdrop.is-visible,
            #editPhysicalFormBackdrop.is-visible {
                display: block !important;
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.56);
                z-index: 1290;
            }

            .lfp-mobile-canvas .lfp-inline-modal {
                margin-top: 0;
                top: auto;
                left: auto;
                width: auto;
                max-height: none;
                transform: none;
                background-color: #ffffff;
                border: 1px solid #dbeafe;
                border-radius: 16px;
                box-shadow: 0 24px 48px rgba(15, 23, 42, 0.24);
            }

            #editProfileFormWrapper,
            #editContractFormWrapper,
            #editPhysicalFormWrapper {
                margin-top: 0;
                top: auto;
                left: auto;
                width: auto;
                max-height: none;
                transform: none;
                background-color: #ffffff;
                border: 1px solid #dbeafe;
                border-radius: 16px;
                box-shadow: 0 24px 48px rgba(15, 23, 42, 0.24);
            }

            .lfp-mobile-canvas .lfp-inline-modal.is-visible {
                position: fixed;
                top: max(12px, env(safe-area-inset-top));
                right: 12px;
                bottom: max(12px, env(safe-area-inset-bottom));
                left: 12px;
                display: flex !important;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
                z-index: 1300;
            }

            #editProfileFormWrapper.is-visible,
            #editContractFormWrapper.is-visible,
            #editPhysicalFormWrapper.is-visible {
                position: fixed;
                top: max(12px, env(safe-area-inset-top));
                right: 12px;
                bottom: max(12px, env(safe-area-inset-bottom));
                left: 12px;
                display: flex !important;
                flex-direction: column;
                min-height: 0;
                overflow: hidden;
                z-index: 1300;
            }

            .lfp-mobile-canvas .lfp-inline-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px 16px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
            }

            #editProfileFormWrapper .lfp-inline-modal-header,
            #editContractFormWrapper .lfp-inline-modal-header,
            #editPhysicalFormWrapper .lfp-inline-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 14px 16px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
            }

            .lfp-mobile-canvas .lfp-inline-modal-body {
                flex: 1 1 auto;
                min-height: 0;
                max-height: none;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                background-color: #ffffff;
            }

            .lfp-mobile-canvas .lfp-inline-modal-body form {
                min-height: 100%;
            }

            #editProfileFormWrapper .lfp-inline-modal-body,
            #editContractFormWrapper .lfp-inline-modal-body,
            #editPhysicalFormWrapper .lfp-inline-modal-body {
                flex: 1 1 auto;
                min-height: 0;
                max-height: none;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                background-color: #ffffff;
            }

            #editProfileFormWrapper .lfp-inline-modal-body form,
            #editContractFormWrapper .lfp-inline-modal-body form,
            #editPhysicalFormWrapper .lfp-inline-modal-body form {
                min-height: 100%;
            }

            #editProfileForm > div:first-of-type,
            #editContractForm > div:first-of-type {
                grid-template-columns: 1fr !important;
            }

        }
    </style>
@endsection

@section('content')
    @php
        $userAgency = strtoupper(trim((string) (Auth::user()->agency ?? '')));
        $userProvince = trim((string) (Auth::user()->province ?? ''));
        $isLguAgencyUser = $userAgency === 'LGU';
        $canUpdateLocallyFundedProject = Auth::user()->hasCrudPermission('locally_funded_projects', 'update');
        $canDeleteLocallyFundedProject = Auth::user()->hasCrudPermission('locally_funded_projects', 'delete');
        $canEditProjectProfile = $userAgency === 'DILG'
            && $userProvince === 'Regional Office'
            && Auth::user()->isSuperAdmin();
    @endphp

    <div class="lfp-mobile-shell">
        <div class="lfp-mobile-canvas">
    <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
        <div>
            <h1 style="font-weight: 700; color: #002C76; font-size: 32px;">Project Details</h1>
            <p>Full record for the selected locally funded project.</p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="{{ route('projects.locally-funded') }}" class="lfp-header-action lfp-header-action--primary">
                <i class="fas fa-arrow-left"></i>
                Back to List
            </a>
            <button id="activityLogFab" type="button" class="lfp-header-action lfp-header-action--secondary" aria-controls="activityLogSection" aria-expanded="false" data-state="closed">
                <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                <span>Activity Logs</span>
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin: 16px 0;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div id="success-alert" style="background-color: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 8px; margin: 16px 0;">
            {{ session('success') }}
        </div>
        <script>
            setTimeout(function () {
                const successAlert = document.getElementById('success-alert');
                if (successAlert) {
                    successAlert.style.display = 'none';
                }
            }, 3000);
        </script>
    @endif

    <div style="background: #f8fafc; padding: 24px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        
        <h1 class="project-main-title" style="font-weight: 700; color: #002C76; font-size: 32px;">{{ $project->project_name }}</h1>
        <div style="
            display: flex;
            flex-direction: row;
            width: 100%;
            ">
            <div style="display: flex; flex-direction: column; gap: 6px; color: #374151; font-family: var(--app-font-sans);">
                <span style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span>
                    <span style="font-weight: 700; color: #374151;">
                        Project Code:
                    </span>
                    {{ $project->subaybayan_project_code }}
                    </span>
                    <button type="button" class="project-copy-button" data-copy-text="{{ $project->subaybayan_project_code }}" aria-label="Copy project code" title="Copy project code">
                        <i class="fas fa-copy" aria-hidden="true"></i>
                    </button>
                </span>

                <span>
                    <span style="font-weight: 700; color: #374151;">
                        Funding Year:
                    </span>
                    {{ $project->funding_year }}
                </span>
                <span>
                <span style="font-weight: 700; color: #374151;">
                        Funding Source:
                    </span>
                    {{ $project->fund_source }}
                </span>
            </div>
        </div>

        <!-- <br>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="padding: 16px; border: 1px solid #002C76; border-radius: 8px;">
                <div style="font-size: 12px; color: #002C76; font-weight: 700; text-transform: uppercase;">Project Code</div>
                <div style="font-size: 16px; font-weight: 700; color: #111827; margin-top: 6px;">{{ $project->subaybayan_project_code }}</div>
            </div>
            <div style="padding: 16px; border: 1px solid #002C76; border-radius: 8px;">
                <div style="font-size: 12px; color: #002C76; font-weight: 700; text-transform: uppercase;">Project Name</div>
                <div style="font-size: 16px; font-weight: 700; color: #111827; margin-top: 6px;">{{ $project->project_name }}</div>
            </div>
            <div style="padding: 16px; border: 1px solid #002C76; border-radius: 8px;">
                <div style="font-size: 12px; color: #002C76; font-weight: 700; text-transform: uppercase;">Funding</div>
                <div style="font-size: 14px; color: #111827; margin-top: 6px;">Year: <strong>{{ $project->funding_year }}</strong></div>
                <div style="font-size: 14px; color: #111827;">Source: <strong>{{ $project->fund_source }}</strong></div>
            </div>
        </div> -->

        <hr style="margin: 10px">

        <!-- <br>
        <div class="lfp-summary-card">
            <div class="lfp-summary-row">
                <span class="lfp-summary-label">Project Code</span>
                <div class="lfp-summary-code">{{ $project->subaybayan_project_code }}</div>
            </div>
            <div class="lfp-summary-row">
                <span class="lfp-summary-label">Project Name</span>
                <div class="lfp-summary-name">{{ $project->project_name }}</div>
            </div>
            <div class="lfp-summary-row">
                <div class="lfp-summary-funding">
                    <div>
                        <span class="lfp-summary-label">Funding Year</span>
                        <div class="lfp-summary-value">{{ $project->funding_year }}</div>
                    </div>
                    <div>
                        <span class="lfp-summary-label">Fund Source</span>
                        <div class="lfp-summary-value">{{ $project->fund_source }}</div>
                    </div>
                </div>
            </div>
        </div> -->

        <div class="project-tabs" role="tablist" aria-label="Project detail sections" style="margin-top: 40px;">
            <button type="button" class="project-tab is-active" id="tab-project-profile" data-project-tab-target="projectProfileSection" role="tab" aria-controls="projectProfileSection" aria-selected="true">Project Profile</button>
            <button type="button" class="project-tab" id="tab-contract-info" data-project-tab-target="contractInfoSection" role="tab" aria-controls="contractInfoSection" aria-selected="false">Contract Information</button>
            <button type="button" class="project-tab" id="tab-physical-accomplishment" data-project-tab-target="physicalAccomplishmentSection" role="tab" aria-controls="physicalAccomplishmentSection" aria-selected="false">Physical Accomplishment</button>
            <button type="button" class="project-tab" id="tab-financial-accomplishment" data-project-tab-target="financialAccomplishmentSection" role="tab" aria-controls="financialAccomplishmentSection" aria-selected="false">Financial Accomplishment</button>
            <button type="button" class="project-tab" id="tab-monitoring-inspection" data-project-tab-target="monitoringInspectionSection" role="tab" aria-controls="monitoringInspectionSection" aria-selected="false">Monitoring/Inspection Activities</button>
            <button type="button" class="project-tab" id="tab-post-implementation" data-project-tab-target="postImplementationSection" role="tab" aria-controls="postImplementationSection" aria-selected="false">Post Implementation</button>
            <button type="button" class="project-tab" id="tab-gallery" data-project-tab-target="gallerySection" role="tab" aria-controls="gallerySection" aria-selected="false">Gallery</button>
        </div>

        <div id="projectProfileSection" class="project-tab-panel is-active" data-tab-key="profile" role="tabpanel" aria-labelledby="tab-project-profile" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Project Profile</h3>
                @if($canEditProjectProfile)
                    <a href="#" class="lfp-inline-edit-trigger" data-toggle="inline-edit" data-target="editProfileForm"><i class="fas fa-edit" aria-hidden="true"></i>Update</a>
                @endif
            </div>
            <div class="project-profile-grid" style="display: grid; grid-template-columns: repeat(2, minmax(260px, 1fr)); gap: 14px;">
                <div style="grid-column: 1 / -1;">
                    <span style="font-weight: 700; color: #374151;">Project Description:</span>
                    </br>
                    <span class="text-[#374151]" style="margin-top: 6px; color: #374151;">{!! nl2br(e($project->project_description)) !!}</span>
                </div>

                    <!-- SITE / LOCATION -->
                    <div class="flex flex-col text-[#374151]" style="gap: 8px;">
                        <span class=""><strong>Province:</strong> {{ $project->province }}</span>
                        <span class=""><strong>City/Municipality:</strong> {{ $project->city_municipality }}</span>
                        @php
                            $barangays = array_filter(array_map('trim', explode(',', (string) $project->barangay)));
                        @endphp
                        <span class="">
                            <strong>Barangay:</strong>
                            @if(count($barangays))
                                <ul style="margin: 4px 0 0 16px; padding: 0;">
                                    @foreach($barangays as $barangay)
                                        <li style="margin: 0; list-style: disc;">{{ $barangay }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </span>
                    </div>

                    <!-- OTHER INFO -->
                    <div class="flex flex-col text-[#374151]" style="gap: 8px;">
                        <span><strong>Project Type:</strong> {{ $project->project_type }}</span>
                        <span><strong>Date of NADAI:</strong> {{ $project->date_nadai ? $project->date_nadai->format('F j, Y') : '' }}</span>
                        <span><strong>No. of Beneficiaries:</strong> {{ number_format($project->no_of_beneficiaries) }}</span>
                        <span><strong>Rainwater Collection System:</strong> {{ $project->rainwater_collection_system }}</span>
                        <span><strong>Date of Confirmation Fund Receipt:</strong> {{ $project->date_confirmation_fund_receipt ? $project->date_confirmation_fund_receipt->format('F j, Y') : '' }}</span>
                        <span><strong>LGSF Allocation:</strong> ₱ {{ number_format($project->lgsf_allocation, 2) }}</span>
                        <span><strong>LGU Counterpart:</strong> ₱ {{ number_format($project->lgu_counterpart, 2) }}</span>
                    </div>
            </div>
            @if($canEditProjectProfile)
            <div id="editProfileFormBackdrop" class="lfp-inline-modal-backdrop{{ old('section') === 'profile' ? ' is-visible' : '' }}" aria-hidden="{{ old('section') === 'profile' ? 'false' : 'true' }}"></div>
            <div id="editProfileFormWrapper" class="lfp-inline-modal{{ old('section') === 'profile' ? ' is-visible' : '' }}" data-inline-modal="true" role="dialog" aria-modal="true" aria-labelledby="editProfileModalTitle" aria-hidden="{{ old('section') === 'profile' ? 'false' : 'true' }}" style="display: {{ old('section') === 'profile' ? 'block' : 'none' }};">
                <div class="lfp-inline-modal-header">
                    <h3 id="editProfileModalTitle" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Edit Project Profile</h3>
                    <button type="button" class="lfp-inline-modal-close" data-toggle="inline-cancel" data-target="editProfileForm" aria-label="Close project profile editor">&times;</button>
                </div>
                <div class="lfp-inline-modal-body">
            <form id="editProfileForm" action="{{ route('locally-funded-project.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="profile">

                <div class="project-profile-form-grid" style="display: grid; grid-template-columns: repeat(3, minmax(260px, 1fr)); gap: 20px; padding: 20px; border: 1px solid #cbd5e1; border-radius: 10px; background-color: white;">
                    @php
                        $selectedProvince = old('province', $project->province);
                        $selectedProvinceNorm = strtolower(trim((string) $selectedProvince));
                        $hasProvinceInOptions = collect($provinces)->contains(function ($item) use ($selectedProvinceNorm) {
                            return strtolower(trim((string) $item)) === $selectedProvinceNorm;
                        });
                        $selectedCityMunicipality = old('city_municipality', $project->city_municipality);
                        $selectedCityMunicipalityNorm = strtolower(trim((string) $selectedCityMunicipality));
                        $cityMunicipalityOptions = collect($provinceMunicipalities[$selectedProvince] ?? [])
                            ->filter(function ($item) {
                                return trim((string) $item) !== '';
                            })
                            ->values();
                        $hasCityInOptions = $cityMunicipalityOptions->contains(function ($item) use ($selectedCityMunicipalityNorm) {
                            return strtolower(trim((string) $item)) === $selectedCityMunicipalityNorm;
                        });

                        $selectedFundingYear = (string) old('funding_year', $project->funding_year);
                        $hasFundingYearInOptions = collect($fundingYears)->contains(function ($item) use ($selectedFundingYear) {
                            return (string) $item === $selectedFundingYear;
                        });

                        $selectedFundSource = old('fund_source', $project->fund_source);
                        $selectedFundSourceNorm = strtolower(trim((string) $selectedFundSource));
                        $hasFundSourceInOptions = collect($fundSources)->contains(function ($item) use ($selectedFundSourceNorm) {
                            return strtolower(trim((string) $item)) === $selectedFundSourceNorm;
                        });
                    @endphp
                    <div>
                        <label for="project_name" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Name <span class="asterisk">*</span></label>
                        <input type="text" id="project_name" name="project_name" value="{{ old('project_name', $project->project_name) }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label for="subaybayan_project_code" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">SubayBayan Project Code <span class="asterisk">*</span></label>
                        <input type="text" id="subaybayan_project_code" name="subaybayan_project_code" value="{{ old('subaybayan_project_code', $project->subaybayan_project_code) }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    
                    <div>
                        <label for="funding_year" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Funding Year <span class="asterisk">*</span></label>
                        <select id="funding_year" name="funding_year" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Funding Year --</option>
                            @foreach($fundingYears as $year)
                                <option value="{{ $year }}" {{ (string) $year === $selectedFundingYear ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                            @if(!$hasFundingYearInOptions && trim($selectedFundingYear) !== '')
                                <option value="{{ $selectedFundingYear }}" selected>{{ $selectedFundingYear }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="fund_source" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Fund Source <span class="asterisk">*</span></label>
                        <select id="fund_source" name="fund_source" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Fund Source --</option>
                            @foreach($fundSources as $source)
                                <option value="{{ $source }}" {{ strtolower(trim((string) $source)) === $selectedFundSourceNorm ? 'selected' : '' }}>{{ $source }}</option>
                            @endforeach
                            @if(!$hasFundSourceInOptions && trim((string) $selectedFundSource) !== '')
                                <option value="{{ $selectedFundSource }}" selected>{{ $selectedFundSource }}</option>
                            @endif
                        </select>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label for="project_description" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Description <span class="asterisk">*</span></label>
                        <textarea id="project_description" name="project_description" required rows="3"
                                  style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; resize: vertical;">{{ old('project_description', $project->project_description) }}</textarea>
                    </div>

                    <div>
                        <label for="province" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Province <span class="asterisk">*</span></label>
                        <select id="province" name="province" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Province --</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}" {{ strtolower(trim((string) $province)) === $selectedProvinceNorm ? 'selected' : '' }}>{{ $province }}</option>
                            @endforeach
                            @if(!$hasProvinceInOptions && trim((string) $selectedProvince) !== '')
                                <option value="{{ $selectedProvince }}" selected>{{ $selectedProvince }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="city_municipality" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">City/Municipality <span class="asterisk">*</span></label>
                        <select id="city_municipality" name="city_municipality" required data-selected="{{ $selectedCityMunicipality }}"
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">{{ $cityMunicipalityOptions->isNotEmpty() ? '-- Select City/Municipality --' : '-- Select Province First --' }}</option>
                            @foreach($cityMunicipalityOptions as $cityMunicipalityOption)
                                <option value="{{ $cityMunicipalityOption }}" {{ strtolower(trim((string) $cityMunicipalityOption)) === $selectedCityMunicipalityNorm ? 'selected' : '' }}>
                                    {{ $cityMunicipalityOption }}
                                </option>
                            @endforeach
                            @if(!$hasCityInOptions && trim((string) $selectedCityMunicipality) !== '')
                                <option value="{{ $selectedCityMunicipality }}" selected>{{ $selectedCityMunicipality }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="barangay" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Barangay <span class="asterisk">*</span></label>
                        <div style="position: relative;">
                            <div id="barangay_badges" role="button" tabindex="0" aria-controls="barangay" aria-expanded="false" style="display: flex; flex-wrap: wrap; gap: 6px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; min-height: 44px; background-color: white; margin-bottom: 8px; align-content: flex-start; cursor: pointer;">
                                <span style="color: #9ca3af; font-size: 14px; align-self: center;">Click here or dropdown to add barangays</span>
                            </div>
                            <select id="barangay" name="barangay[]" multiple hidden
                                    style="width: 100%; margin-top: 8px; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white; min-height: 120px;">
                            </select>
                        </div>
                        <input type="hidden" id="barangay_hidden" name="barangay_json" value="{{ old('barangay_json', json_encode(array_values(array_filter(array_map('trim', explode(',', $project->barangay)))))) }}">
                    </div>

                    <div>
                        <label for="project_type" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Type <span class="asterisk">*</span></label>
                        <select id="project_type" name="project_type" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Project Type --</option>
                            <option value="Evacuation Center / Multi-Purpose Hall" {{ old('project_type', $project->project_type) === 'Evacuation Center / Multi-Purpose Hall' ? 'selected' : '' }}>Evacuation Center / Multi-Purpose Hall</option>
                            <option value="Water Supply and Sanitation" {{ old('project_type', $project->project_type) === 'Water Supply and Sanitation' ? 'selected' : '' }}>Water Supply and Sanitation</option>
                            <option value="Local Roads and Bridges" {{ old('project_type', $project->project_type) === 'Local Roads and Bridges' ? 'selected' : '' }}>Local Roads and Bridges</option>
                            <option value="Others" {{ old('project_type', $project->project_type) === 'Others' ? 'selected' : '' }}>Others</option>
                        </select>
                    </div>

                    <div>
                        <label for="date_nadai" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of NADAI <span class="asterisk">*</span></label>
                        <input type="date" id="date_nadai" name="date_nadai" value="{{ old('date_nadai', $project->date_nadai ? $project->date_nadai->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="lgsf_allocation" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">LGSF Allocation (based on NADAI) <span class="asterisk">*</span></label>
                        <input type="text" id="lgsf_allocation" name="lgsf_allocation" value="{{ old('lgsf_allocation', number_format((float)$project->lgsf_allocation, 2, '.', ',')) }}" placeholder="0.00" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="lgu_counterpart" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">LGU Counterpart <span class="asterisk">*</span></label>
                        <input type="text" id="lgu_counterpart" name="lgu_counterpart" value="{{ old('lgu_counterpart', number_format((float)$project->lgu_counterpart, 2, '.', ',')) }}" placeholder="0.00" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label for="no_of_beneficiaries" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">No. of Beneficiaries <span class="asterisk">*</span></label>
                        <input type="number" id="no_of_beneficiaries" name="no_of_beneficiaries" value="{{ old('no_of_beneficiaries', $project->no_of_beneficiaries) }}" min="0" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="rainwater_collection_system" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 13px;">With Rainwater Collection System (for Govt buildings)</label>
                        <select id="rainwater_collection_system" name="rainwater_collection_system"
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select --</option>
                            <option value="Yes" {{ old('rainwater_collection_system', $project->rainwater_collection_system) === 'Yes' ? 'selected' : '' }}>Yes</option>
                            <option value="No" {{ old('rainwater_collection_system', $project->rainwater_collection_system) === 'No' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>

                    <div>
                        <label for="date_confirmation_fund_receipt" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of Confirmation Fund Receipt <span class="asterisk">*</span></label>
                        <input type="date" id="date_confirmation_fund_receipt" name="date_confirmation_fund_receipt" value="{{ old('date_confirmation_fund_receipt', $project->date_confirmation_fund_receipt ? $project->date_confirmation_fund_receipt->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                </div>

                <div style="margin-top: 16px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save Changes</button>
                    <button type="button" data-toggle="inline-cancel" data-target="editProfileForm" style="padding: 8px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"><i class="fas fa-times" style="margin-right: 8px;"></i>Cancel</button>
                </div>
            </form>
                </div>
            </div>
            @endif
        </div>

        <div id="contractInfoSection" class="project-tab-panel" data-tab-key="contract" role="tabpanel" aria-labelledby="tab-contract-info" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Contract Information</h3>
                @if($canUpdateLocallyFundedProject)
                    <a href="#" class="lfp-inline-edit-trigger" data-toggle="inline-edit" data-target="editContractForm"><i class="fas fa-edit" aria-hidden="true"></i>Update</a>
                @endif
            </div>
            <div class="contract-info-grid" style="display: grid; grid-template-columns: repeat(3, minmax(260px, 1fr)); gap: 14px;">
                <div><strong>Mode of Procurement:</strong> {{ $project->mode_of_procurement }}</div>
                <div><strong>Date of Posting (ITB):</strong> {{ $project->date_posting_itb ? $project->date_posting_itb->format('F j, Y') : '' }}</div>
                <div><strong>Date of Bid Opening:</strong> {{ $project->date_bid_opening ? $project->date_bid_opening->format('F j, Y') : '' }}</div>
                <div><strong>Date of NOA:</strong> {{ $project->date_noa ? $project->date_noa->format('F j, Y') : '' }}</div>
                <div><strong>Date of NTP:</strong> {{ $project->date_ntp ? $project->date_ntp->format('F j, Y') : '' }}</div>
                <div><strong>Contractor:</strong> {{ $project->contractor }}</div>
                <div><strong>Contract Amount:</strong> ₱ {{ number_format($project->contract_amount, 2) }}</div>
                <div><strong>Project Duration:</strong> {{ $project->project_duration }}</div>
                <div><strong>Actual Start Date:</strong> {{ $project->actual_start_date ? $project->actual_start_date->format('F j, Y') : '' }}</div>
                <div><strong>Target Date of Completion:</strong> {{ $project->target_date_completion ? $project->target_date_completion->format('F j, Y') : '' }}</div>
                <div><strong>Revised Target Date:</strong> {{ $project->revised_target_date_completion ? $project->revised_target_date_completion->format('F j, Y') : 'N/A' }}</div>
            </div>
            <div id="editContractFormBackdrop" class="lfp-inline-modal-backdrop{{ old('section') === 'contract' ? ' is-visible' : '' }}" aria-hidden="{{ old('section') === 'contract' ? 'false' : 'true' }}"></div>
            <div id="editContractFormWrapper" class="lfp-inline-modal{{ old('section') === 'contract' ? ' is-visible' : '' }}" data-inline-modal="true" role="dialog" aria-modal="true" aria-labelledby="editContractModalTitle" aria-hidden="{{ old('section') === 'contract' ? 'false' : 'true' }}" style="display: {{ old('section') === 'contract' ? 'block' : 'none' }};">
                <div class="lfp-inline-modal-header">
                    <h3 id="editContractModalTitle" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Edit Contract Information</h3>
                    <button type="button" class="lfp-inline-modal-close" data-toggle="inline-cancel" data-target="editContractForm" aria-label="Close contract information editor">&times;</button>
                </div>
                <div class="lfp-inline-modal-body">
            <form id="editContractForm" action="{{ route('locally-funded-project.update', $project) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="contract">

                <div class="contract-form-grid" style="display: grid; grid-template-columns: repeat(3, minmax(260px, 1fr)); gap: 20px;">
                    @php
                        $selectedModeOfProcurement = old('mode_of_procurement', $project->mode_of_procurement);
                        $selectedModeOfProcurementNorm = strtolower(trim((string) $selectedModeOfProcurement));
                        $knownModes = ['admin', 'contract'];
                        $hasModeOption = in_array($selectedModeOfProcurementNorm, $knownModes, true);

                        $selectedImplementingUnit = old('implementing_unit', $project->implementing_unit);
                        $selectedImplementingUnitNorm = strtolower(trim((string) $selectedImplementingUnit));
                        $knownImplementingUnits = ['provincial lgu', 'municipal lgu', 'barangay lgu'];
                        $hasImplementingOption = in_array($selectedImplementingUnitNorm, $knownImplementingUnits, true);
                    @endphp
                    <div>
                        <label for="mode_of_procurement" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Mode of Procurement *</label>
                        <select id="mode_of_procurement" name="mode_of_procurement" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Mode of Procurement --</option>
                            <option value="admin" {{ $selectedModeOfProcurementNorm === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="contract" {{ $selectedModeOfProcurementNorm === 'contract' ? 'selected' : '' }}>Contract</option>
                            @if(!$hasModeOption && trim((string) $selectedModeOfProcurement) !== '')
                                <option value="{{ $selectedModeOfProcurement }}" selected>{{ $selectedModeOfProcurement }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="implementing_unit" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Implementing Unit *</label>
                        <select id="implementing_unit" name="implementing_unit" required
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box; background-color: white;">
                            <option value="">-- Select Implementing Unit --</option>
                            <option value="Provincial LGU" {{ $selectedImplementingUnitNorm === 'provincial lgu' ? 'selected' : '' }}>Provincial LGU</option>
                            <option value="Municipal LGU" {{ $selectedImplementingUnitNorm === 'municipal lgu' ? 'selected' : '' }}>Municipal LGU</option>
                            <option value="Barangay LGU" {{ $selectedImplementingUnitNorm === 'barangay lgu' ? 'selected' : '' }}>Barangay LGU</option>
                            @if(!$hasImplementingOption && trim((string) $selectedImplementingUnit) !== '')
                                <option value="{{ $selectedImplementingUnit }}" selected>{{ $selectedImplementingUnit }}</option>
                            @endif
                        </select>
                    </div>

                    <div>
                        <label for="date_posting_itb" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of Posting (ITB) *</label>
                        <input type="date" id="date_posting_itb" name="date_posting_itb" value="{{ old('date_posting_itb', $project->date_posting_itb ? $project->date_posting_itb->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="date_bid_opening" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of Bid Opening *</label>
                        <input type="date" id="date_bid_opening" name="date_bid_opening" value="{{ old('date_bid_opening', $project->date_bid_opening ? $project->date_bid_opening->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="date_noa" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of NOA *</label>
                        <input type="date" id="date_noa" name="date_noa" value="{{ old('date_noa', $project->date_noa ? $project->date_noa->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="date_ntp" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Date of NTP *</label>
                        <input type="date" id="date_ntp" name="date_ntp" value="{{ old('date_ntp', $project->date_ntp ? $project->date_ntp->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="contractor" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Contractor *</label>
                        <input type="text" id="contractor" name="contractor" value="{{ old('contractor', $project->contractor) }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="contract_amount" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Contract Amount *</label>
                        <input type="text" id="contract_amount" name="contract_amount" value="{{ old('contract_amount', number_format((float)$project->contract_amount, 2, '.', ',')) }}" placeholder="0.00" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="project_duration" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Project Duration *</label>
                        <input type="text" id="project_duration" name="project_duration" value="{{ old('project_duration', $project->project_duration) }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="actual_start_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Actual Start Date *</label>
                        <input type="date" id="actual_start_date" name="actual_start_date" value="{{ old('actual_start_date', $project->actual_start_date ? $project->actual_start_date->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="target_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Target Date of Completion *</label>
                        <input type="date" id="target_date_completion" name="target_date_completion" value="{{ old('target_date_completion', $project->target_date_completion ? $project->target_date_completion->format('Y-m-d') : '') }}" required
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="revised_target_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Revised Target Date of Completion</label>
                        <input type="date" id="revised_target_date_completion" name="revised_target_date_completion" value="{{ old('revised_target_date_completion', $project->revised_target_date_completion ? $project->revised_target_date_completion->format('Y-m-d') : '') }}"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>

                    <div>
                        <label for="actual_date_completion" style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Actual Date of Completion</label>
                        <input type="date" id="actual_date_completion" name="actual_date_completion" value="{{ old('actual_date_completion', $project->actual_date_completion ? $project->actual_date_completion->format('Y-m-d') : '') }}"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.3s ease; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-top: 16px; display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save Changes</button>
                    <button type="button" data-toggle="inline-cancel" data-target="editContractForm" style="padding: 8px 16px; background-color: #6b7280; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;"><i class="fas fa-times" style="margin-right: 8px;"></i>Cancel</button>
                </div>
            </form>
                </div>
            </div>
        </div>

        @php
            $months = [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ];
            $statusOptions = [
                ['value' => 'COMPLETED', 'label' => 'Completed'],
                ['value' => 'ONGOING', 'label' => 'On-going'],
                ['value' => 'BID EVALUATION/OPENING', 'label' => 'Bid Evaluation/Opening'],
                ['value' => 'NOA ISSUANCE', 'label' => 'NOA Issuance'],
                ['value' => 'DED PREPARATION', 'label' => 'DED Preparation'],
                ['value' => 'NOT YET STARTED', 'label' => 'Not Yet Started'],
                ['value' => 'ITB/AD POSTED', 'label' => 'ITB/AD Posted'],
                ['value' => 'TERMINATED', 'label' => 'Terminated'],
                ['value' => 'CANCELLED', 'label' => 'Cancelled'],
            ];
            $statusOptionValues = array_column($statusOptions, 'value');
            $statusLabelMap = [
                'COMPLETED' => 'Completed',
                'ONGOING' => 'On-going',
                'BID EVALUATION/OPENING' => 'Bid Evaluation/Opening',
                'NOA ISSUANCE' => 'NOA Issuance',
                'DED PREPARATION' => 'DED Preparation',
                'NOT YET STARTED' => 'Not Yet Started',
                'ITB/AD POSTED' => 'ITB/AD Posted',
                'TERMINATED' => 'Terminated',
                'CANCELLED' => 'Cancelled',
                'TERMINATED/CANCEL' => 'Terminated/Cancelled',
                'PROCUREMENT' => 'Procurement',
            ];
            $statusLabel = function ($value) use ($statusLabelMap) {
                return $statusLabelMap[$value] ?? $value;
            };
            $statusBadge = function ($value) use ($statusLabel) {
                if ($value === null || $value === '') {
                    return '<span style="color: #6b7280;">-</span>';
                }
                $colors = [
                    'COMPLETED' => ['#dcfce7', '#166534'],
                    'ONGOING' => ['#dbeafe', '#1d4ed8'],
                    'BID EVALUATION/OPENING' => ['#fef3c7', '#92400e'],
                    'NOA ISSUANCE' => ['#ede9fe', '#6b21a8'],
                    'DED PREPARATION' => ['#e0f2fe', '#0369a1'],
                    'NOT YET STARTED' => ['#f3f4f6', '#374151'],
                    'ITB/AD POSTED' => ['#d1fae5', '#065f46'],
                    'TERMINATED' => ['#fee2e2', '#991b1b'],
                    'CANCELLED' => ['#fecaca', '#7f1d1d'],
                    'TERMINATED/CANCEL' => ['#fee2e2', '#991b1b'],
                    'PROCUREMENT' => ['#e0f2fe', '#0369a1'],
                    'Ahead' => ['#dcfce7', '#166534'],
                    'On Schedule' => ['#dbeafe', '#1d4ed8'],
                    'No Risk' => ['#ecfccb', '#3f6212'],
                    'Low Risk' => ['#fef3c7', '#92400e'],
                    'Moderate Risk' => ['#fed7aa', '#9a3412'],
                    'High Risk' => ['#fee2e2', '#991b1b'],
                ];
                $color = $colors[$value] ?? ['#e5e7eb', '#374151'];
                return '<span style="display: inline-block; padding: 3px 8px; border-radius: 999px; background-color: ' . $color[0] . '; color: ' . $color[1] . '; font-size: 11px; font-weight: 600;">' . e($statusLabel($value)) . '</span>';
            };

            $formatPhysicalPercent = function ($value) {
                if ($value === null || $value === '') {
                    return '-';
                }

                return number_format((float) $value, 2) . '%';
            };

            $physicalTrendIndicator = function ($currentValue, $previousValue) {
                if (!is_numeric($currentValue) || !is_numeric($previousValue)) {
                    return '';
                }

                $current = (float) $currentValue;
                $previous = (float) $previousValue;

                if ($current > $previous) {
                    return '<span class="lfp-physical-trend-indicator is-up !text-2xl !font-bold" title="Higher than the previous logged month">&#8593;</span>';
                }

                if ($current < $previous) {
                    return '<span class="lfp-physical-trend-indicator is-down !text-2xl !font-bold" title="Lower than the previous logged month">&#8595;</span>';
                }

                return '';
            };

            $physicalTimelineStart = $project->date_ntp ? $project->date_ntp->copy()->startOfMonth() : null;
            if ($physicalTimelineStart === null && !empty($physicalTimelineByPeriod ?? [])) {
                $firstPhysicalTimelineKey = array_key_first($physicalTimelineByPeriod);
                if ($firstPhysicalTimelineKey) {
                    [$startYear, $startMonth] = array_map('intval', explode('-', $firstPhysicalTimelineKey));
                    $physicalTimelineStart = \Illuminate\Support\Carbon::create($startYear, $startMonth, 1)->startOfMonth();
                }
            }

            $physicalTimelineEnd = now()->startOfMonth();
            if ($physicalTimelineStart && $physicalTimelineStart->greaterThan($physicalTimelineEnd)) {
                $physicalTimelineStart = $physicalTimelineEnd->copy();
            }

            $currentPhysicalTimelineKey = sprintf('%04d-%02d', (int) $currentYear, (int) $currentMonth);
            $physicalTimelineEntries = [];
            $previousPhysicalMetricValues = [
                'accomplishment_pct' => null,
                'accomplishment_pct_ro' => null,
                'slippage' => null,
                'slippage_ro' => null,
            ];

            if ($physicalTimelineStart) {
                $physicalTimelineCursor = $physicalTimelineStart->copy();

                while ($physicalTimelineCursor->lessThanOrEqualTo($physicalTimelineEnd)) {
                    $monthNumber = (int) $physicalTimelineCursor->month;
                    $timelineYear = (int) $physicalTimelineCursor->year;
                    $periodKey = $physicalTimelineCursor->format('Y-m');
                    $row = $physicalTimelineByPeriod[$periodKey] ?? [];

                    $hasData = collect([
                        $row['status_project_fou'] ?? null,
                        $row['status_project_ro'] ?? null,
                        $row['accomplishment_pct'] ?? null,
                        $row['accomplishment_pct_ro'] ?? null,
                        $row['slippage'] ?? null,
                        $row['slippage_ro'] ?? null,
                        $row['risk_aging'] ?? null,
                        $row['nc_letters'] ?? null,
                    ])->contains(function ($value) {
                        return $value !== null && $value !== '';
                    });

                    $physicalTimelineEntries[] = [
                        'timeline_year' => $timelineYear,
                        'period_key' => $periodKey,
                        'month_number' => $monthNumber,
                        'month_label' => $physicalTimelineCursor->format('F'),
                        'month_short' => $physicalTimelineCursor->format('M'),
                        'has_data' => $hasData,
                        'status_project_fou' => $row['status_project_fou'] ?? null,
                        'status_project_ro' => $row['status_project_ro'] ?? null,
                        'accomplishment_pct' => $row['accomplishment_pct'] ?? null,
                        'accomplishment_pct_ro' => $row['accomplishment_pct_ro'] ?? null,
                        'slippage' => $row['slippage'] ?? null,
                        'slippage_ro' => $row['slippage_ro'] ?? null,
                        'risk_aging' => $row['risk_aging'] ?? null,
                        'nc_letters' => $row['nc_letters'] ?? null,
                        'previous_accomplishment_pct' => $previousPhysicalMetricValues['accomplishment_pct'],
                        'previous_accomplishment_pct_ro' => $previousPhysicalMetricValues['accomplishment_pct_ro'],
                        'previous_slippage' => $previousPhysicalMetricValues['slippage'],
                        'previous_slippage_ro' => $previousPhysicalMetricValues['slippage_ro'],
                        'remarks' => $periodKey === $currentPhysicalTimelineKey ? ($project->physical_remarks ?? null) : null,
                    ];

                    foreach (['accomplishment_pct', 'accomplishment_pct_ro', 'slippage', 'slippage_ro'] as $metricField) {
                        if (is_numeric($row[$metricField] ?? null)) {
                            $previousPhysicalMetricValues[$metricField] = $row[$metricField];
                        }
                    }

                    $physicalTimelineCursor->addMonthNoOverflow();
                }
            }

            $physicalTimelineGroups = collect($physicalTimelineEntries)
                ->groupBy('timeline_year')
                ->sortKeysDesc()
                ->map(function ($entries) {
                    return $entries->sortByDesc('month_number')->values();
                });
            $hasMultiplePhysicalTimelineYears = $physicalTimelineGroups->count() > 1;

            $formatFinancialCurrency = function ($value) {
                if ($value === null || $value === '') {
                    return '-';
                }

                return 'PHP ' . number_format((float) $value, 2);
            };

            $formatFinancialPercent = function ($value) {
                if ($value === null || $value === '') {
                    return '-';
                }

                return number_format((float) $value, 2) . '%';
            };

            $financialTrendIndicator = function ($currentValue, $previousValue) {
                if (!is_numeric($currentValue) || !is_numeric($previousValue)) {
                    return '';
                }

                $current = (float) $currentValue;
                $previous = (float) $previousValue;

                if ($current > $previous) {
                    return '<span class="lfp-physical-trend-indicator is-up !text-2xl !font-bold" title="Higher than the previous logged month">&#8593;</span>';
                }

                if ($current < $previous) {
                    return '<span class="lfp-physical-trend-indicator is-down !text-2xl !font-bold" title="Lower than the previous logged month">&#8595;</span>';
                }

                return '';
            };

            $financialAllocation = (float) ($project->lgsf_allocation ?? 0);
            $financialTimelineEntries = [];
            $latestFinancialEntry = null;
            foreach ($months as $monthNumber => $monthName) {
                $row = $financialByMonth[$monthNumber] ?? [];
                $hasData = collect([
                    $row['obligation'] ?? null,
                    $row['disbursed_amount'] ?? null,
                    $row['reverted_amount'] ?? null,
                ])->contains(function ($value) {
                    return $value !== null && $value !== '';
                });

                if (!$hasData) {
                    continue;
                }

                $monthDisbursed = (float) ($row['disbursed_amount'] ?? 0);
                $monthReverted = (float) ($row['reverted_amount'] ?? 0);
                $monthBalance = $financialAllocation - ($monthDisbursed + $monthReverted);
                $monthUtilizationRate = $financialAllocation > 0
                    ? (($monthDisbursed + $monthReverted) / $financialAllocation) * 100
                    : 0;

                $entry = [
                    'month_number' => $monthNumber,
                    'month_label' => $monthName,
                    'month_short' => substr($monthName, 0, 3),
                    'obligation' => $row['obligation'] ?? null,
                    'disbursed_amount' => $row['disbursed_amount'] ?? null,
                    'reverted_amount' => $row['reverted_amount'] ?? null,
                    'balance' => $monthBalance,
                    'utilization_rate' => $monthUtilizationRate,
                    'remarks' => $monthNumber === (int) $currentMonth ? ($project->financial_remarks ?? null) : null,
                ];

                $financialTimelineEntries[] = $entry;
                $latestFinancialEntry = $entry;
            }

            $currentFinancial = $latestFinancialEntry ?? [
                'obligation' => $financialTotals['obligation'] ?? null,
                'disbursed_amount' => $financialTotals['disbursed_amount'] ?? null,
                'reverted_amount' => $financialTotals['reverted_amount'] ?? null,
                'balance' => $financialBalance ?? 0,
                'utilization_rate' => $financialUtilizationRate ?? 0,
            ];

        @endphp

        <div id="physicalAccomplishmentSection" class="project-tab-panel" data-tab-key="physical" role="tabpanel" aria-labelledby="tab-physical-accomplishment" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 class="lfp-physical-section-title" style="color: #00267C; font-size: clamp(14px, 4vw, 18px); font-weight: 700; margin: 0;">Physical Accomplishment</h3>
                <div style="display: flex; gap: 8px; align-items: center;">
                    @if($canUpdateLocallyFundedProject)
                        <a href="#" class="lfp-inline-edit-trigger" data-toggle="inline-edit" data-target="editPhysicalForm" data-physical-toggle="true"><i class="fas fa-edit" aria-hidden="true"></i>Update</a>
                    @endif
                </div>
            </div>
            <div class="lfp-physical-hero">
                <div class="lfp-physical-hero-copy">
                    <span class="lfp-physical-eyebrow">Progress Snapshot</span>
                    <h4 class="lfp-physical-hero-title">Monthly delivery trend across FOU and RO updates</h4>
                    <p class="lfp-physical-hero-text">The timeline below separates the read-only progress history from the edit flow, making it easier to scan how status, accomplishment, slippage, and compliance changed over time.</p>
                </div>
                <div class="lfp-physical-summary-grid">
                    <div class="lfp-physical-summary-card">
                        <span class="lfp-physical-summary-label">Current FOU Status</span>
                        <div class="lfp-physical-summary-value">{!! $statusBadge($currentPhysical['status_project_fou'] ?? null) !!}</div>
                    </div>
                    <div class="lfp-physical-summary-card">
                        <span class="lfp-physical-summary-label">Current RO Status</span>
                        <div class="lfp-physical-summary-value">{!! $statusBadge($currentPhysical['status_project_ro'] ?? null) !!}</div>
                    </div>
                    <div class="lfp-physical-summary-card">
                        <span class="lfp-physical-summary-label">FOU Accomplishment</span>
                        <div class="lfp-physical-summary-value">{{ $formatPhysicalPercent($currentPhysical['accomplishment_pct'] ?? null) }}</div>
                    </div>
                    <div class="lfp-physical-summary-card">
                        <span class="lfp-physical-summary-label">RO Accomplishment</span>
                        <div class="lfp-physical-summary-value">{{ $formatPhysicalPercent($currentPhysical['accomplishment_pct_ro'] ?? null) }}</div>
                    </div>
                    <div class="lfp-physical-summary-card">
                        <span class="lfp-physical-summary-label">Risk As To Aging</span>
                        <div class="lfp-physical-summary-value">{!! $statusBadge($currentPhysical['risk_aging'] ?? null) !!}</div>
                    </div>
                    <div class="lfp-physical-summary-card">
                        <span class="lfp-physical-summary-label">NC Letters</span>
                        <div class="lfp-physical-summary-value">{!! $statusBadge($currentPhysical['nc_letters'] ?? null) !!}</div>
                    </div>
                </div>
            </div>

            <div class="lfp-physical-footer-meta">
                <div>
                    <span>Actual Date of Completion</span>
                    <strong>{{ $project->actual_date_completion ? $project->actual_date_completion->format('F j, Y') : 'N/A' }}</strong>
                </div>
                <div>
                    <span>Last Remarks Update</span>
                    <strong>{{ $project->physical_remarks_updated_at ? $project->physical_remarks_updated_at->format('M d, Y h:i A') : '-' }}</strong>
                </div>
                <div>
                    <span>Updated By</span>
                    <strong>{{ $physicalRemarksUpdatedByName ?? ($actualCompletionUpdatedByName ?? 'N/A') }}</strong>
                </div>
            </div>

            <details class="lfp-physical-timeline-details">
                <summary class="lfp-physical-timeline-summary">View physical timeline</summary>
                @if($physicalTimelineGroups->isEmpty())
                    <div class="lfp-physical-empty-state">No physical accomplishment updates have been logged yet.</div>
                @else
                    <div class="lfp-physical-timeline-year-groups">
                        @foreach($physicalTimelineGroups as $timelineYear => $yearEntries)
                            @php
                                $yearEntries = $yearEntries->values();
                                $firstYearEntry = $yearEntries->sortBy('month_number')->first();
                                $lastYearEntry = $yearEntries->sortByDesc('month_number')->first();
                                $openYearAccordion = !$hasMultiplePhysicalTimelineYears || (int) $timelineYear === (int) $currentYear;
                            @endphp
                            <details class="lfp-physical-year-accordion{{ $hasMultiplePhysicalTimelineYears ? '' : ' is-static' }}" {{ $openYearAccordion ? 'open' : '' }}>
                                <summary class="lfp-physical-year-summary">
                                    <div class="lfp-physical-year-heading">
                                        <span class="lfp-physical-year-label">{{ $timelineYear }}</span>
                                        <span class="lfp-physical-year-range">{{ $firstYearEntry['month_label'] }} to {{ $lastYearEntry['month_label'] }}</span>
                                    </div>
                                    @if($hasMultiplePhysicalTimelineYears)
                                        <span class="lfp-physical-year-count">{{ $yearEntries->count() }} months</span>
                                    @endif
                                </summary>
                                <div class="lfp-physical-year-body">
                                    <div class="lfp-physical-timeline">
                                        @foreach($yearEntries as $entry)
                                            <article class="lfp-physical-timeline-item{{ $entry['has_data'] ? '' : ' is-empty' }}">
                                                <div class="lfp-physical-timeline-node{{ $entry['has_data'] ? '' : ' is-empty' }}">
                                                    <span>{{ $entry['month_short'] }}</span>
                                                </div>
                                                <div class="lfp-physical-timeline-card{{ $entry['has_data'] ? '' : ' is-empty' }}">
                                                    <div class="lfp-physical-timeline-card-header">
                                                        <div>
                                                            <p class="lfp-physical-timeline-kicker">Timeline Point</p>
                                                            <h4>{{ $entry['month_label'] }}</h4>
                                                        </div>
                                                        <span class="lfp-physical-timeline-month">{{ str_pad((string) $entry['month_number'], 2, '0', STR_PAD_LEFT) }}</span>
                                                    </div>
                                                    <button type="button"
                                                            class="lfp-physical-compare-toggle"
                                                            data-physical-compare-trigger="true"
                                                            data-physical-compare-title="{{ $entry['month_label'] }} {{ $timelineYear }} comparison">
                                                        Compare FOU vs RO
                                                    </button>
                                                    @if(!$entry['has_data'])
                                                        <p class="lfp-physical-timeline-note">No monthly update logged yet.</p>
                                                    @endif
                                                    <div class="lfp-physical-timeline-metrics">
                                                        <div class="">
                                                            <div class="lfp-physical-timeline-metric !mb-4">
                                                                <span>Risk</span>
                                                                <strong>{!! $statusBadge($entry['risk_aging']) !!}</strong>
                                                            </div>
                                                            <div class="lfp-physical-timeline-metric !mt-4">
                                                                <span>NC Letters</span>
                                                                <strong>{!! $statusBadge($entry['nc_letters']) !!}</strong>
                                                            </div>
                                                        </div>

                                                        <div class="lfp-physical-timeline-columns">
                                                            <div class="flex flex-col gap-4">
                                                                <div class="lfp-physical-timeline-metric">
                                                                    <span>FOU Status</span>
                                                                    <strong>{!! $statusBadge($entry['status_project_fou']) !!}</strong>
                                                                </div>

                                                                <div class="lfp-physical-timeline-metric">
                                                                    <span>FOU Accomplishment</span>
                                                                    <strong class="lfp-physical-trend">
                                                                        {!! $physicalTrendIndicator($entry['accomplishment_pct'], $entry['previous_accomplishment_pct']) !!}
                                                                        <span>{{ $formatPhysicalPercent($entry['accomplishment_pct']) }}</span>
                                                                    </strong>
                                                                </div>

                                                                <div class="lfp-physical-timeline-metric">
                                                                    <span>FOU Slippage</span>
                                                                    <strong class="lfp-physical-trend">
                                                                        {!! $physicalTrendIndicator($entry['slippage'], $entry['previous_slippage']) !!}
                                                                        <span>{{ $formatPhysicalPercent($entry['slippage']) }}</span>
                                                                    </strong>
                                                                </div>
                                                            </div>

                                                            <div class="flex flex-col gap-4">
                                                                <div class="lfp-physical-timeline-metric">
                                                                    <span>RO Status</span>
                                                                    <strong>{!! $statusBadge($entry['status_project_ro']) !!}</strong>
                                                                </div>

                                                                <div class="lfp-physical-timeline-metric">
                                                                    <span>RO Accomplishment</span>
                                                                    <strong class="lfp-physical-trend">
                                                                        {!! $physicalTrendIndicator($entry['accomplishment_pct_ro'], $entry['previous_accomplishment_pct_ro']) !!}
                                                                        <span>{{ $formatPhysicalPercent($entry['accomplishment_pct_ro']) }}</span>
                                                                    </strong>
                                                                </div>

                                                                <div class="lfp-physical-timeline-metric">
                                                                    <span>RO Slippage</span>
                                                                    <strong class="lfp-physical-trend">
                                                                        {!! $physicalTrendIndicator($entry['slippage_ro'], $entry['previous_slippage_ro']) !!}
                                                                        <span>{{ $formatPhysicalPercent($entry['slippage_ro']) }}</span>
                                                                    </strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @if(!empty($entry['remarks']))
                                                        <div class="lfp-physical-timeline-remarks">
                                                            <span>Remarks</span>
                                                            <p>{{ $entry['remarks'] }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                @endif
            </details>
        </div>

        <div id="physicalCompareModalBackdrop" class="lfp-inline-modal-backdrop" aria-hidden="true"></div>
        <div id="physicalCompareModalWrapper" class="lfp-inline-modal" role="dialog" aria-modal="true" aria-labelledby="physicalCompareModalTitle" aria-hidden="true" style="display: none;">
            <div class="lfp-inline-modal-header">
                <h3 id="physicalCompareModalTitle" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">FOU vs RO Comparison</h3>
                <button type="button" class="lfp-inline-modal-close" id="physicalCompareModalClose" aria-label="Close physical comparison modal">&times;</button>
            </div>
            <div class="lfp-inline-modal-body">
                <div id="physicalCompareModalContent"></div>
            </div>
        </div>

        <div id="editPhysicalFormBackdrop" class="lfp-inline-modal-backdrop{{ old('section') === 'physical' ? ' is-visible' : '' }}" aria-hidden="{{ old('section') === 'physical' ? 'false' : 'true' }}"></div>
        <div id="editPhysicalFormWrapper" class="lfp-inline-modal{{ old('section') === 'physical' ? ' is-visible' : '' }}" data-inline-modal="true" role="dialog" aria-modal="true" aria-labelledby="editPhysicalModalTitle" aria-hidden="{{ old('section') === 'physical' ? 'false' : 'true' }}" style="display: {{ old('section') === 'physical' ? 'block' : 'none' }};">
            <div class="lfp-inline-modal-header">
                <h3 id="editPhysicalModalTitle" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Edit Physical Accomplishment</h3>
                <button type="button" class="lfp-inline-modal-close" data-toggle="inline-cancel" data-target="editPhysicalForm" aria-label="Close physical accomplishment editor">&times;</button>
            </div>
            <div class="lfp-inline-modal-body">
            <div class="lfp-physical-modal-grid">
                <div>
                    <strong>STATUS OF PROJECT (for FOU updating):</strong>
                    {!! $statusBadge($currentPhysical['status_project_fou'] ?? null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['status_project_fou'] ?? '';
                                        $updatedAt = $row && $row['status_project_fou_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['status_project_fou_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['status_project_fou_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <select name="status_project_fou[{{ $monthNumber }}]" data-physical-edit="true" data-month="{{ $monthNumber }}" disabled
                                                style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                            <option value="">-- Select --</option>
                                            @if($value && !in_array($value, $statusOptionValues, true))
                                                <option value="{{ $value }}" selected>{{ $statusLabel($value) }}</option>
                                            @endif
                                            @foreach($statusOptions as $option)
                                                <option value="{{ $option['value'] }}" {{ $value === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
                <div>
                    <strong>STATUS OF PROJECT PER SUBAYBAYAN (for RO updating):</strong>
                    {!! $statusBadge($currentPhysical['status_project_ro'] ?? null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['status_project_ro'] ?? '';
                                        $updatedAt = $row && $row['status_project_ro_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['status_project_ro_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['status_project_ro_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <select name="status_project_ro[{{ $monthNumber }}]" data-physical-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" {{ !(Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office') ? 'disabled' : '' }}
                                                style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                            <option value="">-- Select --</option>
                                            @if($value && !in_array($value, $statusOptionValues, true))
                                                <option value="{{ $value }}" selected>{{ $statusLabel($value) }}</option>
                                            @endif
                                            @foreach($statusOptions as $option)
                                                <option value="{{ $option['value'] }}" {{ $value === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div>
                    <strong>% of Accomplishment (for FOU updating):</strong>
                    {!! $statusBadge(isset($currentPhysical['accomplishment_pct']) ? number_format((float)$currentPhysical['accomplishment_pct'], 2) . '%' : null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['accomplishment_pct'] ?? '';
                                        $updatedAt = $row && $row['accomplishment_pct_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['accomplishment_pct_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['accomplishment_pct_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <input type="number" step="0.01" min="0" max="100" name="accomplishment_pct[{{ $monthNumber }}]" value="{{ $value }}" data-physical-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
                <div>
                    <strong>% of Accomplishment per Subaybayan (for RO updating):</strong>
                    {!! $statusBadge(isset($currentPhysical['accomplishment_pct_ro']) ? number_format((float)$currentPhysical['accomplishment_pct_ro'], 2) . '%' : null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['accomplishment_pct_ro'] ?? '';
                                        $updatedAt = $row && $row['accomplishment_pct_ro_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['accomplishment_pct_ro_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['accomplishment_pct_ro_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <input type="number" step="0.01" min="0" max="100" name="accomplishment_pct_ro[{{ $monthNumber }}]" value="{{ $value }}" data-physical-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" {{ !(Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office') ? 'disabled' : '' }} style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div>
                    <strong>Slippage (for FOU updating):</strong>
                    {!! $statusBadge(isset($currentPhysical['slippage']) ? number_format((float)$currentPhysical['slippage'], 2) . '%' : null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['slippage'] ?? '';
                                        $updatedAt = $row && $row['slippage_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['slippage_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['slippage_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <input type="number" step="0.01" min="0" max="100" name="slippage[{{ $monthNumber }}]" value="{{ $value }}" data-physical-edit="true" data-month="{{ $monthNumber }}" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
                <div>
                    <strong>Slippage as to SubayBAYAN (for RO updating):</strong>
                    {!! $statusBadge(isset($currentPhysical['slippage_ro']) ? number_format((float)$currentPhysical['slippage_ro'], 2) . '%' : null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['slippage_ro'] ?? '';
                                        $updatedAt = $row && $row['slippage_ro_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['slippage_ro_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['slippage_ro_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <input type="number" step="0.01" min="0" max="100" name="slippage_ro[{{ $monthNumber }}]" value="{{ $value }}" data-physical-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" {{ !(Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office') ? 'disabled' : '' }} style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <form method="POST" action="{{ route('locally-funded-project.update', $project) }}" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; margin-top: 6px;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="physical">
                      <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; font-size: 12px; color: #6b7280;">
                        <strong style="font-size: 14px; font-weight: 600; color: #000000;">Actual Date of Completion:</strong>
                        <span>{{ $project->actual_date_completion ? $project->actual_date_completion->format('F j, Y') : 'N/A' }}</span>
                        <span>Updated by: {{ $actualCompletionUpdatedByName ?? 'N/A' }}</span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <input type="date" id="actual_date_completion_physical" name="actual_date_completion" value="{{ old('actual_date_completion', $project->actual_date_completion ? $project->actual_date_completion->format('Y-m-d') : '') }}"
                               data-physical-edit="true" data-month="{{ $currentMonth }}" data-ro-only="true" disabled
                               style="padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                    </div>
                </form>

                <div>
                    <strong>
                        Risk as to aging:
                        <span title="Ahead (+ value of slippage)&#10;On Schedule (0%)&#10;No Risk (-0.01% to -4.99% slippage)&#10;Low Risk (-5% to -9.99% slippage)&#10;Moderate Risk (-10% to -14.99% slippage)&#10;High Risk (-15% and higher slippage)" style="display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; margin-left: 6px; border-radius: 999px; background-color: #e5e7eb; color: #374151; font-size: 11px; font-weight: 700; cursor: help;">i</span>
                    </strong>
                    {!! $statusBadge($currentPhysical['risk_aging'] ?? null) !!}
                    @if((int) $project->id === 25)
                        <span style="margin-left: 6px; color: #6b7280; font-size: 12px; font-weight: 600;">No Update</span>
                    @endif
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['risk_aging'] ?? '';
                                        $updatedAt = $row && $row['risk_aging_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['risk_aging_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['risk_aging_updated_by_name'] ?? '-';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <select name="risk_aging[{{ $monthNumber }}]" data-physical-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" {{ !(Auth::user()->agency === 'DILG' && Auth::user()->province === 'Regional Office') ? 'disabled' : '' }}
                                                style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                            <option value="">-- Select --</option>
                                            @if($value !== '' && !in_array($value, ['Ahead', 'On Schedule', 'No Risk', 'Low Risk', 'Moderate Risk', 'High Risk'], true))
                                                <option value="{{ $value }}" selected>{{ $value }}</option>
                                            @endif
                                            <option value="Ahead" {{ $value === 'Ahead' ? 'selected' : '' }}>Ahead</option>
                                            <option value="On Schedule" {{ $value === 'On Schedule' ? 'selected' : '' }}>On Schedule</option>
                                            <option value="No Risk" {{ $value === 'No Risk' ? 'selected' : '' }}>No Risk</option>
                                            <option value="Low Risk" {{ $value === 'Low Risk' ? 'selected' : '' }}>Low Risk</option>
                                            <option value="Moderate Risk" {{ $value === 'Moderate Risk' ? 'selected' : '' }}>Moderate Risk</option>
                                            <option value="High Risk" {{ $value === 'High Risk' ? 'selected' : '' }}>High Risk</option>
                                        </select>
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div>
                    <strong>Issued with Non-Compliance (NC) Letters:</strong>
                    {!! $statusBadge($currentPhysical['nc_letters'] ?? null) !!}
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Status</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="physical">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @php
                                        $monthNumber = (int) $currentMonth;
                                        $monthName = $months[$monthNumber] ?? now()->format('F');
                                        $row = $physicalByMonth[$monthNumber] ?? null;
                                        $value = $row['nc_letters'] ?? '';
                                        $updatedAt = $row && $row['nc_letters_updated_at']
                                            ? \Illuminate\Support\Carbon::parse($row['nc_letters_updated_at'])->format('M d, Y h:i A')
                                            : '-';
                                        $updatedBy = $row['nc_letters_updated_by_name'] ?? '-';
                                        $ncColors = [
                                            'NC No. 1' => '#fef3c7',
                                            'NC No. 2' => '#fde68a',
                                            'NC No. 3' => '#fecaca',
                                            'No' => '#dcfce7',
                                        ];
                                        $ncTextColors = [
                                            'NC No. 1' => '#92400e',
                                            'NC No. 2' => '#78350f',
                                            'NC No. 3' => '#991b1b',
                                            'No' => '#166534',
                                        ];
                                        $bgColor = $ncColors[$value] ?? '#f3f4f6';
                                        $textColor = $ncTextColors[$value] ?? '#374151';
                                    @endphp
                                    <div>{{ $monthName }}</div>
                                    <div>
                                        <select name="nc_letters[{{ $monthNumber }}]" data-physical-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" disabled
                                                style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: {{ $bgColor }}; color: {{ $textColor }};">
                                            <option value="">-- Select --</option>
                                            <option value="NC No. 1" {{ $value === 'NC No. 1' ? 'selected' : '' }}>NC No. 1</option>
                                            <option value="NC No. 2" {{ $value === 'NC No. 2' ? 'selected' : '' }}>NC No. 2</option>
                                            <option value="NC No. 3" {{ $value === 'NC No. 3' ? 'selected' : '' }}>NC No. 3</option>
                                            <option value="No" {{ $value === 'No' ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                    <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>
                <div>
                    <strong>Remarks:</strong>
                    <form method="POST" action="{{ route('locally-funded-project.update', $project) }}" style="margin-top: 8px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="physical">
                        <textarea name="physical_remarks" rows="3" data-physical-edit="true" data-month="{{ $currentMonth }}" data-ro-only="true" disabled
                                  style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; resize: vertical; background-color: #f3f4f6;">{{ old('physical_remarks', $project->physical_remarks) }}</textarea>
                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                            <span><strong>Updated By:</strong> {{ $physicalRemarksUpdatedByName ?? '-' }}</span>
                            <span><strong>Date & Time:</strong> {{ $project->physical_remarks_updated_at ? $project->physical_remarks_updated_at->format('M d, Y h:i A') : '-' }}</span>
                        </div>
                    </form>
                </div>
            </div>
            <div class="lfp-inline-section-footer" style="display: flex;">
                <button type="button" class="lfp-inline-section-save" data-inline-section-save="editPhysicalForm"><i class="fas fa-check" style="margin-right: 8px;" aria-hidden="true"></i>Save Changes</button>
                <button type="button" class="lfp-inline-section-cancel" data-inline-section-cancel="editPhysicalForm"><i class="fas fa-times" style="margin-right: 8px;" aria-hidden="true"></i>Cancel</button>
            </div>
            </div>
        </div>

        <div id="financialAccomplishmentSection" class="project-tab-panel" data-tab-key="financial" role="tabpanel" aria-labelledby="tab-financial-accomplishment" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 class="lfp-physical-section-title" style="color: #00267C; font-size: clamp(14px, 4vw, 18px); font-weight: 700; margin: 0;">Financial Accomplishment (based on Subaybayan)</h3>
                <div class="lfp-financial-section-actions" style="display: flex; gap: 8px; align-items: center;">
                    @if($canUpdateLocallyFundedProject)
                        <a href="#" class="lfp-inline-edit-trigger" data-toggle="inline-edit" data-target="editFinancialForm" data-financial-toggle="true"><i class="fas fa-edit" aria-hidden="true"></i>Update</a>
                    @endif
                </div>
            </div>
            <div class="lfp-financial-view-stack">
                <div class="lfp-physical-hero">
                    <div class="lfp-physical-hero-copy">
                        <span class="lfp-physical-eyebrow">Funding Snapshot</span>
                        <h4 class="lfp-physical-hero-title">Monthly financial movement across obligation, disbursement, and balance</h4>
                        <p class="lfp-physical-hero-text">The financial timeline mirrors the physical accomplishment view so you can scan each logged month, compare movement, and still use the existing inline update flow below.</p>
                    </div>
                    <div class="lfp-physical-summary-grid">
                        <div class="lfp-physical-summary-card">
                            <span class="lfp-physical-summary-label">Obligated Amount</span>
                            <div class="lfp-physical-summary-value" id="financialSum-obligation">{{ number_format((float) ($financialTotals['obligation'] ?? 0), 2) }}</div>
                        </div>
                        <div class="lfp-physical-summary-card">
                            <span class="lfp-physical-summary-label">Disbursed Amount</span>
                            <div class="lfp-physical-summary-value" id="financialSum-disbursed_amount">{{ number_format((float) ($financialTotals['disbursed_amount'] ?? 0), 2) }}</div>
                        </div>
                        <div class="lfp-physical-summary-card">
                            <span class="lfp-physical-summary-label">Reverted Amount</span>
                            <div class="lfp-physical-summary-value" id="financialSum-reverted_amount">{{ number_format((float) ($financialTotals['reverted_amount'] ?? 0), 2) }}</div>
                        </div>
                        <div class="lfp-physical-summary-card">
                            <span class="lfp-physical-summary-label">Remaining Balance</span>
                            <div class="lfp-physical-summary-value" id="financialBalance">{{ number_format((float) $financialBalance, 2) }}</div>
                        </div>
                        <div class="lfp-physical-summary-card">
                            <span class="lfp-physical-summary-label">Utilization Rate</span>
                            <div class="lfp-physical-summary-value" id="financialUtilizationRate" style="color: {{ (float) $financialUtilizationRate < 100 ? '#dc2626' : '#111827' }};">{{ number_format((float) $financialUtilizationRate, 2) . '%' }}</div>
                        </div>
                        <div class="lfp-physical-summary-card">
                            <span class="lfp-physical-summary-label">Latest Logged Month</span>
                            <div class="lfp-physical-summary-value">{{ $latestFinancialEntry['month_label'] ?? $months[$currentMonth] }}</div>
                        </div>
                    </div>
                </div>

                <div class="lfp-physical-footer-meta">
                    <div>
                        <span>LGSF Allocation</span>
                        <strong>{{ $formatFinancialCurrency($project->lgsf_allocation) }}</strong>
                    </div>
                    <div>
                        <span>Last Remarks Update</span>
                        <strong>{{ $project->financial_remarks_updated_at ? $project->financial_remarks_updated_at->format('M d, Y h:i A') : '-' }}</strong>
                    </div>
                    <div>
                        <span>Updated By</span>
                        <strong>{{ $financialRemarksUpdatedByName ?? 'N/A' }}</strong>
                    </div>
                </div>

                <details class="lfp-physical-timeline-details">
                    <summary class="lfp-physical-timeline-summary">View financial timeline</summary>
                    <div class="lfp-physical-timeline">
                        @forelse($financialTimelineEntries as $entry)
                            @php
                                $previousEntry = $loop->first ? null : ($financialTimelineEntries[$loop->index - 1] ?? null);
                            @endphp
                            <article class="lfp-physical-timeline-item">
                                <div class="lfp-physical-timeline-node">
                                    <span>{{ $entry['month_short'] }}</span>
                                </div>
                                <div class="lfp-physical-timeline-card">
                                    <div class="lfp-physical-timeline-card-header">
                                        <div>
                                            <p class="lfp-physical-timeline-kicker">Timeline Point</p>
                                            <h4>{{ $entry['month_label'] }}</h4>
                                        </div>
                                        <span class="lfp-physical-timeline-month">{{ str_pad((string) $entry['month_number'], 2, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <div class="lfp-financial-timeline-metrics">
                                        <div class="lfp-physical-timeline-metric">
                                            <span>Balance</span>
                                            <strong>{{ $formatFinancialCurrency($entry['balance']) }}</strong>
                                        </div>

                                        <div class="lfp-physical-timeline-metric">
                                            <span>Obligation</span>
                                            <strong class="lfp-physical-trend">
                                                {!! $financialTrendIndicator($entry['obligation'], $previousEntry['obligation'] ?? null) !!}
                                                <span>{{ $formatFinancialCurrency($entry['obligation']) }}</span>
                                            </strong>
                                        </div>

                                        <div class="lfp-physical-timeline-metric">
                                            <span>Disbursed</span>
                                            <strong class="lfp-physical-trend">
                                                {!! $financialTrendIndicator($entry['disbursed_amount'], $previousEntry['disbursed_amount'] ?? null) !!}
                                                <span>{{ $formatFinancialCurrency($entry['disbursed_amount']) }}</span>
                                            </strong>
                                        </div>

                                        <div class="lfp-physical-timeline-metric">
                                            <span>Reverted</span>
                                            <strong class="lfp-physical-trend">
                                                {!! $financialTrendIndicator($entry['reverted_amount'], $previousEntry['reverted_amount'] ?? null) !!}
                                                <span>{{ $formatFinancialCurrency($entry['reverted_amount']) }}</span>
                                            </strong>
                                        </div>

                                        <div class="lfp-physical-timeline-metric">
                                            <span>Utilization Rate</span>
                                            <strong class="lfp-physical-trend">
                                                {!! $financialTrendIndicator($entry['utilization_rate'], $previousEntry['utilization_rate'] ?? null) !!}
                                                <span>{{ $formatFinancialPercent($entry['utilization_rate']) }}</span>
                                            </strong>
                                        </div>
                                    </div>
                                    @if(!empty($entry['remarks']))
                                        <div class="lfp-physical-timeline-remarks">
                                            <span>Remarks</span>
                                            <p>{{ $entry['remarks'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="lfp-physical-empty-state">No financial accomplishment updates have been logged yet.</div>
                        @endforelse
                    </div>
                </details>
            </div>
        </div>

        <div id="editFinancialFormBackdrop" class="lfp-inline-modal-backdrop{{ old('section') === 'financial' ? ' is-visible' : '' }}" aria-hidden="{{ old('section') === 'financial' ? 'false' : 'true' }}"></div>
        <div id="editFinancialFormWrapper" class="lfp-inline-modal{{ old('section') === 'financial' ? ' is-visible' : '' }}" data-inline-modal="true" role="dialog" aria-modal="true" aria-labelledby="editFinancialModalTitle" aria-hidden="{{ old('section') === 'financial' ? 'false' : 'true' }}" style="display: {{ old('section') === 'financial' ? 'block' : 'none' }};">
            <div class="lfp-inline-modal-header">
                <h3 id="editFinancialModalTitle" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Edit Financial Accomplishment</h3>
                <button type="button" class="lfp-inline-modal-close" data-toggle="inline-cancel" data-target="editFinancialForm" aria-label="Close financial accomplishment editor">&times;</button>
            </div>
            <div class="lfp-inline-modal-body">
            <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                <div>
                    <strong>Obligated Amount:</strong>
                    <span>{{ number_format((float) ($financialTotals['obligation'] ?? 0), 2) }}</span>
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Value</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="financial">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        @php
                                            $row = $financialByMonth[$monthNumber] ?? null;
                                            $value = $row['obligation'] ?? '';
                                            $updatedAt = $row && $row['obligation_updated_at']
                                                ? \Illuminate\Support\Carbon::parse($row['obligation_updated_at'])->format('M d, Y h:i A')
                                                : '-';
                                            $updatedBy = $row && $row['obligation_updated_by']
                                                ? \Illuminate\Support\Facades\DB::table('tbusers')->where('idno', $row['obligation_updated_by'])->value(\Illuminate\Support\Facades\DB::raw("concat(fname, ' ', lname)"))
                                                : '-';
                                        @endphp
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" name="obligation[{{ $monthNumber }}]" value="{{ $value }}" placeholder="-" data-financial-field="obligation" data-financial-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                    @endforeach
                                </div>
                                <div style="margin-top: 10px;">
                                    <button type="submit" data-financial-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div>
                    <strong>Disbursed Amount:</strong>
                    <span>{{ number_format((float) ($financialTotals['disbursed_amount'] ?? 0), 2) }}</span>
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Value</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="financial">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        @php
                                            $row = $financialByMonth[$monthNumber] ?? null;
                                            $value = $row['disbursed_amount'] ?? '';
                                            $updatedAt = $row && $row['disbursed_amount_updated_at']
                                                ? \Illuminate\Support\Carbon::parse($row['disbursed_amount_updated_at'])->format('M d, Y h:i A')
                                                : '-';
                                            $updatedBy = $row && $row['disbursed_amount_updated_by']
                                                ? \Illuminate\Support\Facades\DB::table('tbusers')->where('idno', $row['disbursed_amount_updated_by'])->value(\Illuminate\Support\Facades\DB::raw("concat(fname, ' ', lname)"))
                                                : '-';
                                        @endphp
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" name="disbursed_amount[{{ $monthNumber }}]" value="{{ $value }}" placeholder="-" data-financial-field="disbursed_amount" data-financial-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                    @endforeach
                                </div>
                                <div style="margin-top: 10px;">
                                    <button type="submit" data-financial-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div>
                    <strong>Reverted Amount:</strong>
                    <span>{{ number_format((float) ($financialTotals['reverted_amount'] ?? 0), 2) }}</span>
                    <details class="monthly-details" style="margin-top: 8px;">
                        <summary class="monthly-summary" style="cursor: pointer; color: #1d4ed8; background-color: #e0e7ff; border: 1px solid #c7d2fe; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">View monthly Status</summary>
                        <div style="margin-top: 10px;">
                            <div style="display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase;">
                                <div>Month</div>
                                <div>Value</div>
                                <div>Date & Time</div>
                                <div>Updated By</div>
                            </div>
                            <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="section" value="financial">
                                <div style="margin-top: 6px; color: #6b7280; display: grid; grid-template-columns: 120px 1fr 180px 140px; gap: 8px;">
                                    @foreach($months as $monthNumber => $monthName)
                                        @php
                                            $row = $financialByMonth[$monthNumber] ?? null;
                                            $value = $row['reverted_amount'] ?? '';
                                            $updatedAt = $row && $row['reverted_amount_updated_at']
                                                ? \Illuminate\Support\Carbon::parse($row['reverted_amount_updated_at'])->format('M d, Y h:i A')
                                                : '-';
                                            $updatedBy = $row && $row['reverted_amount_updated_by']
                                                ? \Illuminate\Support\Facades\DB::table('tbusers')->where('idno', $row['reverted_amount_updated_by'])->value(\Illuminate\Support\Facades\DB::raw("concat(fname, ' ', lname)"))
                                                : '-';
                                        @endphp
                                        <div>{{ $monthName }}</div>
                                        <div>
                                            <input type="number" step="0.01" min="0" name="reverted_amount[{{ $monthNumber }}]" value="{{ $value }}" placeholder="-" data-financial-field="reverted_amount" data-financial-edit="true" data-month="{{ $monthNumber }}" data-ro-only="true" disabled style="width: 100%; min-width: 0; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; background-color: #f3f4f6;">
                                        </div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #f3f4f6; color: #374151; font-size: 11px; font-weight: 600;">{{ $updatedAt }}</span></div>
                                        <div><span style="display: inline-block; padding: 3px 8px; border-radius: 999px; border: 1px solid #e5e7eb; background-color: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600;">{{ $updatedBy }}</span></div>
                                    @endforeach
                                </div>
                                <div style="margin-top: 10px;">
                                    <button type="submit" data-financial-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                                </div>
                            </form>
                        </div>
                    </details>
                </div>

                <div>
                    <strong>Balance:</strong>
                    <span>{{ number_format((float) $financialBalance, 2) }}</span>
                </div>

                <div>
                    <strong>Utilization Rate:</strong>
                    <span style="color: {{ (float) $financialUtilizationRate < 100 ? '#dc2626' : '#111827' }};">{{ number_format((float) $financialUtilizationRate, 2) . '%' }}</span>
                </div>

                <div>
                    <strong>Remarks:</strong>
                    <form method="POST" action="{{ route('locally-funded-project.update', $project) }}" style="margin-top: 8px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="financial">
                        <textarea name="financial_remarks" rows="3" data-financial-edit="true" data-month="{{ $currentMonth }}" data-ro-only="true" disabled
                                  style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; resize: vertical; background-color: #f3f4f6;">{{ old('financial_remarks', $project->financial_remarks) }}</textarea>
                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                            <span><strong>Updated By:</strong> {{ $financialRemarksUpdatedByName ?? '-' }}</span>
                            <span><strong>Date & Time:</strong> {{ $project->financial_remarks_updated_at ? $project->financial_remarks_updated_at->format('M d, Y h:i A') : '-' }}</span>
                        </div>
                        <div style="margin-top: 8px;">
                            <button type="submit" data-financial-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                        </div>
                    </form>
                </div>
            </div>
            </div>
        </div>

        <div id="editMonitoringFormBackdrop" class="lfp-inline-modal-backdrop" aria-hidden="true"></div>
        <div id="monitoringInspectionSection" class="project-tab-panel lfp-inline-modal-section" data-inline-modal-section="true" data-inline-target="editMonitoringForm" data-tab-key="monitoring" role="tabpanel" aria-labelledby="tab-monitoring-inspection" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 data-inline-section-heading="true" data-view-title="Monitoring/Inspection Activities" data-edit-title="Edit Monitoring/Inspection Activities" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Monitoring/Inspection Activities</h3>
                <div style="display: flex; gap: 8px; align-items: center;">
                    @if($canUpdateLocallyFundedProject)
                        <a href="#" class="lfp-inline-edit-trigger" data-toggle="inline-edit" data-target="editMonitoringForm" data-monitoring-toggle="true"><i class="fas fa-edit" aria-hidden="true"></i>Update</a>
                    @endif
                    <button type="button" class="lfp-inline-modal-close lfp-inline-modal-section-close" data-toggle="inline-cancel" data-target="editMonitoringForm" aria-label="Close monitoring editor">&times;</button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                <div style="padding: 16px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #00267C;">DILG Provincial Office Activity</h4>
                    <div style="display: grid; gap: 12px;">
                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="po_monitoring_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date of Monitoring by PO</label>
                            <input type="date" id="po_monitoring_date" name="po_monitoring_date" value="{{ old('po_monitoring_date', $project->po_monitoring_date ? $project->po_monitoring_date->format('Y-m-d') : '') }}"
                                   data-monitoring-edit="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $poMonitoringDateUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->po_monitoring_date_updated_at ? $project->po_monitoring_date_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="submit" data-monitoring-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="po_final_inspection" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">PO Conducted Final Inspection?</label>
                            <select id="po_final_inspection" name="po_final_inspection" data-monitoring-edit="true" disabled
                                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                                <option value="">-- Select --</option>
                                <option value="Yes" {{ old('po_final_inspection', $project->po_final_inspection) === 'Yes' ? 'selected' : '' }}>Yes</option>
                                <option value="No" {{ old('po_final_inspection', $project->po_final_inspection) === 'No' ? 'selected' : '' }}>No</option>
                            </select>
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $poFinalInspectionUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->po_final_inspection_updated_at ? $project->po_final_inspection_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="submit" data-monitoring-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="po_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                            <textarea id="po_remarks" name="po_remarks" rows="3" data-monitoring-edit="true" disabled
                                      style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical; background-color: #f3f4f6;">{{ old('po_remarks', $project->po_remarks) }}</textarea>
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $poRemarksUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->po_remarks_updated_at ? $project->po_remarks_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="submit" data-monitoring-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div style="padding: 16px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #00267C;">DILG Regional Office Activity</h4>
                    <div style="display: grid; gap: 12px;">
                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="ro_monitoring_date" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date of Monitoring by RO</label>
                            <input type="date" id="ro_monitoring_date" name="ro_monitoring_date" value="{{ old('ro_monitoring_date', $project->ro_monitoring_date ? $project->ro_monitoring_date->format('Y-m-d') : '') }}"
                                   data-monitoring-edit="true" data-ro-only="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $roMonitoringDateUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->ro_monitoring_date_updated_at ? $project->ro_monitoring_date_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="submit" data-monitoring-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="ro_final_inspection" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">RO Conducted Final Inspection?</label>
                            <select id="ro_final_inspection" name="ro_final_inspection" data-monitoring-edit="true" data-ro-only="true" disabled
                                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                                <option value="">-- Select --</option>
                                <option value="Yes" {{ old('ro_final_inspection', $project->ro_final_inspection) === 'Yes' ? 'selected' : '' }}>Yes</option>
                                <option value="No" {{ old('ro_final_inspection', $project->ro_final_inspection) === 'No' ? 'selected' : '' }}>No</option>
                            </select>
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $roFinalInspectionUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->ro_final_inspection_updated_at ? $project->ro_final_inspection_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="submit" data-monitoring-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="ro_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                            <textarea id="ro_remarks" name="ro_remarks" rows="3" data-monitoring-edit="true" data-ro-only="true" disabled
                                      style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical; background-color: #f3f4f6;">{{ old('ro_remarks', $project->ro_remarks) }}</textarea>
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $roRemarksUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->ro_remarks_updated_at ? $project->ro_remarks_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            <div style="margin-top: 8px;">
                                <button type="submit" data-monitoring-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="editPostImplementationFormBackdrop" class="lfp-inline-modal-backdrop" aria-hidden="true"></div>
        <div id="postImplementationSection" class="project-tab-panel lfp-inline-modal-section" data-inline-modal-section="true" data-inline-target="editPostImplementationForm" data-tab-key="post-implementation" role="tabpanel" aria-labelledby="tab-post-implementation" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 data-inline-section-heading="true" data-view-title="Post Implementation Requirements" data-edit-title="Edit Post Implementation Requirements" style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Post Implementation Requirements</h3>
                <div style="display: flex; gap: 8px; align-items: center;">
                    @if($canUpdateLocallyFundedProject)
                        <a href="#" class="lfp-inline-edit-trigger" data-toggle="inline-edit" data-target="editPostImplementationForm" data-post-implementation-toggle="true"><i class="fas fa-edit" aria-hidden="true"></i>Update</a>
                    @endif
                    <button type="button" class="lfp-inline-modal-close lfp-inline-modal-section-close" data-toggle="inline-cancel" data-target="editPostImplementationForm" aria-label="Close post implementation editor">&times;</button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
                <div style="padding: 14px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #00267C;">PCR Submission</h4>
                    <div style="display: grid; gap: 12px;">
                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="pcr_submission_deadline" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Deadline of PCR Submission</label>
                            <input type="date" id="pcr_submission_deadline" name="pcr_submission_deadline" value="{{ old('pcr_submission_deadline', $effectivePcrSubmissionDeadline ? $effectivePcrSubmissionDeadline->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" data-ro-only="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($pcrSubmissionDeadlineUpdatedByName || $project->pcr_submission_deadline_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $pcrSubmissionDeadlineUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->pcr_submission_deadline_updated_at ? $project->pcr_submission_deadline_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="pcr_date_submitted_to_po" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Submitted to PO</label>
                            <input type="date" id="pcr_date_submitted_to_po" name="pcr_date_submitted_to_po" value="{{ old('pcr_date_submitted_to_po', $project->pcr_date_submitted_to_po ? $project->pcr_date_submitted_to_po->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($pcrDateSubmittedToPoUpdatedByName || $project->pcr_date_submitted_to_po_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $pcrDateSubmittedToPoUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->pcr_date_submitted_to_po_updated_at ? $project->pcr_date_submitted_to_po_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="pcr_mov_file" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Upload PCR MOV</label>
                            <input type="file" id="pcr_mov_file" name="pcr_mov_file" accept="application/pdf,image/*"
                                   data-post-implementation-edit="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($project->pcr_mov_file_path)
                                <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                    <a href="{{ route('locally-funded-project.view-pcr-mov', $project) }}" target="_blank" style="padding: 4px 8px; background-color: #0369a1; color: white; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: 600;">
                                        <i class="fas fa-eye" style="margin-right: 4px;"></i>View
                                    </a>
                                    <span>Uploaded: {{ basename($project->pcr_mov_file_path) }}</span>
                                </div>
                            @endif
                            @if($project->pcr_mov_uploaded_at)
                                <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                    <span><strong>Submitted By:</strong> {{ $pcrMovUploadedByName ?? '-' }}</span>
                                    <span><strong>Date & Time:</strong> {{ $project->pcr_mov_uploaded_at->format('M d, Y h:i A') }}</span>
                                </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="pcr_date_received_by_ro" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Received by RO</label>
                            <input type="date" id="pcr_date_received_by_ro" name="pcr_date_received_by_ro" value="{{ old('pcr_date_received_by_ro', $project->pcr_date_received_by_ro ? $project->pcr_date_received_by_ro->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" data-ro-only="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($pcrDateReceivedByRoUpdatedByName || $project->pcr_date_received_by_ro_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $pcrDateReceivedByRoUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->pcr_date_received_by_ro_updated_at ? $project->pcr_date_received_by_ro_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="pcr_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                            <textarea id="pcr_remarks" name="pcr_remarks" rows="3" data-post-implementation-edit="true" disabled
                                      style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical; background-color: #f3f4f6;">{{ old('pcr_remarks', $project->pcr_remarks) }}</textarea>
                            @if($pcrRemarksUpdatedByName || $project->pcr_remarks_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $pcrRemarksUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->pcr_remarks_updated_at ? $project->pcr_remarks_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div style="padding: 14px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
                    <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: #00267C;">RSSA Report</h4>
                    <div style="display: grid; gap: 12px;">
                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="rssa_report_deadline" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Deadline of RSSA Report</label>
                            <input type="date" id="rssa_report_deadline" name="rssa_report_deadline" value="{{ old('rssa_report_deadline', $effectiveRssaReportDeadline ? $effectiveRssaReportDeadline->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" data-ro-only="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($rssaReportDeadlineUpdatedByName || $project->rssa_report_deadline_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $rssaReportDeadlineUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->rssa_report_deadline_updated_at ? $project->rssa_report_deadline_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="rssa_submission_status" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Status of Submission</label>
                            <select id="rssa_submission_status" name="rssa_submission_status" data-post-implementation-edit="true" disabled
                                    style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                                <option value="">-- Select Status --</option>
                                <option value="Not yet assessed" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Not yet assessed' ? 'selected' : '' }}>Not yet assessed</option>
                                <option value="Draft" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Draft' ? 'selected' : '' }}>Draft</option>
                                <option value="Returned" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Returned' ? 'selected' : '' }}>Returned</option>
                                <option value="Submitted to C/MLGOO" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Submitted to C/MLGOO' ? 'selected' : '' }}>Submitted to C/MLGOO</option>
                                <option value="Submitted to PO" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Submitted to PO' ? 'selected' : '' }}>Submitted to PO</option>
                                <option value="Submitted to RO" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Submitted to RO' ? 'selected' : '' }}>Submitted to RO</option>
                                <option value="Submitted to PMED" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Submitted to PMED' ? 'selected' : '' }}>Submitted to PMED</option>
                                <option value="Vetted" {{ old('rssa_submission_status', $project->rssa_submission_status) === 'Vetted' ? 'selected' : '' }}>Vetted</option>
                            </select>
                            @if($rssaSubmissionStatusUpdatedByName || $project->rssa_submission_status_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $rssaSubmissionStatusUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->rssa_submission_status_updated_at ? $project->rssa_submission_status_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="rssa_date_submitted_to_po" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Submitted to PO</label>
                            <input type="date" id="rssa_date_submitted_to_po" name="rssa_date_submitted_to_po" value="{{ old('rssa_date_submitted_to_po', $project->rssa_date_submitted_to_po ? $project->rssa_date_submitted_to_po->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($rssaDateSubmittedToPoUpdatedByName || $project->rssa_date_submitted_to_po_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $rssaDateSubmittedToPoUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->rssa_date_submitted_to_po_updated_at ? $project->rssa_date_submitted_to_po_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="rssa_date_received_by_ro" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Received by RO</label>
                            <input type="date" id="rssa_date_received_by_ro" name="rssa_date_received_by_ro" value="{{ old('rssa_date_received_by_ro', $project->rssa_date_received_by_ro ? $project->rssa_date_received_by_ro->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" data-ro-only="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($rssaDateReceivedByRoUpdatedByName || $project->rssa_date_received_by_ro_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $rssaDateReceivedByRoUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->rssa_date_received_by_ro_updated_at ? $project->rssa_date_received_by_ro_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="rssa_date_submitted_to_co" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Date Submitted to CO</label>
                            <input type="date" id="rssa_date_submitted_to_co" name="rssa_date_submitted_to_co" value="{{ old('rssa_date_submitted_to_co', $project->rssa_date_submitted_to_co ? $project->rssa_date_submitted_to_co->format('Y-m-d') : '') }}"
                                   data-post-implementation-edit="true" data-ro-only="true" disabled
                                   style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; background-color: #f3f4f6;">
                            @if($rssaDateSubmittedToCoUpdatedByName || $project->rssa_date_submitted_to_co_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $rssaDateSubmittedToCoUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->rssa_date_submitted_to_co_updated_at ? $project->rssa_date_submitted_to_co_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('locally-funded-project.update', $project) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="section" value="monitoring">
                            <label for="rssa_remarks" style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px;">Remarks</label>
                            <textarea id="rssa_remarks" name="rssa_remarks" rows="3" data-post-implementation-edit="true" disabled
                                      style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; box-sizing: border-box; resize: vertical; background-color: #f3f4f6;">{{ old('rssa_remarks', $project->rssa_remarks) }}</textarea>
                            @if($rssaRemarksUpdatedByName || $project->rssa_remarks_updated_at)
                            <div style="display: flex; gap: 12px; align-items: center; margin-top: 8px; font-size: 12px; color: #6b7280; flex-wrap: wrap;">
                                <span><strong>Updated By:</strong> {{ $rssaRemarksUpdatedByName ?? '-' }}</span>
                                <span><strong>Date & Time:</strong> {{ $project->rssa_remarks_updated_at ? $project->rssa_remarks_updated_at->format('M d, Y h:i A') : '-' }}</span>
                            </div>
                            @endif
                            <div style="margin-top: 8px;">
                                <button type="submit" data-post-implementation-save="true" style="display: none; padding: 6px 12px; background-color: #16a34a; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;"><i class="fas fa-check" style="margin-right: 4px;"></i>Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @php
            $galleryButtons = ['All', 'Before', 'Project Billboard', 'Community Billboard', '20-40%', '50-70%', '90%', 'Completed', 'During'];
        @endphp
        <div id="gallerySection" class="project-tab-panel" data-tab-key="gallery" role="tabpanel" aria-labelledby="tab-gallery" style="margin-bottom: 24px; padding: 20px; border: 1px solid #00267C; border-radius: 10px; background-color: #ffffff;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; border-bottom: 2px solid #00267C; padding-bottom: 10px;">
                <h3 style="color: #00267C; font-size: 15px; font-weight: 700; margin: 0;">Gallery</h3>
            </div>

            <div class="lfp-gallery-layout">
                <aside class="lfp-gallery-sidebar">
                    <div class="lfp-gallery-sidebar-buttons" role="tablist" aria-label="Gallery categories">
                        @foreach ($galleryButtons as $index => $buttonLabel)
                            @php
                                $gallerySlug = \Illuminate\Support\Str::slug($buttonLabel);
                                $galleryTabId = 'gallery-tab-' . $gallerySlug;
                                $galleryPanelId = 'gallery-panel-' . $gallerySlug;
                            @endphp
                            <button
                                type="button"
                                id="{{ $galleryTabId }}"
                                class="lfp-gallery-sidebar-button{{ $index === 0 ? ' is-active' : '' }}"
                                data-gallery-tab-target="{{ $galleryPanelId }}"
                                role="tab"
                                aria-controls="{{ $galleryPanelId }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                tabindex="{{ $index === 0 ? '0' : '-1' }}"
                            >
                                {{ $buttonLabel }}
                            </button>
                        @endforeach
                    </div>
                </aside>

                <div class="lfp-gallery-stage">
                    <div class="lfp-gallery-sidebar-buttons">
                        @foreach ($galleryButtons as $index => $buttonLabel)
                            @php
                                $gallerySlug = \Illuminate\Support\Str::slug($buttonLabel);
                                $galleryTabId = 'gallery-tab-' . $gallerySlug;
                                $galleryPanelId = 'gallery-panel-' . $gallerySlug;
                            @endphp
                            <div
                                id="{{ $galleryPanelId }}"
                                class="lfp-gallery-panel{{ $index === 0 ? ' is-active' : '' }}"
                                role="tabpanel"
                                aria-labelledby="{{ $galleryTabId }}"
                                aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                            ></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        </div>
    </div>
        <div id="activityLogSection" role="dialog" aria-modal="true" aria-labelledby="activityLogTitle" aria-hidden="true">
            <div style="display: flex; flex-direction: column; height: 100%;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 18px 24px 16px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); border-radius: 12px 12px 0 0; flex-shrink: 0;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-clipboard-list" style="color: white; font-size: 14px;"></i>
                        </div>
                        <h3 id="activityLogTitle" style="color: white; font-size: 16px; font-weight: 700; margin: 0;">Activity Logs</h3>
                    </div>
                    <button type="button" id="activityLogClose" aria-label="Close activity logs" style="border: none; background: rgba(255,255,255,0.15); color: white; width: 30px; height: 30px; border-radius: 999px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; transition: background 0.2s;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div style="padding: 20px 24px; overflow-y: auto; max-height: 65vh;">
                    @if(empty($activityLogs))
                        <div style="padding: 40px 20px; text-align: center;">
                            <i class="fas fa-clipboard" style="font-size: 36px; margin-bottom: 12px; display: block; color: #d1d5db;"></i>
                            <div style="font-size: 14px; font-weight: 600; color: #6b7280;">No activity logs found for this project.</div>
                        </div>
                    @else
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                                <thead>
                                    <tr style="background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
                                        <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap;">Date/Time</th>
                                        <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">User</th>
                                        <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Section</th>
                                        <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Field</th>
                                        <th style="padding: 10px 12px; text-align: left; color: white; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activityLogs as $index => $log)
                                        @php $rowBg = $index % 2 === 0 ? '#ffffff' : '#f9fafb'; @endphp
                                        <tr style="background-color: {{ $rowBg }}; border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 10px 12px; color: #374151; font-size: 12px; white-space: nowrap;">{{ $log['timestamp']->format('M d, Y h:i A') }}</td>
                                            <td style="padding: 10px 12px; color: #374151; font-size: 12px; white-space: nowrap;">
                                                {{ $log['user_name'] ?? 'Unknown' }}@if(!empty($log['user_agency'])) <span style="color: #6b7280;">({{ $log['user_agency'] }})</span>@endif
                                            </td>
                                            <td style="padding: 10px 12px; color: #374151; font-size: 12px;">{{ $log['section'] }}</td>
                                            <td style="padding: 10px 12px; color: #374151; font-size: 12px;">{{ $log['field'] }}</td>
                                            <td style="padding: 10px 12px; color: #6b7280; font-size: 12px;">{{ $log['details'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div id="activityLogBackdrop" aria-hidden="true"></div>

    <style>
        .asterisk {
            color: #dc2626;
        }
        .form-label{
            font-size: 12px; color: #002C76; font-weight: 700; text-transform: uppercase;
        }
        .project-copy-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #002C76;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .project-copy-button:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }

        .project-copy-button.is-copied {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .project-copy-button i {
            font-size: 11px;
        }

        .lfp-header-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
            outline: none;
        }

        .lfp-header-action i {
            font-size: 12px;
        }

        .lfp-header-action--primary {
            background: linear-gradient(135deg, #002c76 0%, #003d9e 100%);
            border-color: #002c76;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(0, 44, 118, 0.2);
        }

        .lfp-header-action--secondary {
            background: #ffffff;
            border-color: #bfd2f3;
            color: #002c76;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
        }

        .lfp-header-action--primary:hover {
            background: linear-gradient(135deg, #00348d 0%, #0b4db8 100%);
            border-color: #00348d;
            transform: translateY(-1px);
            box-shadow: 0 12px 22px rgba(0, 44, 118, 0.28);
        }

        .lfp-header-action--secondary:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #0b4db8;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(59, 130, 246, 0.16);
        }

        .lfp-header-action:focus-visible {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.22), 0 10px 18px rgba(15, 23, 42, 0.12);
        }

        .lfp-header-action:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
        }
        .content-header {
            flex-wrap: wrap;
        }

        .content-header > div:last-child {
            flex-wrap: wrap;
        }

        .project-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .project-tab {
            border: 1px solid #c8d8f0;
            background-color: #ffffff;
            color: #002c76;
            border-radius: 999px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1.1;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .project-tab:hover {
            background-color: #eff6ff;
            border-color: #9bb7e3;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
        }

        .project-tab.is-active {
            background-color: #002c76;
            border-color: #002c76;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(0, 44, 118, 0.28);
        }

        .project-tab-panel {
            display: none;
        }

        .project-tab-panel.is-active {
            display: block;
        }

        .lfp-gallery-layout {
            display: grid;
            grid-template-columns: minmax(180px, 220px) minmax(0, 1fr);
            gap: 20px;
            align-items: start;
            width: 100%;
            min-width: 0;
        }

        .lfp-gallery-sidebar {
            padding: 14px;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
        }

        .lfp-gallery-sidebar-buttons {
            display: grid;
            gap: 10px;
        }

        .lfp-gallery-sidebar-button {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background-color: #ffffff;
            color: #1e293b;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .lfp-gallery-sidebar-button:hover {
            border-color: #002c76;
            color: #002c76;
            box-shadow: 0 8px 18px rgba(0, 44, 118, 0.12);
        }

        .lfp-gallery-sidebar-button.is-active {
            background-color: #002c76;
            border-color: #002c76;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(0, 44, 118, 0.22);
        }

        .lfp-gallery-stage {
            min-height: 320px;
            width: 100%;
            min-width: 0;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            background-color: #f8fafc;
            padding: 12px;
        }

        .lfp-gallery-panel {
            display: none;
            min-height: 294px;
            width: 100%;
            border-radius: 10px;
            background: #f8fafc;
        }

        .lfp-gallery-panel.is-active {
            display: block;
        }

        #projectProfileSection,
        #contractInfoSection,
        #physicalAccomplishmentSection,
        #financialAccomplishmentSection,
        #monitoringInspectionSection,
        #postImplementationSection,
        #gallerySection,
        #activityLogSection {
            font-size: 0.9em;
            color: #374151;
        }

        #activityLogBackdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            z-index: 1390;
        }

        #activityLogBackdrop.is-visible {
            opacity: 1;
            visibility: visible;
        }

        #activityLogSection {
            position: fixed;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%) scale(0.98);
            width: min(900px, 92vw);
            max-height: 80vh;
            overflow: hidden;
            border-radius: 12px;
            background: white;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
            display: none;
            margin: 0 !important;
            z-index: 1400;
            transition: transform 0.25s ease;
        }

        #activityLogSection.is-visible {
            display: block;
            transform: translate(-50%, -50%) scale(1);
        }

        #physicalAccomplishmentSection.is-inline-editing,
        #financialAccomplishmentSection.is-inline-editing,
        #monitoringInspectionSection.is-inline-editing,
        #postImplementationSection.is-inline-editing {
            position: fixed;
            left: 50%;
            top: 50%;
            width: min(1180px, calc(100vw - 32px));
            max-height: min(90vh, 960px);
            margin-bottom: 0 !important;
            overflow: auto;
            transform: translate(-50%, -50%);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.24);
            z-index: 1300;
        }

        #physicalAccomplishmentSection.is-inline-editing .lfp-inline-modal-section-close,
        #financialAccomplishmentSection.is-inline-editing .lfp-inline-modal-section-close,
        #monitoringInspectionSection.is-inline-editing .lfp-inline-modal-section-close,
        #postImplementationSection.is-inline-editing .lfp-inline-modal-section-close {
            display: inline-flex;
        }

        body.modal-open {
            overflow: hidden;
        }

        #activityLogFab[data-state="open"] {
            background: #dbeafe;
            border-color: #60a5fa;
            color: #1d4ed8;
        }

        #physicalAccomplishmentSection [style*="font-size"] {
            font-size: inherit !important;
        }

        .monthly-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .monthly-details[open] {
            border-color: #c7d2fe;
            background-color: #eef2ff;
            box-shadow: 0 6px 14px rgba(30, 64, 175, 0.12);
        }

        .monthly-details > summary {
            list-style: none;
        }

        .monthly-summary::marker {
            content: '';
        }

        .monthly-summary::after {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background-color: #c7d2fe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            transition: transform 0.2s ease;
        }

        details[open] > .monthly-summary::after {
            transform: rotate(45deg);
        }

        #financialAccomplishmentSection .monthly-details {
            width: 50%;
            min-width: 320px;
            box-sizing: border-box;
        }

        .lfp-inline-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px !important;
        }

        .lfp-inline-form-save,
        .lfp-inline-form-cancel {
            padding: 8px 16px !important;
            border: none !important;
            border-radius: 6px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
        }

        .lfp-inline-form-save {
            background-color: #16a34a !important;
            color: #ffffff !important;
        }

        .lfp-inline-form-cancel {
            display: none;
            background-color: #6b7280 !important;
            color: #ffffff !important;
        }

        .lfp-inline-section-footer {
            display: none;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .lfp-inline-modal-section.is-inline-editing .lfp-inline-section-footer {
            display: flex;
        }

        .lfp-inline-section-save,
        .lfp-inline-section-cancel {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .lfp-inline-section-save {
            background-color: #16a34a;
            color: #ffffff;
        }

        .lfp-inline-section-save:disabled,
        .lfp-inline-modal button[type="submit"]:disabled,
        .lfp-inline-modal input[type="submit"]:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .lfp-inline-section-cancel {
            background-color: #6b7280;
            color: #ffffff;
        }

        button[data-financial-save="true"],
        button[data-monitoring-save="true"],
        button[data-post-implementation-save="true"] {
            display: none !important;
        }

        @media (max-width: 1024px) {
            div[style*="grid-template-columns: repeat(3"] {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            .content-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .content-header > div:last-child {
                width: 100%;
                justify-content: flex-start;
            }

            .project-tabs {
                gap: 6px;
            }

            .project-tab {
                border-radius: 999px;
                padding: 8px 12px;
                font-size: 11px;
            }

            #financialAccomplishmentSection .monthly-details {
                width: 100%;
                min-width: 0;
            }

            #financialAccomplishmentSection.is-inline-editing {
                top: max(12px, env(safe-area-inset-top));
                right: 12px;
                bottom: max(12px, env(safe-area-inset-bottom));
                left: 12px;
                width: auto;
                max-height: none;
                transform: none;
            }

            table {
                min-width: 720px;
            }
        }

        @media (max-width: 480px) {
            .content-header h1 {
                font-size: 20px;
            }

            .content-header p {
                font-size: 12px;
            }

            #activityLogSection {
                width: 94vw;
                max-height: 85vh;
            }
        }

        @media (max-width: 1024px) {
            .lfp-mobile-shell {
                width: 100%;
                overflow-x: hidden;
                overflow-y: visible;
                padding-bottom: 0;
            }

            .lfp-mobile-canvas {
                min-width: 100%;
                font-size: 11px;
            }

            .lfp-mobile-canvas .content-header {
                flex-direction: row;
                align-items: center;
                flex-wrap: wrap;
            }

            .lfp-mobile-canvas .content-header h1 {
                font-size: 22px;
            }

            .lfp-mobile-canvas .project-main-title {
                font-size: 20px !important;
            }

            .lfp-mobile-canvas .content-header > div:last-child {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .lfp-mobile-canvas .project-tabs {
                flex-wrap: nowrap;
                gap: 6px;
                overflow-x: auto;
                overflow-y: hidden;
                width: 100%;
                padding-bottom: 4px;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                scroll-snap-type: x proximity;
            }

            .lfp-mobile-canvas .project-tabs::-webkit-scrollbar {
                display: none;
            }

            .lfp-mobile-canvas .project-tab {
                flex: 0 0 auto;
                padding: 6px 10px;
                font-size: 10px;
                white-space: nowrap;
                text-align: center;
                scroll-snap-align: start;
            }

            .lfp-mobile-canvas div[style*="grid-template-columns: repeat(3"] {
                grid-template-columns: repeat(3, minmax(110px, 1fr)) !important;
                gap: 10px !important;
            }

            .lfp-mobile-canvas div[style*="grid-template-columns: repeat(2, minmax(260px, 1fr))"] {
                grid-template-columns: repeat(2, minmax(150px, 1fr)) !important;
                gap: 10px !important;
            }

            .lfp-mobile-canvas div[style*="grid-template-columns: repeat(2, minmax(300px, 1fr))"] {
                grid-template-columns: repeat(2, minmax(150px, 1fr)) !important;
                gap: 10px !important;
            }

            .lfp-mobile-canvas div[style*="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"] {
                grid-template-columns: repeat(2, minmax(150px, 1fr)) !important;
                gap: 12px !important;
            }

            .lfp-mobile-canvas div[style*="grid-template-columns: 120px 1fr 180px 140px"] {
                grid-template-columns: 46px minmax(72px, 1fr) 62px 54px !important;
                gap: 4px !important;
                font-size: 10px !important;
            }

            .lfp-mobile-canvas #editProfileForm > div:first-of-type,
            .lfp-mobile-canvas #editContractForm > div:first-of-type {
                grid-template-columns: repeat(3, minmax(110px, 1fr)) !important;
                gap: 10px !important;
            }

            .lfp-mobile-canvas #financialAccomplishmentSection .monthly-details {
                width: 100%;
                min-width: 0;
            }

            .lfp-mobile-canvas #projectProfileSection,
            .lfp-mobile-canvas #contractInfoSection,
            .lfp-mobile-canvas #physicalAccomplishmentSection,
            .lfp-mobile-canvas #financialAccomplishmentSection,
            .lfp-mobile-canvas #monitoringInspectionSection,
            .lfp-mobile-canvas #postImplementationSection,
            .lfp-mobile-canvas #gallerySection {
                font-size: 0.78em;
            }

            .lfp-mobile-canvas > div[style*="background: #f8fafc; padding: 24px"] {
                padding: 14px !important;
            }

            .lfp-mobile-canvas table {
                min-width: 100% !important;
                font-size: 10px !important;
            }

            .lfp-mobile-canvas th,
            .lfp-mobile-canvas td {
                white-space: normal;
                word-break: break-word;
            }
        }

        @media (max-width: 768px) {
            .lfp-mobile-canvas #projectProfileSection {
                padding: 16px !important;
                font-size: 0.95em;
            }

            .lfp-mobile-canvas .project-tab-panel > div:first-child {
                flex-direction: row !important;
                align-items: flex-start !important;
            }

            .lfp-mobile-canvas .project-tab-panel > div:first-child a[data-toggle="inline-edit"] {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px !important;
                min-width: 36px;
                height: 36px !important;
                padding: 0 !important;
                border-radius: 999px !important;
                font-size: 0 !important;
                line-height: 1;
                flex: 0 0 auto;
                overflow: hidden;
            }

            .lfp-mobile-canvas .project-tab-panel > div:first-child a[data-toggle="inline-edit"] i {
                margin-right: 0 !important;
                font-size: 14px !important;
            }

            .lfp-mobile-canvas #physicalAccomplishmentSection a[data-toggle="inline-edit"][data-target="editPhysicalForm"] {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px !important;
                min-width: 36px;
                height: 36px !important;
                padding: 0 !important;
                border-radius: 999px !important;
                font-size: 0 !important;
                line-height: 1;
                flex: 0 0 auto;
                overflow: hidden;
            }

            .lfp-mobile-canvas #physicalAccomplishmentSection a[data-toggle="inline-edit"][data-target="editPhysicalForm"] i {
                margin-right: 0 !important;
                font-size: 14px !important;
            }

            .lfp-mobile-canvas .lfp-physical-section-title {
                font-size: clamp(12px, 5vw, 16px) !important;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }

            .lfp-mobile-canvas #financialAccomplishmentSection > div:first-child {
                justify-content: flex-start !important;
            }

            .lfp-mobile-canvas #financialAccomplishmentSection > div:first-child > h3 {
                flex: 1 1 auto;
                min-width: 0;
            }

            .lfp-mobile-canvas #postImplementationSection > div:first-child {
                justify-content: space-between !important;
            }

            .lfp-mobile-canvas #postImplementationSection > div:first-child > h3 {
                flex: 0 1 auto;
            }

            .lfp-mobile-canvas #postImplementationSection > div:first-child > div {
                margin-left: auto;
            }

            .lfp-mobile-canvas #gallerySection .lfp-gallery-layout {
                grid-template-columns: 1fr;
            }

            .lfp-mobile-canvas #gallerySection .lfp-gallery-stage {
                min-height: 180px;
            }

            .lfp-mobile-canvas #gallerySection .lfp-gallery-panel {
                min-height: 154px;
            }

            .lfp-mobile-canvas .lfp-financial-section-title {
                font-size: 11px !important;
                line-height: 1.15;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .lfp-mobile-canvas .lfp-financial-section-actions {
                margin-left: auto;
                flex: 0 0 auto;
                justify-content: flex-end;
            }

            .lfp-mobile-canvas #physicalAccomplishmentSection > div[style*="grid-template-columns: repeat(2, minmax(300px, 1fr))"] {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            .lfp-mobile-canvas #physicalAccomplishmentSection > div[style*="grid-template-columns: repeat(2, minmax(300px, 1fr))"] > div {
                width: 100%;
                min-width: 0;
            }

            #editPhysicalFormWrapper > .lfp-inline-modal-body > div[style*="grid-template-columns: repeat(2, minmax(300px, 1fr))"] {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            #editPhysicalFormWrapper > .lfp-inline-modal-body > div[style*="grid-template-columns: repeat(2, minmax(300px, 1fr))"] > div,
            #editPhysicalFormWrapper > .lfp-inline-modal-body > div[style*="grid-template-columns: repeat(2, minmax(300px, 1fr))"] > form {
                width: 100%;
                min-width: 0;
            }

            #editPhysicalFormWrapper > .lfp-inline-modal-body > .lfp-physical-modal-grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }

            .lfp-mobile-canvas #monitoringInspectionSection > div[style*="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"] {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            .lfp-mobile-canvas #monitoringInspectionSection > div[style*="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"] > div {
                width: 100%;
                min-width: 0;
            }

            .lfp-mobile-canvas .project-profile-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 14px !important;
            }

            .lfp-mobile-canvas .project-profile-form-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            #editProfileFormWrapper .project-profile-form-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            .lfp-mobile-canvas .project-profile-grid > div,
            .lfp-mobile-canvas .project-profile-form-grid > div {
                min-width: 0;
                width: 100%;
            }

            #editProfileFormWrapper .project-profile-form-grid > div {
                min-width: 0;
                width: 100%;
            }

            .lfp-mobile-canvas #editProfileForm > div:last-child {
                flex-direction: column;
            }

            .lfp-mobile-canvas #editProfileForm > div:last-child button {
                width: 100%;
            }

            #editProfileForm > div:last-child {
                flex-direction: column;
            }

            #editProfileForm > div:last-child button {
                width: 100%;
            }

            .lfp-mobile-canvas #contractInfoSection {
                padding: 16px !important;
                font-size: 0.95em;
            }

            .lfp-mobile-canvas #contractInfoSection .contract-info-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            #editContractFormWrapper .contract-form-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 12px !important;
            }

            .lfp-mobile-canvas .contract-info-grid > div,
            .lfp-mobile-canvas .contract-form-grid > div,
            #editContractFormWrapper .contract-form-grid > div {
                min-width: 0;
            }

            .lfp-mobile-canvas .contract-info-grid > div {
                display: flex;
                flex-direction: column;
                gap: 4px;
                box-sizing: border-box;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .lfp-mobile-canvas .contract-info-grid > div strong {
                display: block;
            }

        }

    </style>

    <script>
        function formatMoney(value) {
            return (Math.round((value + Number.EPSILON) * 100) / 100).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        const financialAllocation = Number(@json((float) $project->lgsf_allocation)) || 0;

        function getFinancialFieldSum(field) {
            let sum = 0;
            document.querySelectorAll('[data-financial-field="' + field + '"]').forEach((input) => {
                const raw = input.value ? input.value.toString() : '';
                const cleaned = raw.replace(/,/g, '');
                const num = parseFloat(cleaned);
                if (!isNaN(num)) {
                    sum += num;
                }
            });
            return sum;
        }

        function updateFinancialSums() {
            const fields = ['obligation', 'disbursed_amount', 'reverted_amount'];
            fields.forEach((field) => {
                const el = document.getElementById('financialSum-' + field);
                if (el) {
                    el.textContent = formatMoney(getFinancialFieldSum(field));
                }
            });

            const disbursedTotal = getFinancialFieldSum('disbursed_amount');
            const revertedTotal = getFinancialFieldSum('reverted_amount');
            const balance = financialAllocation - (disbursedTotal + revertedTotal);
            const utilizationRate = financialAllocation > 0
                ? ((financialAllocation - balance) / financialAllocation) * 100
                : 0;

            const balanceEl = document.getElementById('financialBalance');
            if (balanceEl) {
                balanceEl.textContent = formatMoney(balance);
            }

            const utilizationEl = document.getElementById('financialUtilizationRate');
            if (utilizationEl) {
                utilizationEl.textContent = formatMoney(utilizationRate) + '%';
                utilizationEl.style.color = utilizationRate < 100 ? '#dc2626' : '#111827';
            }
        }

        document.addEventListener('input', (event) => {
            if (event.target && event.target.hasAttribute('data-financial-field')) {
                updateFinancialSums();
            }
        });

        updateFinancialSums();

        const inlineSectionTargetMap = {
            editMonitoringForm: 'monitoringInspectionSection',
            editPostImplementationForm: 'postImplementationSection',
        };
        const inlinePortalRegistry = new Map();

        function getInlineEditElements(targetId) {
            const mappedTargetId = inlineSectionTargetMap[targetId] || targetId;
            return {
                target: document.getElementById(mappedTargetId),
                wrapper: document.getElementById(targetId + 'Wrapper'),
                backdrop: document.getElementById(targetId + 'Backdrop'),
            };
        }

        function isInlineSectionTarget(targetId, inlineElements) {
            const elements = inlineElements || getInlineEditElements(targetId);

            return !elements.wrapper
                && Object.prototype.hasOwnProperty.call(inlineSectionTargetMap, targetId)
                && !!elements.target;
        }

        function isInlineEditOpen(targetId) {
            const inlineElements = getInlineEditElements(targetId);

            if (inlineElements.wrapper) {
                return inlineElements.wrapper.style.display !== 'none'
                    && inlineElements.wrapper.getAttribute('aria-hidden') !== 'true';
            }

            if (isInlineSectionTarget(targetId, inlineElements)) {
                return inlineElements.target.classList.contains('is-inline-editing');
            }

            return false;
        }

        function syncInlineSectionHeading(targetId, isEditing) {
            const mappedTargetId = inlineSectionTargetMap[targetId];
            if (!mappedTargetId) {
                return;
            }

            const section = document.getElementById(mappedTargetId);
            const heading = section ? section.querySelector('[data-inline-section-heading="true"]') : null;
            if (!heading) {
                return;
            }

            heading.textContent = isEditing
                ? (heading.dataset.editTitle || heading.textContent)
                : (heading.dataset.viewTitle || heading.textContent);
        }

        function syncBodyModalState() {
            const activityLogModal = document.getElementById('activityLogSection');
            const hasActivityLogModal = activityLogModal ? activityLogModal.classList.contains('is-visible') : false;
            const physicalCompareModal = document.getElementById('physicalCompareModalWrapper');
            const hasPhysicalCompareModal = physicalCompareModal
                ? physicalCompareModal.classList.contains('is-visible') && physicalCompareModal.getAttribute('aria-hidden') !== 'true'
                : false;
            const hasInlineModal = Array.from(document.querySelectorAll('.lfp-inline-modal[data-inline-modal="true"]')).some((modal) => {
                return modal.style.display !== 'none' && modal.getAttribute('aria-hidden') !== 'true';
            });
            const hasInlineSectionModal = Array.from(document.querySelectorAll('.lfp-inline-modal-section[data-inline-modal-section="true"]')).some((section) => {
                return section.classList.contains('is-inline-editing');
            });

            document.body.classList.toggle('modal-open', hasActivityLogModal || hasPhysicalCompareModal || hasInlineModal || hasInlineSectionModal);
        }

        function registerInlinePortal(targetId) {
            const inlineElements = getInlineEditElements(targetId);
            const nodeToPortal = inlineElements.wrapper || inlineElements.target;
            const backdrop = inlineElements.backdrop;

            if (!nodeToPortal || !backdrop || !nodeToPortal.parentNode || !backdrop.parentNode) {
                return;
            }

            const originalParent = nodeToPortal.parentNode;
            const anchor = document.createElement('span');
            anchor.hidden = true;
            originalParent.insertBefore(anchor, backdrop);

            const syncPortal = () => {
                const isOpen = isInlineEditOpen(targetId);

                if (isOpen) {
                    if (backdrop.parentNode !== document.body) {
                        document.body.appendChild(backdrop);
                    }

                    if (nodeToPortal.parentNode !== document.body) {
                        document.body.appendChild(nodeToPortal);
                    }
                } else {
                    if (backdrop.parentNode !== originalParent) {
                        originalParent.insertBefore(backdrop, anchor.nextSibling);
                    }

                    if (nodeToPortal.parentNode !== originalParent) {
                        const referenceNode = backdrop.parentNode === originalParent
                            ? backdrop.nextSibling
                            : anchor.nextSibling;
                        originalParent.insertBefore(nodeToPortal, referenceNode);
                    }
                }

                syncBodyModalState();
            };

            inlinePortalRegistry.set(targetId, syncPortal);
            syncPortal();
        }

        function syncInlinePortalState(targetId) {
            const syncPortal = inlinePortalRegistry.get(targetId);
            if (typeof syncPortal === 'function') {
                syncPortal();
            }
        }

        function registerActivityLogPortal() {
            const section = document.getElementById('activityLogSection');
            const backdrop = document.getElementById('activityLogBackdrop');

            if (!section || !backdrop || !section.parentNode || !backdrop.parentNode) {
                return;
            }

            const originalParent = section.parentNode;
            const anchor = document.createElement('span');
            anchor.hidden = true;
            originalParent.insertBefore(anchor, section);

            const syncPortal = () => {
                const isVisible = section.classList.contains('is-visible');

                if (isVisible) {
                    if (backdrop.parentNode !== document.body) {
                        document.body.appendChild(backdrop);
                    }

                    if (section.parentNode !== document.body) {
                        document.body.appendChild(section);
                    }
                } else {
                    if (section.parentNode !== originalParent) {
                        originalParent.insertBefore(section, anchor.nextSibling);
                    }

                    if (backdrop.parentNode !== originalParent) {
                        originalParent.insertBefore(backdrop, section.nextSibling);
                    }
                }
            };

            syncPortal();

            return syncPortal;
        }

        function getInlineToggleMarkup(label, iconClass) {
            return '<i class="' + iconClass + '" style="margin-right: 6px;" aria-hidden="true"></i>' + label;
        }

        function setInlineToggleState(button, isEditing) {
            if (!button) return;
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent.trim();
            }
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            if (!button.dataset.originalBg) {
                button.dataset.originalBg = button.style.backgroundColor;
            }
            if (!button.dataset.originalColor) {
                button.dataset.originalColor = button.style.color;
            }
            button.innerHTML = isEditing
                ? getInlineToggleMarkup('Cancel', 'fas fa-times')
                : (button.dataset.originalHtml || getInlineToggleMarkup(button.dataset.originalText || 'Update', 'fas fa-edit'));
            button.dataset.inlineState = isEditing ? 'editing' : 'idle';
            const defaultBackground = button.classList.contains('lfp-inline-edit-trigger') ? '#002C76' : (button.dataset.originalBg || '');
            const defaultColor = button.classList.contains('lfp-inline-edit-trigger') ? '#ffffff' : (button.dataset.originalColor || '#ffffff');
            button.style.backgroundColor = isEditing ? '#dc2626' : defaultBackground;
            button.style.color = isEditing ? '#ffffff' : defaultColor;
            button.setAttribute('aria-label', isEditing ? 'Cancel editing' : (button.dataset.originalText || 'Update'));

            const targetId = button.getAttribute('data-target');
            const isSectionTarget = targetId && ['editMonitoringForm', 'editPostImplementationForm'].includes(targetId);
            if (isSectionTarget) {
                button.style.display = isEditing ? 'none' : 'inline-flex';
            }
        }

        function openInlineEdit(button) {
            const targetId = button.getAttribute('data-target');
            const inlineElements = getInlineEditElements(targetId);
            const { target, wrapper, backdrop } = inlineElements;
            if (wrapper) {
                wrapper.style.display = 'block';
                wrapper.classList.add('is-visible');
                wrapper.setAttribute('aria-hidden', 'false');

                const focusTarget = wrapper.querySelector('button, input, select, textarea');
                if (focusTarget) {
                    setTimeout(() => focusTarget.focus(), 0);
                }
            }

            if (isInlineSectionTarget(targetId, inlineElements)) {
                target.classList.add('is-inline-editing');
                syncInlineSectionHeading(targetId, true);
            }

            if (backdrop && (wrapper || isInlineSectionTarget(targetId, inlineElements))) {
                backdrop.classList.add('is-visible');
                backdrop.setAttribute('aria-hidden', 'false');
            }

            snapshotInlineEditFields(targetId);
            syncInlineEditSaveState(targetId);
            syncInlinePortalState(targetId);
            syncBodyModalState();

            if (button.hasAttribute('data-physical-toggle')) {
                const currentMonth = {{ $currentMonth }};
                const userAgency = '{{ Auth::user()->agency }}';
                const userProvince = '{{ Auth::user()->province }}';
                const isROUser = userAgency === 'DILG' && userProvince === 'Regional Office';
                
                document.querySelectorAll('[data-physical-edit="true"]').forEach((input) => {
                    const inputMonth = parseInt(input.getAttribute('data-month'), 10);
                    const isROOnly = input.hasAttribute('data-ro-only');
                    
                    if (inputMonth === currentMonth) {
                        // For RO-only fields, only enable if user is RO user
                        if (isROOnly && !isROUser) {
                            input.disabled = true;
                            input.style.backgroundColor = '#f3f4f6';
                        } else {
                            input.disabled = false;
                            input.style.backgroundColor = '#ffffff';
                        }
                    } else {
                        input.disabled = true;
                        input.style.backgroundColor = '#f3f4f6';
                    }
                });
            }

            if (button.hasAttribute('data-financial-toggle')) {
                const currentMonth = {{ $currentMonth }};
                const userAgency = '{{ Auth::user()->agency }}';
                const userProvince = '{{ Auth::user()->province }}';
                const isROUser = userAgency === 'DILG' && userProvince === 'Regional Office';
                
                document.querySelectorAll('[data-financial-edit="true"]').forEach((input) => {
                    const inputMonth = parseInt(input.getAttribute('data-month'), 10);
                    const isROOnly = input.hasAttribute('data-ro-only');
                    
                    if (inputMonth === currentMonth) {
                        // For RO-only fields, only enable if user is RO user
                        if (isROOnly && !isROUser) {
                            input.disabled = true;
                            input.style.backgroundColor = '#f3f4f6';
                        } else {
                            input.disabled = false;
                            input.style.backgroundColor = '#ffffff';
                        }
                    } else {
                        input.disabled = true;
                        input.style.backgroundColor = '#f3f4f6';
                    }
                });
                // Only show save button if user is RO user
                document.querySelectorAll('[data-financial-save="true"]').forEach((saveBtn) => {
                    if (isROUser) {
                        saveBtn.style.display = 'inline-block';
                    } else {
                        saveBtn.style.display = 'none';
                    }
                });
                document.querySelectorAll('[data-inline-form-cancel="true"][data-target="editFinancialForm"]').forEach((cancelBtn) => {
                    cancelBtn.style.display = isROUser ? 'inline-block' : 'none';
                });
            }

            if (button.hasAttribute('data-monitoring-toggle')) {
                const userAgency = '{{ Auth::user()->agency }}';
                const userProvince = '{{ Auth::user()->province }}';
                const isROUser = userAgency === 'DILG' && userProvince === 'Regional Office';
                
                document.querySelectorAll('[data-monitoring-edit="true"]').forEach((input) => {
                    const isROOnly = input.hasAttribute('data-ro-only');
                    
                    // For RO-only fields, only enable if user is RO user
                    if (isROOnly && !isROUser) {
                        input.disabled = true;
                        input.style.backgroundColor = '#f3f4f6';
                    } else {
                        input.disabled = false;
                        input.style.backgroundColor = '#ffffff';
                    }
                });
                // Show save button only for fields that are not disabled
                document.querySelectorAll('[data-monitoring-save="true"]').forEach((saveBtn) => {
                    // Find the corresponding input field
                    const form = saveBtn.closest('form');
                    const input = form ? form.querySelector('[data-monitoring-edit="true"]') : null;
                    
                    if (input && !input.disabled) {
                        saveBtn.style.display = 'inline-block';
                    } else {
                        saveBtn.style.display = 'none';
                    }
                });
                document.querySelectorAll('[data-inline-form-cancel="true"][data-target="editMonitoringForm"]').forEach((cancelBtn) => {
                    const form = cancelBtn.closest('form');
                    const input = form ? form.querySelector('[data-monitoring-edit="true"]') : null;

                    cancelBtn.style.display = input && !input.disabled ? 'inline-block' : 'none';
                });
            }

            if (button.hasAttribute('data-post-implementation-toggle')) {
                const userAgency = '{{ Auth::user()->agency }}';
                const userProvince = '{{ Auth::user()->province }}';
                const isROUser = userAgency === 'DILG' && userProvince === 'Regional Office';
                
                document.querySelectorAll('[data-post-implementation-edit="true"]').forEach((input) => {
                    const isROOnly = input.hasAttribute('data-ro-only');
                    
                    // For RO-only fields, only enable if user is RO user
                    if (isROOnly && !isROUser) {
                        input.disabled = true;
                        input.style.backgroundColor = '#f3f4f6';
                    } else {
                        input.disabled = false;
                        input.style.backgroundColor = '#ffffff';
                    }
                });
                // Show save button only for fields that are not disabled
                document.querySelectorAll('[data-post-implementation-save="true"]').forEach((saveBtn) => {
                    // Find the corresponding input field (previous sibling)
                    const form = saveBtn.closest('form');
                    const input = form ? form.querySelector('[data-post-implementation-edit="true"]') : null;
                    
                    if (input && !input.disabled) {
                        saveBtn.style.display = 'inline-block';
                    } else {
                        saveBtn.style.display = 'none';
                    }
                });
                document.querySelectorAll('[data-inline-form-cancel="true"][data-target="editPostImplementationForm"]').forEach((cancelBtn) => {
                    const form = cancelBtn.closest('form');
                    const input = form ? form.querySelector('[data-post-implementation-edit="true"]') : null;

                    cancelBtn.style.display = input && !input.disabled ? 'inline-block' : 'none';
                });
            }
        }

        [
            'editProfileForm',
            'editContractForm',
            'editPhysicalForm',
            'editFinancialForm',
            'editMonitoringForm',
            'editPostImplementationForm',
        ].forEach((targetId) => {
            registerInlinePortal(targetId);
            syncInlineSectionHeading(targetId, isInlineEditOpen(targetId));
        });

        function closeInlineEdit(targetId) {
            const inlineElements = getInlineEditElements(targetId);
            const { target, wrapper, backdrop } = inlineElements;

            restoreInlineEditFields(targetId);

            if (wrapper) {
                wrapper.style.display = 'none';
                wrapper.classList.remove('is-visible');
                wrapper.setAttribute('aria-hidden', 'true');
            }

            if (isInlineSectionTarget(targetId, inlineElements)) {
                target.classList.remove('is-inline-editing');
                syncInlineSectionHeading(targetId, false);
            }

            if (backdrop) {
                backdrop.classList.remove('is-visible');
                backdrop.setAttribute('aria-hidden', 'true');
            }

            if (targetId === 'editPhysicalForm') {
                document.querySelectorAll('[data-physical-edit="true"]').forEach((input) => {
                    input.disabled = true;
                    input.style.backgroundColor = '#f3f4f6';
                });
            }

            if (targetId === 'editFinancialForm') {
                document.querySelectorAll('[data-financial-edit="true"]').forEach((input) => {
                    input.disabled = true;
                    input.style.backgroundColor = '#f3f4f6';
                });
                document.querySelectorAll('[data-financial-save="true"]').forEach((saveBtn) => {
                    saveBtn.style.display = 'none';
                });
                document.querySelectorAll('[data-inline-form-cancel="true"][data-target="editFinancialForm"]').forEach((cancelBtn) => {
                    cancelBtn.style.display = 'none';
                });
            }

            if (targetId === 'editMonitoringForm') {
                document.querySelectorAll('[data-monitoring-edit="true"]').forEach((input) => {
                    input.disabled = true;
                    input.style.backgroundColor = '#f3f4f6';
                });
                document.querySelectorAll('[data-monitoring-save="true"]').forEach((saveBtn) => {
                    saveBtn.style.display = 'none';
                });
                document.querySelectorAll('[data-inline-form-cancel="true"][data-target="editMonitoringForm"]').forEach((cancelBtn) => {
                    cancelBtn.style.display = 'none';
                });
            }

            if (targetId === 'editPostImplementationForm') {
                document.querySelectorAll('[data-post-implementation-edit="true"]').forEach((input) => {
                    input.disabled = true;
                    input.style.backgroundColor = '#f3f4f6';
                });
                document.querySelectorAll('[data-post-implementation-save="true"]').forEach((saveBtn) => {
                    saveBtn.style.display = 'none';
                });
                document.querySelectorAll('[data-inline-form-cancel="true"][data-target="editPostImplementationForm"]').forEach((cancelBtn) => {
                    cancelBtn.style.display = 'none';
                });
            }

            syncInlineEditSaveState(targetId);
            syncInlinePortalState(targetId);
            syncBodyModalState();
        }

        function disableAllEditableControlsOnLoad() {
            const editableSelectors = [
                '[data-physical-edit="true"]',
                '[data-financial-edit="true"]',
                '[data-monitoring-edit="true"]',
                '[data-post-implementation-edit="true"]',
            ];

            document.querySelectorAll(editableSelectors.join(',')).forEach((input) => {
                input.disabled = true;
                input.style.backgroundColor = '#f3f4f6';
            });

            const saveSelectors = [
                '[data-financial-save="true"]',
                '[data-monitoring-save="true"]',
                '[data-post-implementation-save="true"]',
            ];

            document.querySelectorAll(saveSelectors.join(',')).forEach((saveBtn) => {
                saveBtn.style.display = 'none';
            });

            document.querySelectorAll('[data-inline-form-cancel="true"]').forEach((cancelBtn) => {
                cancelBtn.style.display = 'none';
            });
        }

        disableAllEditableControlsOnLoad();

        const inlineEditConfigs = {
            editProfileForm: {
                selector: 'input, select, textarea',
                scope: 'form',
                submitMode: 'native',
            },
            editContractForm: {
                selector: 'input, select, textarea',
                scope: 'form',
                submitMode: 'native',
            },
            editPhysicalForm: {
                selector: '[data-physical-edit="true"]',
                scope: 'section',
                submitMode: 'ajax',
                fallbackSection: 'physical',
            },
            editFinancialForm: {
                selector: '[data-financial-edit="true"]',
                scope: 'section',
                submitMode: 'ajax',
                fallbackSection: 'financial',
            },
            editMonitoringForm: {
                selector: '[data-monitoring-edit="true"]',
                scope: 'section',
                submitMode: 'ajax',
                fallbackSection: 'monitoring',
            },
            editPostImplementationForm: {
                selector: '[data-post-implementation-edit="true"]',
                scope: 'section',
                submitMode: 'ajax',
                fallbackSection: 'monitoring',
            },
        };

        function getInlineEditConfig(targetId) {
            return inlineEditConfigs[targetId] || null;
        }

        function getInlineSectionElement(targetId) {
            return document.querySelector('.lfp-inline-modal-section[data-inline-target="' + targetId + '"]')
                || document.getElementById(targetId + 'Wrapper');
        }

        function getInlineEditScopeElement(targetId) {
            const config = getInlineEditConfig(targetId);
            if (!config) {
                return null;
            }

            if (config.scope === 'section') {
                return getInlineSectionElement(targetId);
            }

            return document.getElementById(targetId);
        }

        function isTrackedInlineEditField(field) {
            if (!field || !field.name) {
                return false;
            }

            if (field.matches('button, input[type="submit"], input[type="button"], input[type="reset"], input[type="image"]')) {
                return false;
            }

            if (field.type === 'hidden' && ['_token', '_method', 'section'].includes(field.name)) {
                return false;
            }

            return true;
        }

        function getTrackedInlineEditValue(field) {
            if (!field) {
                return '';
            }

            if (field.type === 'file') {
                return Array.from(field.files || []).map((file) => {
                    return [file.name, file.size, file.lastModified].join(':');
                }).join('|');
            }

            if (field.tagName === 'SELECT' && field.multiple) {
                return Array.from(field.selectedOptions || []).map((option) => option.value).join('|');
            }

            return getEditableFieldValue(field);
        }

        function getInlineEditFields(targetId) {
            const config = getInlineEditConfig(targetId);
            const scopeElement = getInlineEditScopeElement(targetId);
            if (!config || !scopeElement) {
                return [];
            }

            return Array.from(scopeElement.querySelectorAll(config.selector)).filter(isTrackedInlineEditField);
        }

        function snapshotInlineEditFields(targetId) {
            const scopeElement = getInlineEditScopeElement(targetId);
            if (scopeElement) {
                delete scopeElement.dataset.forceInlineDirty;
            }

            getInlineEditFields(targetId).forEach((field) => {
                field.dataset.inlineOriginalValue = getTrackedInlineEditValue(field);
            });
        }

        function restoreInlineEditFields(targetId) {
            getInlineEditFields(targetId).forEach((field) => {
                if (!Object.prototype.hasOwnProperty.call(field.dataset, 'inlineOriginalValue')) {
                    return;
                }

                if (field.type === 'file') {
                    field.value = '';
                    return;
                }

                setEditableFieldValue(field, field.dataset.inlineOriginalValue);
            });

            if (targetId === 'editProfileForm' && typeof window.syncProjectBarangayPicker === 'function') {
                window.syncProjectBarangayPicker();
            }
        }

        function hasInlineEditChanges(targetId) {
            const scopeElement = getInlineEditScopeElement(targetId);
            if (scopeElement && scopeElement.dataset.forceInlineDirty === 'true') {
                return true;
            }

            return getInlineEditFields(targetId).some((field) => {
                const originalValue = Object.prototype.hasOwnProperty.call(field.dataset, 'inlineOriginalValue')
                    ? field.dataset.inlineOriginalValue
                    : getTrackedInlineEditValue(field);

                return getTrackedInlineEditValue(field) !== originalValue;
            });
        }

        function getInlineEditSaveButtons(targetId) {
            const config = getInlineEditConfig(targetId);
            const scopeElement = getInlineEditScopeElement(targetId);
            if (!config || !scopeElement) {
                return [];
            }

            if (config.submitMode === 'ajax') {
                return Array.from(scopeElement.querySelectorAll('[data-inline-section-save="' + targetId + '"]'));
            }

            return Array.from(scopeElement.querySelectorAll('button[type="submit"], input[type="submit"]'));
        }

        function syncInlineEditSaveState(targetId) {
            const hasChanges = hasInlineEditChanges(targetId);
            getInlineEditSaveButtons(targetId).forEach((button) => {
                button.disabled = !hasChanges;
                button.setAttribute('aria-disabled', hasChanges ? 'false' : 'true');
            });
        }

        function finalizeInlineEditClose(targetId) {
            closeInlineEdit(targetId);
            const editButton = document.querySelector('[data-toggle="inline-edit"][data-target="' + targetId + '"]');
            setInlineToggleState(editButton, false);
        }

        function requestInlineEditClose(targetId) {
            if (!hasInlineEditChanges(targetId)) {
                finalizeInlineEditClose(targetId);
                return;
            }

            openReusableConfirmation(
                'You have unsaved changes. Discard them?',
                () => {
                    finalizeInlineEditClose(targetId);
                }
            );
        }

        function requestInlineSectionSave(targetId) {
            if (!hasInlineEditChanges(targetId)) {
                syncInlineEditSaveState(targetId);
                return;
            }

            openReusableConfirmation(
                'Save the changes in this section?',
                () => {
                    submitInlineSection(targetId);
                }
            );
        }

        function initializeInlineSectionFooters() {
            document.querySelectorAll('.lfp-inline-modal-section[data-inline-target]').forEach((section) => {
                if (section.querySelector('.lfp-inline-section-footer')) {
                    return;
                }

                const targetId = section.getAttribute('data-inline-target');
                const footer = document.createElement('div');
                footer.className = 'lfp-inline-section-footer';
                footer.innerHTML = '' +
                    '<button type="button" class="lfp-inline-section-save" data-inline-section-save="' + targetId + '" data-confirm-skip="true">' +
                        '<i class="fas fa-check" style="margin-right: 8px;" aria-hidden="true"></i>Save Changes' +
                    '</button>' +
                    '<button type="button" class="lfp-inline-section-cancel" data-inline-section-cancel="' + targetId + '">' +
                        '<i class="fas fa-times" style="margin-right: 8px;" aria-hidden="true"></i>Cancel' +
                    '</button>';

                section.appendChild(footer);
            });
        }

        async function submitInlineSection(targetId) {
            const section = getInlineEditScopeElement(targetId);
            const config = getInlineEditConfig(targetId);
            if (!section || !config) {
                return;
            }

            const fields = getInlineEditFields(targetId).filter((field) => {
                if (field.disabled || !field.name) {
                    return false;
                }

                const originalValue = Object.prototype.hasOwnProperty.call(field.dataset, 'inlineOriginalValue')
                    ? field.dataset.inlineOriginalValue
                    : getTrackedInlineEditValue(field);

                return getTrackedInlineEditValue(field) !== originalValue;
            });
            const referenceForm = section.querySelector('form[action]');
            if (!referenceForm) {
                return;
            }

            if (!hasInlineEditChanges(targetId) || fields.length === 0) {
                syncInlineEditSaveState(targetId);
                return;
            }

            const actionUrl = referenceForm.getAttribute('action');
            const sectionField = referenceForm.querySelector('input[name="section"]');
            const sectionValue = sectionField ? sectionField.value : config.fallbackSection;
            const formData = new FormData();

            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            formData.append('section', sectionValue);

            fields.forEach((field) => {
                if (field.type === 'file') {
                    Array.from(field.files || []).forEach((file) => {
                        formData.append(field.name, file);
                    });
                    return;
                }

                if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) {
                    return;
                }

                formData.append(field.name, field.value);
            });

            const saveButton = section.querySelector('[data-inline-section-save="' + targetId + '"]');
            if (saveButton) {
                saveButton.disabled = true;
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html,application/xhtml+xml',
                    },
                });

                const html = await response.text();
                if (!response.ok) {
                    throw new Error('Request failed');
                }

                document.open();
                document.write(html);
                document.close();
            } catch (error) {
                if (saveButton) {
                    saveButton.disabled = false;
                }

                if (typeof window.showSystemErrorModal === 'function') {
                    window.showSystemErrorModal('Unable to save changes right now.');
                }
            }
        }

        initializeInlineSectionFooters();

        document.querySelectorAll([
            '[data-inline-section-save]',
            '#editProfileForm button[type="submit"]',
            '#editProfileForm input[type="submit"]',
            '#editContractForm button[type="submit"]',
            '#editContractForm input[type="submit"]',
        ].join(',')).forEach((button) => {
            button.dataset.confirmSkip = 'true';
        });

        Object.keys(inlineEditConfigs).forEach((targetId) => {
            syncInlineEditSaveState(targetId);
        });
        const initialInlineDirtySectionKey = @json($errors->any() ? old('section') : '');
        const initialInlineDirtyTargetId = initialInlineDirtySectionKey
            ? ({
                profile: 'editProfileForm',
                contract: 'editContractForm',
                physical: 'editPhysicalForm',
                financial: 'editFinancialForm',
                monitoring: 'editMonitoringForm',
                'post-implementation': 'editPostImplementationForm',
            }[initialInlineDirtySectionKey] || '')
            : '';
        if (initialInlineDirtyTargetId) {
            const scopeElement = getInlineEditScopeElement(initialInlineDirtyTargetId);
            if (scopeElement) {
                scopeElement.dataset.forceInlineDirty = 'true';
                syncInlineEditSaveState(initialInlineDirtyTargetId);
            }
        }
        

        function submitFieldChangeForm(field) {
            if (!field) {
                return false;
            }

            const form = field.closest('form');
            if (!form) {
                return false;
            }

            const submitterCandidates = Array.from(form.querySelectorAll(
                '[data-financial-save="true"], [data-monitoring-save="true"], [data-post-implementation-save="true"], button[type="submit"], input[type="submit"]'
            ));
            const submitter = submitterCandidates.find((button) => {
                if (!button || button.disabled) {
                    return false;
                }
                const style = window.getComputedStyle(button);
                return style.display !== 'none' && style.visibility !== 'hidden' && button.offsetParent !== null;
            }) || null;

            if (!submitter) {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }

                return true;
            }

            if (submitter && submitter.dataset) {
                submitter.dataset.confirmSkip = 'true';
                submitter.dataset.confirmed = 'true';
            }

            try {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(submitter);
                } else {
                    form.submit();
                }
            } finally {
                if (submitter && submitter.dataset) {
                    setTimeout(() => {
                        delete submitter.dataset.confirmSkip;
                        delete submitter.dataset.confirmed;
                    }, 0);
                }
            }

            return true;
        }

        function handleFieldSubmitFailure(restoreCallback) {
            if (typeof restoreCallback === 'function') {
                restoreCallback();
            }

            if (typeof window.showSystemErrorModal === 'function') {
                window.showSystemErrorModal('Unable to save this field change right now.');
            }
        }

        function getEditableFieldValue(field) {
            if (!field) {
                return '';
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked ? '1' : '0';
            }

            return field.value;
        }

        function setEditableFieldValue(field, value) {
            if (!field) {
                return;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = value === '1';
                return;
            }

            field.value = value;
        }

        function openReusableConfirmation(message, onConfirm, onCancel) {
            if (typeof window.openConfirmationModal === 'function') {
                window.openConfirmationModal(message, onConfirm, onCancel);
                return;
            }

            if (typeof window.showSystemErrorModal === 'function') {
                window.showSystemErrorModal('Confirmation dialog is unavailable right now.');
            }

            if (typeof onCancel === 'function') {
                onCancel();
            }
        }

        function initializeFieldChangeConfirmation() {
            const editableFieldSelectors = [
                'select[data-physical-edit="true"]',
                'input[data-physical-edit="true"]:not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="reset"])',
                'textarea[data-physical-edit="true"]',
                'select[data-financial-edit="true"]',
                'input[data-financial-edit="true"]:not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="reset"])',
                'textarea[data-financial-edit="true"]',
                'select[data-monitoring-edit="true"]',
                'input[data-monitoring-edit="true"]:not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="reset"])',
                'textarea[data-monitoring-edit="true"]',
                'select[data-post-implementation-edit="true"]',
                'input[data-post-implementation-edit="true"]:not([type="hidden"]):not([type="file"]):not([type="submit"]):not([type="button"]):not([type="reset"])',
                'textarea[data-post-implementation-edit="true"]',
            ];

            document.querySelectorAll(editableFieldSelectors.join(',')).forEach((field) => {
                const rememberCurrentValue = () => {
                    field.dataset.previousValue = getEditableFieldValue(field);
                };

                rememberCurrentValue();

                field.addEventListener('focus', rememberCurrentValue);
                field.addEventListener('mousedown', rememberCurrentValue);
                field.addEventListener('touchstart', rememberCurrentValue, { passive: true });
                field.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        rememberCurrentValue();
                    }
                });

                field.addEventListener('change', () => {
                    if (field.disabled) {
                        field.dataset.previousValue = getEditableFieldValue(field);
                        return;
                    }

                    const previousValue = Object.prototype.hasOwnProperty.call(field.dataset, 'previousValue')
                        ? field.dataset.previousValue
                        : '';
                    const currentValue = getEditableFieldValue(field);

                    if (previousValue === currentValue) {
                        return;
                    }

                    const confirmMessage = field.tagName === 'SELECT'
                        ? 'Dropdown value changed. Do you want to save this change?'
                        : 'Field value changed. Do you want to save this change?';
                    const restorePreviousValue = () => {
                        setEditableFieldValue(field, previousValue);
                        field.dataset.previousValue = previousValue;
                    };

                    openReusableConfirmation(
                        confirmMessage,
                        () => {
                            field.dataset.previousValue = currentValue;
                            const submitted = submitFieldChangeForm(field);
                            if (!submitted) {
                                handleFieldSubmitFailure(restorePreviousValue);
                            }
                        },
                        () => {
                            restorePreviousValue();
                        }
                    );
                });
            });
        }

        document.querySelectorAll('[data-toggle="inline-edit"]').forEach((button) => {
            const targetId = button.getAttribute('data-target');
            setInlineToggleState(button, isInlineEditOpen(targetId));

            button.addEventListener('click', (event) => {
                event.preventDefault();
                if (button.dataset.inlineState === 'editing') {
                    requestInlineEditClose(targetId);
                    return;
                }

                openInlineEdit(button);
                setInlineToggleState(button, true);
            });
        });

        syncBodyModalState();

        document.querySelectorAll('[data-inline-section-save]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-inline-section-save');
                requestInlineSectionSave(targetId);
            });
        });

        document.querySelectorAll('[data-inline-section-cancel]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-inline-section-cancel');
                requestInlineEditClose(targetId);
            });
        });

        document.querySelectorAll('[data-toggle="inline-cancel"]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                requestInlineEditClose(targetId);
            });
        });

        document.querySelectorAll('.lfp-inline-modal-backdrop').forEach((backdrop) => {
            backdrop.addEventListener('click', () => {
                const targetId = backdrop.id.replace(/Backdrop$/, '');
                requestInlineEditClose(targetId);
            });
        });

        Object.keys(inlineEditConfigs).forEach((targetId) => {
            getInlineEditFields(targetId).forEach((field) => {
                const updateState = () => {
                    syncInlineEditSaveState(targetId);
                };

                field.addEventListener('input', updateState);
                field.addEventListener('change', updateState);
            });
        });

        ['editProfileForm', 'editContractForm'].forEach((targetId) => {
            const form = document.getElementById(targetId);
            if (!form) {
                return;
            }

            form.addEventListener('submit', (event) => {
                if (form.dataset.inlineSubmitConfirmed === 'true') {
                    delete form.dataset.inlineSubmitConfirmed;
                    return;
                }

                event.preventDefault();
                syncInlineEditSaveState(targetId);

                if (!hasInlineEditChanges(targetId)) {
                    return;
                }

                const submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                openReusableConfirmation(
                    'Save the changes in this section?',
                    () => {
                        form.dataset.inlineSubmitConfirmed = 'true';

                        if (submitter && typeof form.requestSubmit === 'function') {
                            form.requestSubmit(submitter);
                            return;
                        }

                        form.submit();
                    }
                );
            });
        });

        window.addEventListener('resize', syncBodyModalState);

        document.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            if (!submitter) return;
            let targetId = event.target && event.target.getAttribute('id');
            if (submitter.hasAttribute('data-financial-save')) {
                targetId = 'editFinancialForm';
            }
            if (submitter.hasAttribute('data-monitoring-save')) {
                targetId = 'editMonitoringForm';
            }
            if (submitter.hasAttribute('data-post-implementation-save')) {
                targetId = 'editPostImplementationForm';
            }
            if (!targetId) return;
            const editButton = document.querySelector('[data-toggle="inline-edit"][data-target="' + targetId + '"]');
            setInlineToggleState(editButton, false);
        }, true);

        const contractForm = document.getElementById('editContractForm');
        if (contractForm) {
            contractForm.addEventListener('submit', () => {
                const field = contractForm.querySelector('#contract_amount');
                if (field) {
                    const cleaned = field.value.replace(/[^\d.]/g, '');
                    field.value = cleaned === '' ? '0' : cleaned;
                }
            });
        }

        async function copyTextToClipboard(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                await navigator.clipboard.writeText(text);
                return;
            }

            const fallbackField = document.createElement('textarea');
            fallbackField.value = text;
            fallbackField.setAttribute('readonly', '');
            fallbackField.style.position = 'absolute';
            fallbackField.style.left = '-9999px';
            document.body.appendChild(fallbackField);
            fallbackField.select();
            document.execCommand('copy');
            document.body.removeChild(fallbackField);
        }

        document.querySelectorAll('.project-copy-button').forEach((button) => {
            let resetTimer;

            button.addEventListener('click', async () => {
                const label = button.querySelector('[data-copy-label="true"]');
                const originalLabel = label ? label.textContent : 'Copy';

                try {
                    await copyTextToClipboard(button.dataset.copyText || '');
                    button.classList.add('is-copied');
                    if (label) {
                        label.textContent = 'Copied';
                    }

                    window.clearTimeout(resetTimer);
                    resetTimer = window.setTimeout(() => {
                        button.classList.remove('is-copied');
                        if (label) {
                            label.textContent = originalLabel;
                        }
                    }, 1800);
                } catch (error) {
                    if (typeof window.showSystemErrorModal === 'function') {
                        window.showSystemErrorModal('Unable to copy the project code.');
                    } else {
                        alert('Unable to copy the project code.');
                    }
                }
            });
        });

        const projectTabs = Array.from(document.querySelectorAll('.project-tab'));
        const projectPanels = Array.from(document.querySelectorAll('.project-tab-panel'));
        const oldSectionKey = @json(old('section'));
        const sectionKeyToPanelId = {
            profile: 'projectProfileSection',
            contract: 'contractInfoSection',
            physical: 'physicalAccomplishmentSection',
            financial: 'financialAccomplishmentSection',
            monitoring: 'monitoringInspectionSection',
            'post-implementation': 'postImplementationSection',
            gallery: 'gallerySection',
        };

        function setActiveProjectPanel(panelId) {
            projectPanels.forEach((panel) => {
                const isActive = panel.id === panelId;
                panel.classList.toggle('is-active', isActive);
                panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            projectTabs.forEach((tab) => {
                const isActive = tab.dataset.projectTabTarget === panelId;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
        }

        if (projectTabs.length > 0 && projectPanels.length > 0) {
            projectTabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const panelId = tab.dataset.projectTabTarget;
                    if (panelId) {
                        setActiveProjectPanel(panelId);
                    }
                });
            });

            const hashPanelId = window.location.hash ? window.location.hash.replace('#', '') : '';
            const hasHashPanel = projectPanels.some((panel) => panel.id === hashPanelId);
            const oldPanelId = oldSectionKey ? sectionKeyToPanelId[oldSectionKey] : '';
            const initialPanelId = hasHashPanel
                ? hashPanelId
                : (oldPanelId || 'projectProfileSection');

            setActiveProjectPanel(initialPanelId);
        }

        const galleryTabs = Array.from(document.querySelectorAll('[data-gallery-tab-target]'));
        const galleryPanels = Array.from(document.querySelectorAll('.lfp-gallery-panel'));

        function setActiveGalleryPanel(panelId) {
            galleryPanels.forEach((panel) => {
                const isActive = panel.id === panelId;
                panel.classList.toggle('is-active', isActive);
                panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            galleryTabs.forEach((tab) => {
                const isActive = tab.dataset.galleryTabTarget === panelId;
                tab.classList.toggle('is-active', isActive);
                tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                tab.setAttribute('tabindex', isActive ? '0' : '-1');
            });
        }

        if (galleryTabs.length > 0 && galleryPanels.length > 0) {
            galleryTabs.forEach((tab, index) => {
                tab.addEventListener('click', () => {
                    const panelId = tab.dataset.galleryTabTarget;
                    if (panelId) {
                        setActiveGalleryPanel(panelId);
                    }
                });

                tab.addEventListener('keydown', (event) => {
                    let nextIndex = index;

                    if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
                        nextIndex = (index + 1) % galleryTabs.length;
                    } else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
                        nextIndex = (index - 1 + galleryTabs.length) % galleryTabs.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = galleryTabs.length - 1;
                    } else {
                        return;
                    }

                    event.preventDefault();
                    const nextTab = galleryTabs[nextIndex];
                    const panelId = nextTab ? nextTab.dataset.galleryTabTarget : '';
                    if (nextTab && panelId) {
                        setActiveGalleryPanel(panelId);
                        nextTab.focus();
                    }
                });
            });

            const initialGalleryPanelId = galleryTabs[0].dataset.galleryTabTarget;
            if (initialGalleryPanelId) {
                setActiveGalleryPanel(initialGalleryPanelId);
            }
        }

        const physicalCompareModalWrapper = document.getElementById('physicalCompareModalWrapper');
        const physicalCompareModalBackdrop = document.getElementById('physicalCompareModalBackdrop');
        const physicalCompareModalClose = document.getElementById('physicalCompareModalClose');
        const physicalCompareModalTitle = document.getElementById('physicalCompareModalTitle');
        const physicalCompareModalContent = document.getElementById('physicalCompareModalContent');

        function closePhysicalCompareModal() {
            if (!physicalCompareModalWrapper || !physicalCompareModalBackdrop || !physicalCompareModalContent) {
                return;
            }

            physicalCompareModalWrapper.classList.remove('is-visible');
            physicalCompareModalWrapper.style.display = 'none';
            physicalCompareModalWrapper.setAttribute('aria-hidden', 'true');
            physicalCompareModalBackdrop.classList.remove('is-visible');
            physicalCompareModalBackdrop.setAttribute('aria-hidden', 'true');
            physicalCompareModalContent.innerHTML = '';
            syncBodyModalState();
        }

        function openPhysicalCompareModal(button) {
            if (!physicalCompareModalWrapper || !physicalCompareModalBackdrop || !physicalCompareModalTitle || !physicalCompareModalContent) {
                return;
            }

            const timelineCard = button.closest('.lfp-physical-timeline-card');
            const comparisonSource = timelineCard ? timelineCard.querySelector('.lfp-physical-timeline-columns') : null;
            if (!comparisonSource) {
                return;
            }

            const comparisonClone = comparisonSource.cloneNode(true);
            comparisonClone.classList.remove('lfp-physical-timeline-columns');
            comparisonClone.classList.add('lfp-physical-compare-modal-grid');

            const columns = Array.from(comparisonClone.children);
            columns.forEach((column, index) => {
                column.classList.remove('flex', 'flex-col', 'gap-4');
                column.classList.add('lfp-physical-compare-modal-column');

                const heading = document.createElement('h4');
                heading.className = 'lfp-physical-compare-modal-heading';
                heading.textContent = index === 0 ? 'FOU' : 'RO';
                column.insertBefore(heading, column.firstChild);
            });

            physicalCompareModalTitle.textContent = button.getAttribute('data-physical-compare-title') || 'FOU vs RO Comparison';
            physicalCompareModalContent.innerHTML = '';
            physicalCompareModalContent.appendChild(comparisonClone);

            physicalCompareModalWrapper.style.display = 'flex';
            physicalCompareModalWrapper.classList.add('is-visible');
            physicalCompareModalWrapper.setAttribute('aria-hidden', 'false');
            physicalCompareModalBackdrop.classList.add('is-visible');
            physicalCompareModalBackdrop.setAttribute('aria-hidden', 'false');
            syncBodyModalState();

            if (physicalCompareModalClose) {
                physicalCompareModalClose.focus();
            }
        }

        document.querySelectorAll('[data-physical-compare-trigger="true"]').forEach((button) => {
            button.addEventListener('click', () => {
                openPhysicalCompareModal(button);
            });
        });

        if (physicalCompareModalBackdrop) {
            physicalCompareModalBackdrop.addEventListener('click', () => {
                closePhysicalCompareModal();
            });
        }

        if (physicalCompareModalClose) {
            physicalCompareModalClose.addEventListener('click', () => {
                closePhysicalCompareModal();
            });
        }

        const activityLogSection = document.getElementById('activityLogSection');
        const activityLogBackdrop = document.getElementById('activityLogBackdrop');
        const activityLogFab = document.getElementById('activityLogFab');
        const activityLogClose = document.getElementById('activityLogClose');
        const syncActivityLogPortal = registerActivityLogPortal();

        function setActivityLogVisibility(isVisible) {
            if (!activityLogSection || !activityLogFab || !activityLogBackdrop) {
                return;
            }

            activityLogSection.classList.toggle('is-visible', isVisible);
            activityLogBackdrop.classList.toggle('is-visible', isVisible);
            activityLogFab.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            activityLogFab.dataset.state = isVisible ? 'open' : 'closed';
            activityLogSection.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            activityLogBackdrop.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
            if (typeof syncActivityLogPortal === 'function') {
                syncActivityLogPortal();
            }
            syncBodyModalState();

            const labelSpan = activityLogFab.querySelector('span');
            if (labelSpan) {
                labelSpan.textContent = isVisible ? 'Activity Logs' : 'Activity Logs';
            }

            if (isVisible && activityLogClose) {
                activityLogClose.focus();
            }
        }

        if (activityLogFab && activityLogSection && activityLogBackdrop) {
            activityLogFab.addEventListener('click', () => {
                const isOpen = activityLogSection.classList.contains('is-visible');
                setActivityLogVisibility(!isOpen);
            });

            activityLogBackdrop.addEventListener('click', () => {
                setActivityLogVisibility(false);
            });

            if (activityLogClose) {
                activityLogClose.addEventListener('click', () => {
                    setActivityLogVisibility(false);
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (physicalCompareModalWrapper && physicalCompareModalWrapper.classList.contains('is-visible')) {
                    closePhysicalCompareModal();
                    return;
                }

                if (activityLogSection.classList.contains('is-visible')) {
                    setActivityLogVisibility(false);
                }
            });

            if (window.location.hash === '#activityLogSection') {
                setActivityLogVisibility(true);
            }
        }

        // Monthly accordion uses native <details> only; no JS needed.
    </script>
@endsection
