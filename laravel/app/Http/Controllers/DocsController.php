<?php

namespace App\Http\Controllers;

class DocsController extends Controller
{
    /**
     * Valid documentation pages.
     */
    private array $pages = [
        'home' => 'Home',
        'basics' => 'Game Basics',
        'buildings' => 'Buildings',
        'army' => 'Army & Units',
        'attack' => 'Attacking',
        'explore' => 'Exploring',
        'research' => 'Research',
        'trade' => 'Trading',
        'alliance' => 'Alliances',
        'aid' => 'Sending Aid',
        'resources' => 'Resources',
        'people' => 'Population',
        'wall' => 'Great Wall',
        'manage' => 'Empire Management',
        'search' => 'Searching Players',
        'civs' => 'Civilizations',
    ];

    public function show(?string $page = null)
    {
        $page = $page ?? 'home';

        if (!array_key_exists($page, $this->pages)) {
            $page = 'home';
        }

        $title = $this->pages[$page];
        $pages = $this->pages;

        return view('pages.docs.index', compact('page', 'title', 'pages'));
    }
}
