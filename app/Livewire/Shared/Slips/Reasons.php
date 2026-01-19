<?php

namespace App\Livewire\Shared\Slips;

use App\Models\Reason;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Reasons extends Component
{
    // Modal states
    public $showReasonsModal = false;
    public $showCreateReasonModal = false;
    public $showReasonsDeleteConfirmation = false;

    // Form fields
    public $newReasonText = '';
    public $editingReasonId = null;
    public $editingReasonText = '';
    public $originalReasonText = '';
    public $savingReason = false;
    public $reasonToDelete = null;

    // Search and filter
    public $searchReasonSettings = '';
    public $filterReasonStatus = 'all'; // Filter: 'all', 'enabled', 'disabled'
    public $reasonsPage = 1; // Page for reasons pagination

    // Configuration - minimum user_type required (2 = superadmin only)
    public $minUserType = 2;

    protected $listeners = [
        'openReasonsModal' => 'openReasonsModal',
    ];

    /**
     * Check if the current user has permission to manage reasons
     */
    private function canManageReasons()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Super guards have admin-level permissions for reasons
        if ($user->user_type === 0 && $user->super_guard) {
            return true;
        }

        // Regular user type check
        return $user->user_type >= $this->minUserType;
    }

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 2;
        $this->showReasonsModal = false;
    }

    // Reason management methods
    public function updatedSearchReasonSettings()
    {
        $this->reasonsPage = 1; // Reset to first page when search changes
    }

    public function updatedFilterReasonStatus()
    {
        $this->reasonsPage = 1; // Reset to first page when filter changes
    }

    public function getPaginatedReasons($search = '', $page = 1, $perPage = 20, $includeIds = [])
    {
        $query = Reason::query()
            ->select(['id', 'reason_text']);

        if (!empty($search)) {
            $query->where('reason_text', 'like', '%' . $search . '%');
        }

        // Handle included IDs for autocomplete
        if (!empty($includeIds)) {
            $includedItems = Reason::whereIn('id', $includeIds)
                ->select(['id', 'reason_text'])
                ->pluck('reason_text', 'id')
                ->toArray();

            $query->whereNotIn('id', $includeIds);
        }

        $query->orderBy('reason_text', 'asc');

        $results = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $results->pluck('reason_text', 'id')->toArray();

        // Merge included items if any
        if (!empty($includedItems)) {
            $data = array_merge($includedItems, $data);
        }

        return [
            'data' => $data,
            'current_page' => $results->currentPage(),
            'last_page' => $results->lastPage(),
            'per_page' => $results->perPage(),
            'total' => $results->total()
        ];
    }

    public function getReasonsProperty()
    {
        $query = Reason::query()
            ->select(['id', 'reason_text', 'is_disabled'])
            ->orderBy('reason_text', 'asc');

        // Filter by status if not 'all'
        if ($this->filterReasonStatus !== 'all') {
            $isDisabled = $this->filterReasonStatus === 'disabled';
            $query->where('is_disabled', $isDisabled);
        }

        // Filter by search term if provided
        if (!empty($this->searchReasonSettings)) {
            $searchTerm = strtolower(trim($this->searchReasonSettings));
            $query->whereRaw('LOWER(reason_text) LIKE ?', ['%' . $searchTerm . '%']);
        }

        // Use database pagination
        $perPage = 5;
        return $query->paginate($perPage, ['*'], 'page', $this->reasonsPage);
    }

    // Separate pagination methods for reasons (don't override default pagination)
    public function gotoReasonsPage($page)
    {
        $this->reasonsPage = $page;
    }

    public function previousReasonsPage()
    {
        if ($this->reasonsPage > 1) {
            $this->reasonsPage--;
        }
    }

    public function nextReasonsPage()
    {
        $this->reasonsPage++;
    }

    public function getPage()
    {
        return request()->get('page', 1);
    }

    public function openCreateReasonModal()
    {
        $this->newReasonText = '';
        $this->showCreateReasonModal = true;
    }

    public function closeCreateReasonModal()
    {
        $this->newReasonText = '';
        $this->showCreateReasonModal = false;
    }

    public function createReason()
    {
        // Authorization check
        if (!$this->canManageReasons()) {
            abort(403, 'Unauthorized action.');
        }

        // Validate the new reason text
        $this->validate([
            'newReasonText' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $trimmedValue = trim($value);
                    if (empty($trimmedValue)) {
                        $fail('Reason text cannot be empty.');
                        return;
                    }

                    $exists = Reason::whereRaw('LOWER(reason_text) = ?', [strtolower($trimmedValue)])
                        ->exists();

                    if ($exists) {
                        $fail('This reason already exists.');
                    }
                },
            ],
        ], [
            'newReasonText.required' => 'Reason text is required.',
            'newReasonText.max' => 'Reason text must not exceed 255 characters.',
        ], [
            'newReasonText' => 'Reason text',
        ]);

        $reason = Reason::create([
            'reason_text' => trim($this->newReasonText),
            'is_disabled' => false,
        ]);

        // Log the create action
        Logger::create(
            Reason::class,
            $reason->id,
            "Added new reason: {$reason->reason_text}",
            $reason->only(['reason_text', 'is_disabled'])
        );

        $this->dispatch('toast', message: 'Reason created successfully.', type: 'success');

        $this->closeCreateReasonModal();

        // Reset to first page to show the new reason
        $this->reasonsPage = 1;
        $this->dispatch('reason-created');
    }

    public function startEditingReason($reasonId)
    {
        $reason = Reason::find($reasonId);

        if ($reason) {
            $this->editingReasonId = $reasonId;
            $this->editingReasonText = $reason->reason_text;
            $this->originalReasonText = $reason->reason_text;
        }
    }

    public function saveReasonEdit()
    {
        // Authorization check
        if (!$this->canManageReasons()) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'editingReasonText' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $trimmedValue = trim($value);
                    if (empty($trimmedValue)) {
                        $fail('Reason text cannot be empty.');
                        return;
                    }

                    $exists = Reason::where('id', '!=', $this->editingReasonId)
                        ->whereRaw('LOWER(reason_text) = ?', [strtolower($trimmedValue)])
                        ->exists();

                    if ($exists) {
                        $fail('This reason already exists.');
                    }
                },
            ],
        ], [
            'editingReasonText.required' => 'Reason text is required.',
            'editingReasonText.max' => 'Reason text must not exceed 255 characters.',
        ], [
            'editingReasonText' => 'Reason text',
        ]);

        // Check if there are actual changes
        if (trim($this->editingReasonText) === $this->originalReasonText) {
            $this->cancelReasonEdit();
            return;
        }

        $this->confirmSaveReasonEdit();
    }

    public function confirmSaveReasonEdit()
    {
        $this->savingReason = true;

        $reason = Reason::find($this->editingReasonId);

        if ($reason) {
            // Authorization check
            if (!$this->canManageReasons()) {
                abort(403, 'Unauthorized action.');
            }

            $oldValues = $reason->only(['reason_text', 'is_disabled']);

            $reason->reason_text = trim($this->editingReasonText);
            $reason->save();

            // Log the update action
            Logger::update(
                Reason::class,
                $reason->id,
                "Updated reason: {$reason->reason_text}",
                $oldValues,
                $reason->only(['reason_text', 'is_disabled'])
            );

            $this->dispatch('toast', message: 'Reason updated successfully.', type: 'success');
        }

        $this->savingReason = false;
        $this->editingReasonId = null;
        $this->editingReasonText = '';
        $this->originalReasonText = '';
    }

    public function cancelReasonEdit()
    {
        $this->editingReasonId = null;
        $this->editingReasonText = '';
        $this->originalReasonText = '';
    }

    public function toggleReasonDisabled($reasonId)
    {
        // Authorization check
        if (!$this->canManageReasons()) {
            abort(403, 'Unauthorized action.');
        }

        $reason = Reason::find($reasonId);

        if ($reason) {
            $oldValues = $reason->only(['reason_text', 'is_disabled']);

            $reason->is_disabled = !$reason->is_disabled;
            $reason->save();

            // Log the update action
            Logger::update(
                Reason::class,
                $reason->id,
                ($reason->is_disabled ? "Disabled reason: {$reason->reason_text}" : "Enabled reason: {$reason->reason_text}"),
                $oldValues,
                $reason->only(['reason_text', 'is_disabled'])
            );

            $status = $reason->is_disabled ? 'disabled' : 'enabled';
            $this->dispatch('toast', message: "Reason {$status} successfully.", type: 'success');
        }
    }

    public function confirmDeleteReason($reasonId)
    {
        $this->reasonToDelete = $reasonId;
        $this->showReasonsDeleteConfirmation = true;
    }

    public function deleteReason()
    {
        // Authorization check
        if (!$this->canManageReasons()) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->reasonToDelete) {
            return;
        }

        $reason = Reason::find($this->reasonToDelete);

        if ($reason) {
            $oldValues = $reason->only(['reason_text', 'is_disabled']);
            $reasonText = $reason->reason_text;
            $reasonId = $reason->id;

            $reason->delete();

            // Log the delete action
            Logger::delete(
                Reason::class,
                $reasonId,
                "Deleted reason: {$reasonText}",
                $oldValues
            );

            $this->dispatch('toast', message: 'Reason deleted successfully.', type: 'success');
        }

        $this->showReasonsDeleteConfirmation = false;
        $this->reasonToDelete = null;
    }

    public function attemptCloseReasonsModal()
    {
        if ($this->editingReasonId !== null) {
            $this->dispatch('toast', message: 'Please save or cancel your current edit before closing.', type: 'warning');
            return;
        }

        $this->closeReasonsModal();
    }

    public function closeReasonsModal()
    {
        $this->newReasonText = '';
        $this->searchReasonSettings = '';
        $this->editingReasonId = null;
        $this->editingReasonText = '';
        $this->originalReasonText = '';
        $this->savingReason = false;
        $this->showReasonsModal = false;
        $this->showReasonsDeleteConfirmation = false;
        $this->showCreateReasonModal = false;
        $this->reasonToDelete = null;
        $this->reasonsPage = 1;
        $this->filterReasonStatus = 'all';
    }

    public function openReasonsModal()
    {
        $this->newReasonText = '';
        $this->searchReasonSettings = '';
        $this->editingReasonId = null;
        $this->editingReasonText = '';
        $this->originalReasonText = '';
        $this->savingReason = false;
        $this->showReasonsModal = true;
        $this->showReasonsDeleteConfirmation = false;
        $this->showCreateReasonModal = false;
        $this->reasonToDelete = null;
        $this->reasonsPage = 1;
        $this->filterReasonStatus = 'all';
    }

    public function render()
    {
        return view('livewire.shared.slips.reasons');
    }
}