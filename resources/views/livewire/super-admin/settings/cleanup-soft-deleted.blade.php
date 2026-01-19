<div>
    <x-modals.confirmation-modal 
        show="showModal" 
        title="Clean Up Soft-Deleted Records"
        message="Are you sure you want to run soft-deleted records cleanup?"
        :details="'This will permanently delete soft-deleted records (users, vehicles, drivers, locations, slips, issues) older than <strong>' . (\App\Models\Setting::where('setting_name', 'soft_deleted_retention_months')->value('value') ?? '3') . ' months</strong>.'"
        onConfirm="cleanup"
        confirmText="Run Cleanup" 
        cancelText="Cancel" 
        confirmColor="orange" />
</div>
