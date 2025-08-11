<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class BrandingService
{
    protected $tenant;
    protected $branding;

    public function __construct()
    {
        $this->tenant = tenant();
        $this->branding = $this->tenant ? $this->tenant->getSetting('branding', []) : [];
    }

    public function getPrimaryColor(): string
    {
        return $this->branding['primary_color'] ?? '#4F46E5';
    }

    public function getSecondaryColor(): string
    {
        return $this->branding['secondary_color'] ?? '#6B7280';
    }

    public function getLogo(): ?string
    {
        $logo = $this->branding['logo'] ?? null;
        return $logo ? Storage::url($logo) : null;
    }

    public function getOrganizationName(): string
    {
        return $this->tenant ? $this->tenant->getName() : config('app.name');
    }

    public function getInitials(): string
    {
        $name = $this->getOrganizationName();
        $words = explode(' ', $name);
        
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        
        return strtoupper(substr($name, 0, 2));
    }

    public function getCssVariables(): array
    {
        return [
            '--primary-color' => $this->getPrimaryColor(),
            '--secondary-color' => $this->getSecondaryColor(),
            '--primary-rgb' => $this->hexToRgb($this->getPrimaryColor()),
            '--secondary-rgb' => $this->hexToRgb($this->getSecondaryColor()),
        ];
    }

    public function getCssString(): string
    {
        $variables = $this->getCssVariables();
        $css = ':root {';
        
        foreach ($variables as $property => $value) {
            $css .= "{$property}: {$value};";
        }
        
        $css .= '}';
        
        return $css;
    }

    protected function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            
            return "{$r}, {$g}, {$b}";
        }
        
        return '79, 70, 229'; // Default indigo RGB
    }

    public function getLoginPageBranding(): array
    {
        return [
            'logo' => $this->getLogo(),
            'organization_name' => $this->getOrganizationName(),
            'initials' => $this->getInitials(),
            'primary_color' => $this->getPrimaryColor(),
            'secondary_color' => $this->getSecondaryColor(),
        ];
    }
}
