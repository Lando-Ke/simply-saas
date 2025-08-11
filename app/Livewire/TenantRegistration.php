<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;

class TenantRegistration extends Component
{
    // Organization Information
    public $organization_name = '';
    public $subdomain = '';
    public $organization_email = '';
    
    // Admin User Information
    public $admin_name = '';
    public $admin_email = '';
    public $admin_password = '';
    public $admin_password_confirmation = '';
    
    // Flags
    public $step = 1;
    public $subdomainAvailable = null;
    public $isChecking = false;

    protected $rules = [
        'organization_name' => 'required|string|max:255',
        'subdomain' => 'required|string|max:50|regex:/^[a-z0-9-]+$/|unique:domains,domain',
        'organization_email' => 'required|email|max:255',
        'admin_name' => 'required|string|max:255',
        'admin_email' => 'required|email|max:255',
        'admin_password' => 'required|string|min:8|confirmed',
    ];

    protected $messages = [
        'subdomain.regex' => 'Subdomain can only contain lowercase letters, numbers, and hyphens.',
        'subdomain.unique' => 'This subdomain is already taken.',
    ];

    public function updatedSubdomain()
    {
        $this->subdomainAvailable = null;
        if (strlen($this->subdomain) >= 3) {
            $this->checkSubdomainAvailability();
        }
    }

    public function checkSubdomainAvailability()
    {
        $this->isChecking = true;
        
        $this->validate(['subdomain' => 'required|string|max:50|regex:/^[a-z0-9-]+$/']);
        
        $exists = Domain::where('domain', $this->subdomain . '.localhost')->exists();
        $this->subdomainAvailable = !$exists;
        
        $this->isChecking = false;
    }

    public function nextStep()
    {
        if ($this->step === 1) {
            $this->validate([
                'organization_name' => 'required|string|max:255',
                'subdomain' => 'required|string|max:50|regex:/^[a-z0-9-]+$/',
                'organization_email' => 'required|email|max:255',
            ]);

            // Check subdomain availability one more time
            $this->checkSubdomainAvailability();
            
            if (!$this->subdomainAvailable) {
                $this->addError('subdomain', 'This subdomain is not available.');
                return;
            }

            $this->step = 2;
        }
    }

    public function previousStep()
    {
        if ($this->step === 2) {
            $this->step = 1;
        }
    }

    public function register()
    {
        $this->validate();

        // Final subdomain check
        $this->checkSubdomainAvailability();
        if (!$this->subdomainAvailable) {
            $this->addError('subdomain', 'This subdomain is not available.');
            $this->step = 1;
            return;
        }

        DB::beginTransaction();
        
        try {
            // Create tenant
            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'data' => [
                    'name' => $this->organization_name,
                    'email' => $this->organization_email,
                    'status' => 'active',
                    'trial_ends_at' => now()->addDays(14), // 14-day trial
                    'settings' => [
                        'branding' => [
                            'logo' => null,
                            'primary_color' => '#4F46E5', // Default indigo
                            'secondary_color' => '#6B7280', // Default gray
                        ]
                    ]
                ]
            ]);

            // Create domain
            Domain::create([
                'domain' => $this->subdomain . '.localhost',
                'tenant_id' => $tenant->id,
            ]);

            // Initialize tenant context to create admin user
            tenancy()->initialize($tenant);

            // Create admin user in tenant context
            $adminUser = User::create([
                'name' => $this->admin_name,
                'email' => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'email_verified_at' => now(),
            ]);

            // Assign admin role if roles exist
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                try {
                    $adminUser->assignRole('admin');
                } catch (\Exception $e) {
                    // Role might not exist in tenant context, continue anyway
                }
            }

            DB::commit();

            // Redirect to tenant domain or show success message
            session()->flash('tenant_created', [
                'tenant_id' => $tenant->id,
                'domain' => $this->subdomain . '.localhost',
                'admin_email' => $this->admin_email,
            ]);

            // Reset form
            $this->reset();
            $this->step = 1;

        } catch (\Exception $e) {
            DB::rollback();
            
            // End tenant context if it was initialized
            tenancy()->end();
            
            $this->addError('general', 'An error occurred during registration. Please try again.');
            
            // Log the error for debugging
            \Log::error('Tenant registration failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tenant-registration');
    }
}