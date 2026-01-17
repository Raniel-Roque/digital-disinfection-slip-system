<?php

namespace App\Livewire\Shared\Issues;

use App\Models\Issue;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class Create extends Component
{
    public $showModal = false;
    public $description = '';
    public $showSuccess = false;
    public $isSubmitting = false;
    public $minUserType = 0; // Default for guards

    protected $listeners = ['openCreateModal' => 'openModal'];

    public function mount($config = [])
    {
        $this->minUserType = $config['minUserType'] ?? 0;
    }

    public function openModal()
    {
        // Authorization check - guards can create issues
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->showModal = false;
    }

    public function resetForm()
    {
        $this->description = '';
        $this->showSuccess = false;
        $this->resetErrorBag();
    }

    public function submitIssue()
    {
        // Prevent multiple submissions
        if ($this->isSubmitting) {
            return;
        }

        // Authorization check
        if (Auth::user()->user_type < $this->minUserType) {
            abort(403, 'Unauthorized action.');
        }

        $this->isSubmitting = true;

        try {
            $this->validate([
                'description' => 'required|string|min:10|max:1000',
            ], [
                'description.required' => 'Please provide a description of the issue.',
                'description.min' => 'The description must be at least 10 characters.',
                'description.max' => 'The description must not exceed 1000 characters.',
            ]);

            $issue = Issue::create([
                'user_id' => Auth::id(),
                'slip_id' => null, // Miscellaneous issue
                'description' => $this->description,
            ]);

            // Log the miscellaneous issue creation
            Logger::create(
                Issue::class,
                $issue->id,
                "Submitted miscellaneous issue",
                $issue->only(['user_id', 'slip_id', 'description'])
            );

            Cache::forget('issues_all');

            $this->dispatch('toast', message: 'Issue submitted successfully. It will be reviewed by administrators.', type: 'success');
            $this->dispatch('issue-created');
            
            $this->description = '';
            $this->showSuccess = true;
            $this->showModal = false;
        } catch (\Exception $e) {
            Log::error('Failed to create miscellaneous issue: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to submit issue. Please try again.', type: 'error');
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function render()
    {
        return view('livewire.shared.issues.create');
    }
}
