<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class CssShowcaseController extends Controller
{
    public function index(): string
    {
        return $this->render('showcase/index', [
            'title' => 'CSS Frameworks Integration Hub',
            'activeEngine' => 'Hub',
        ]);
    }

    public function tailwind(): string
    {
        return $this->render('showcase/tailwind', [
            'title' => 'Tailwind CSS Integration Showcase',
            'activeEngine' => 'Tailwind',
            'components' => [
                ['name' => 'Card Component', 'type' => 'Utility First', 'status' => 'Active'],
                ['name' => 'Button Group', 'type' => 'Flex Container', 'status' => 'Ready'],
                ['name' => 'Grid Layout', 'type' => 'Responsive Grid', 'status' => 'Compiled'],
            ]
        ]);
    }

    public function openprops(): string
    {
        return $this->render('showcase/openprops', [
            'title' => 'Open Props CSS Custom Variables Showcase',
            'activeEngine' => 'Open Props',
            'props' => [
                '--size-1' => '0.25rem',
                '--size-3' => '1rem',
                '--gradient-1' => 'linear-gradient(135deg, #4f46e5, #06b6d4)',
                '--shadow-3' => '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
            ]
        ]);
    }

    public function vanilla(): string
    {
        return $this->render('showcase/vanilla', [
            'title' => 'Vanilla Glassmorphic CSS Engine Showcase',
            'activeEngine' => 'Vanilla Glassmorphism',
            'metrics' => [
                'Boot Time' => '1.8 ms',
                'Dependencies' => '0 KB',
                'CSS Memory' => '12 KB',
            ]
        ]);
    }
}
