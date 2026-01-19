<div>
    <x-modals.confirmation-modal 
        show="showModal" 
        title="Clean Up Audit Logs"
        message="Are you sure you want to run audit logs cleanup?"
        :details="'This will permanently delete audit trail logs older than <strong>' . (\App\Models\Setting::where('setting_name', 'log_retention_months')->value('value') ?? '3') . ' months</strong>.'"
        onConfirm="cleanup"
        confirmText="Run Cleanup" 
        cancelText="Cancel" 
        confirmColor="orange" />
</div>
