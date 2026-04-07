<style>
    .system-dialog-modal {
        position: fixed;
        inset: 0;
        z-index: 3000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .system-dialog-modal.is-open {
        display: flex;
    }

    .system-dialog-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
    }

    .system-dialog-card {
        position: relative;
        z-index: 1;
        width: min(460px, 100%);
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .system-dialog-header {
        padding: 16px 18px 10px;
        border-bottom: 1px solid #f1f5f9;
    }

    .system-dialog-title {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }

    .system-dialog-body {
        padding: 14px 18px;
        font-size: 14px;
        line-height: 1.6;
        color: #334155;
    }

    .system-dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 12px 18px 18px;
    }

    .system-dialog-btn {
        border: none;
        border-radius: 8px;
        padding: 9px 16px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .system-dialog-btn.cancel {
        background: #e5e7eb;
        color: #1f2937;
    }

    .system-dialog-btn.confirm {
        background: #002c76;
        color: #ffffff;
    }

    .system-dialog-btn.error-ok {
        background: #dc2626;
        color: #ffffff;
    }

    body.system-dialog-open {
        overflow: hidden;
    }
</style>

<div id="globalConfirmModal" class="system-dialog-modal" aria-hidden="true">
    <div class="system-dialog-backdrop" data-confirm-dismiss></div>
    <div class="system-dialog-card" role="dialog" aria-modal="true" aria-labelledby="globalConfirmModalTitle">
        <div class="system-dialog-header">
            <h3 id="globalConfirmModalTitle" class="system-dialog-title">Please Confirm</h3>
        </div>
        <div class="system-dialog-body" id="globalConfirmModalMessage"></div>
        <div class="system-dialog-actions">
            <button type="button" class="system-dialog-btn cancel" id="globalConfirmCancelBtn">Cancel</button>
            <button type="button" class="system-dialog-btn confirm" id="globalConfirmOkBtn">Confirm</button>
        </div>
    </div>
</div>
