<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class BrandingManager extends Component
{
    use WithFileUploads;

    public $tenant;
    public $logo;
    public $primary_color = '#4F46E5';
    public $secondary_color = '#6B7280';
    public $organization_name = '';
    
    // File upload properties
    public $logoFile;
    public $logoPreview;

    public function mount()
    {
        $this->tenant = tenant();
        
        if ($this->tenant) {
            $this->organization_name = $this->tenant->getName();
            $this->primary_color = $this->tenant->getSetting('branding.primary_color', '#4F46E5');
            $this->secondary_color = $this->tenant->getSetting('branding.secondary_color', '#6B7280');
            $this->logo = $this->tenant->getSetting('branding.logo');
        }
    }

    public function updatedLogoFile()
    {
        $this->validate([
            'logoFile' => 'image|max:1024', // 1MB Max
        ]);

        $this->logoPreview = $this->logoFile->temporaryUrl();
    }

    public function saveBranding()
    {
        $this->validate([
            'organization_name' => 'required|string|max:255',
            'primary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'logoFile' => 'nullable|image|max:1024',
        ]);

        if (!$this->tenant) {
            session()->flash('error', 'Tenant not found.');
            return;
        }

        // Handle logo upload
        $logoPath = $this->logo; // Keep existing logo if no new one
        if ($this->logoFile) {
            // Delete old logo if exists
            if ($this->logo) {
                Storage::disk('public')->delete($this->logo);
            }
            
            // Store new logo
            $logoPath = $this->logoFile->store('tenant-logos', 'public');
        }

        // Update tenant data
        $data = $this->tenant->data ?? [];
        $data['name'] = $this->organization_name;
        $data['settings']['branding'] = [
            'logo' => $logoPath,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
        ];

        $this->tenant->update(['data' => $data]);

        // Reset file upload
        $this->logoFile = null;
        $this->logoPreview = null;
        $this->logo = $logoPath;

        session()->flash('message', 'Branding updated successfully!');
    }

    public function removeLogo()
    {
        if ($this->logo) {
            Storage::disk('public')->delete($this->logo);
            
            $data = $this->tenant->data ?? [];
            $data['settings']['branding']['logo'] = null;
            $this->tenant->update(['data' => $data]);
            
            $this->logo = null;
            session()->flash('message', 'Logo removed successfully!');
        }
    }

    public function resetColors()
    {
        $this->primary_color = '#4F46E5';
        $this->secondary_color = '#6B7280';
    }

    public function render()
    {
        return view('livewire.tenant.branding-manager');
    }
}