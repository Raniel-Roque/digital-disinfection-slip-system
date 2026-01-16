<?php

namespace App\Livewire\User;

use App\Models\Issue;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class MiscIssue extends Component
{
    public $description = '';
    public $showSuccess = false;

    public function submitIssue()
    {
        $this->validate([
            'description' => 'required|string|min:10|max:1000',
        ], [
            'description.required' => 'Please provide a description of the issue.',
            'description.min' => 'The description must be at least 10 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ]);

        try {
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
            
            $this->description = '';
            $this->showSuccess = true;
        } catch (\Exception $e) {
            Log::error('Failed to create miscellaneous issue: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to submit issue. Please try again.', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.user.misc-issue');
    }
}
