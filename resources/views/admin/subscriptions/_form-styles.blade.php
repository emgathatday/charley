<style>
.subscription-form-page { max-width: 1040px; margin: 0 auto; }
.subscription-form-page .form-card { background: var(--surface, #fff); border: 1px solid var(--line, #E2E8F0); border-radius: 14px; box-shadow: 0 16px 40px rgba(15, 23, 42, .06); overflow: hidden; }
.subscription-form-page .form-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 22px 24px; border-bottom: 1px solid var(--line, #E2E8F0); }
.subscription-form-page .form-card-head h2 { margin: 0; font-size: 18px; line-height: 1.2; color: var(--ink, #0F172A); }
.subscription-form-page .form-card-head span { color: var(--ink-faint, #64748B); font-size: 13px; }
.subscription-form-page .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; padding: 24px; }
.subscription-form-page .field-block { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
.subscription-form-page .field-block.span-2 { grid-column: 1 / -1; }
.subscription-form-page .field-block label { color: var(--ink-soft, #334155); font-size: 13px; font-weight: 700; }
.subscription-form-page .form-input { width: 100%; min-height: 44px; border: 1px solid var(--line, #CBD5E1); border-radius: 10px; background: #fff; color: var(--ink, #0F172A); font-size: 14px; padding: 10px 12px; outline: none; }
.subscription-form-page textarea.form-input { resize: vertical; min-height: 112px; }
.subscription-form-page .form-input:focus { border-color: #4F8DFD; box-shadow: 0 0 0 3px rgba(79, 141, 253, .14); }
.subscription-form-page .form-input.is-invalid { border-color: #DC2626; }
.subscription-form-page .field-error { color: #DC2626; font-size: 12px; font-weight: 600; }
.subscription-form-page .form-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 18px 24px; border-top: 1px solid var(--line, #E2E8F0); background: #F8FAFC; }
.subscription-form-page .form-actions button { border: 0; }
@media (max-width: 768px) {
    .subscription-form-page .page-head { align-items: flex-start; }
    .subscription-form-page .page-head-actions { width: 100%; }
    .subscription-form-page .page-head-actions a { flex: 1; justify-content: center; }
    .subscription-form-page .form-grid { grid-template-columns: 1fr; padding: 18px; }
    .subscription-form-page .form-card-head, .subscription-form-page .form-actions { padding-left: 18px; padding-right: 18px; }
}
</style>