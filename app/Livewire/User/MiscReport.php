<?php

namespace App\Livewire\User;

use App\Models\Report;
use App\Services\Logger;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MiscReport extends Component
{
    public $description = '';
    public $showSuccess = false;

    public function submitReport()
    {
        $this->validate([
            'description' => 'required|string|min:10|max:1000',
        ], [
            'description.required' => 'Please provide a description of the issue.',
            'description.min' => 'The description must be at least 10 characters.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ]);

        try {
            $report = Report::create([
                'user_id' => Auth::id(),
                'slip_id' => null, // Miscellaneous report
                'description' => $this->description,
            ]);

            $this->dispatch('toast', message: 'Report submitted successfully. It will be reviewed by administrators.', type: 'success');
            
            $this->description = '';
            $this->showSuccess = true;
        } catch (\Exception $e) {
            Log::error('Failed to create miscellaneous report: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Failed to submit report. Please try again.', type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.user.misc-report');
    }
}
