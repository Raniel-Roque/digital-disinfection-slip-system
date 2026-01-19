<div>
    <x-modals.confirmation-modal 
        show="showModal" 
        title="Clean Up Photos"
        message="Are you sure you want to run Photo cleanup?"
        :details="'This will permanently delete photos older than <strong>' . (\App\Models\Setting::where('setting_name', 'attachment_retention_days')->value('value') ?? '30') . ' days</strong>.'"
        onConfirm="cleanup"
        confirmText="Run Cleanup" 
        cancelText="Cancel" 
        confirmColor="orange" />
</div>
