<div>
    <x-modals.confirmation-modal 
        show="showModal" 
        title="Clean Up Resolved Issues"
        message="Are you sure you want to run issues cleanup?"
        :details="'This will permanently delete resolved issues older than <strong>' . (\App\Models\Setting::where('setting_name', 'resolved_issues_retention_months')->value('value') ?? '3') . ' months</strong>.'"
        onConfirm="cleanup"
        confirmText="Run Cleanup" 
        cancelText="Cancel" 
        confirmColor="orange" />
</div>
