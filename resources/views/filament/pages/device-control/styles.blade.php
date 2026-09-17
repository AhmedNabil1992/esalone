    <style>
        @keyframes pulse-border {
            0% { border-color: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { border-color: #ef4444; box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { border-color: #ef4444; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .device-control-page {
            min-width: 0;
        }

        .device-control-modal-panel,
        .device-control-table-scroll {
            scrollbar-width: thin;
        }

        .dark .device-control-page [style*="background:#fff"],
        .dark .device-control-page [style*="background: #fff"],
        .dark .device-control-page [style*="background:#ffffff"] {
            background: #18181b !important;
        }

        .dark .device-control-page [style*="background:#f9fafb"],
        .dark .device-control-page [style*="background:#f3f4f6"] {
            background: #27272a !important;
        }

        .dark .device-control-page [style*="color:#111827"],
        .dark .device-control-page [style*="color:#0f172a"] {
            color: #fafafa !important;
        }

        .dark .device-control-page [style*="color:#374151"],
        .dark .device-control-page [style*="color:#1e293b"] {
            color: #e4e4e7 !important;
        }

        .dark .device-control-page [style*="color:#4b5563"],
        .dark .device-control-page [style*="color:#6b7280"] {
            color: #a1a1aa !important;
        }

        .dark .device-control-page [style*="border:1px solid #e5e7eb"],
        .dark .device-control-page [style*="border:1px solid #d1d5db"],
        .dark .device-control-page [style*="border:1px solid #cbd5e1"] {
            border-color: #3f3f46 !important;
        }

        .dark .device-control-page [style*="border-bottom:1px solid #e5e7eb"],
        .dark .device-control-page [style*="border-bottom:1px solid #f3f4f6"] {
            border-bottom-color: #3f3f46 !important;
        }

        .dark .device-control-page [style*="background:#fffbeb"] {
            background: #451a03 !important;
            color: #fde68a !important;
        }

        .dark .device-control-modal-panel {
            background: #18181b !important;
            border-color: #3f3f46 !important;
            color: #fafafa !important;
        }

        .dark .device-control-modal-panel .bg-white,
        .dark .device-control-modal-panel .bg-slate-50 {
            background: #09090b !important;
        }

        .dark .device-control-close-error {
            background: #450a0a !important;
            border-color: #991b1b !important;
        }

        .dark .device-control-close-error,
        .dark .device-control-close-error * {
            color: #fecaca !important;
        }

        @media (max-width: 640px) {
            .device-control-page {
                gap: 12px !important;
            }

            .device-control-type-tabs {
                margin-inline: -8px;
                padding-inline: 8px;
            }

            .device-control-device-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }

            .device-control-modal-backdrop {
                align-items: stretch !important;
                padding: 0 !important;
            }

            .device-control-modal-panel {
                width: 100% !important;
                max-height: 100dvh !important;
                min-height: 100dvh;
                border-radius: 0 !important;
                border-inline: 0 !important;
                padding: 14px 12px calc(14px + env(safe-area-inset-bottom)) !important;
            }

            .device-control-close-grid,
            .device-control-form-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            .device-control-payment-row {
                grid-template-columns: minmax(0, 1fr) auto !important;
            }

            .device-control-payment-row > div {
                grid-column: 1 / -1;
            }

            .device-control-payment-row > button {
                grid-column: 2;
                justify-self: end;
            }

            .device-control-modal-actions {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }

            .device-control-modal-actions button {
                min-height: 42px;
                white-space: normal;
            }

            .device-control-table-scroll {
                max-height: 240px !important;
                overscroll-behavior: contain;
            }
        }

        @media (max-width: 380px) {
            .device-control-device-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }
        }
    </style>

