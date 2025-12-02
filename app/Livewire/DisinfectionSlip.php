<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DisinfectionSlip as DisinfectionSlipModel;

class DisinfectionSlip extends Component
{
    public $showDetailsModal = false;
    public $showAttachmentModal = false;

    public $selectedSlip = null;
    public $attachmentFile = null;

    protected $listeners = ['open-disinfection-details' => 'openDetailsModal'];

    public function openDetailsModal($id)
    {
        $this->selectedSlip = DisinfectionSlipModel::with([
            'truck',
            'location',
            'destination',
            'driver',
            'attachment',
            'hatcheryGuard',
            'receivedGuard'
        ])->find($id);

        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->js('setTimeout(() => $wire.clearSelectedSlip(), 300)');
    }

    public function clearSelectedSlip()
    {
        $this->selectedSlip = null;
    }

    /** Attachment Modal Logic */
    public function openAttachmentModal($file)
    {
        $this->attachmentFile = $file;
        $this->showAttachmentModal = true;
    }

    public function closeAttachmentModal()
    {
        $this->showAttachmentModal = false;
        $this->js('setTimeout(() => $wire.clearAttachment(), 300)');
        
    }

    public function clearAttachment() 
    {
        $this->attachmentFile = null;
    }

    public function render()
    {
        return view('livewire.disinfection-slip');
    }
}
