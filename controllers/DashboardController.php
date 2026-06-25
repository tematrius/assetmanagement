<?php

declare(strict_types=1);

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $dashboard = new Dashboard();
        $isItStaff = Auth::isItStaff();
        $showPersonalArea = Auth::isManagerIt() && !Auth::isAdmin();
        $portal = new Portal();
        $this->view('dashboard/index', [
            'title' => $isItStaff ? 'Pilotage IT Asset' : 'Mon espace IT',
            'isItStaff' => $isItStaff,
            'stats' => $isItStaff ? $dashboard->stats() : $portal->summary(Auth::id()),
            'operations' => $isItStaff ? $dashboard->operations() : [],
            'myEquipment' => (!$isItStaff || $showPersonalArea) ? array_slice($portal->equipmentFor(Auth::id()), 0, 5) : [],
            'myRequests' => (!$isItStaff || $showPersonalArea) ? array_slice($portal->requestsFor(Auth::id()), 0, 5) : [],
            'personalStats' => $showPersonalArea ? $portal->summary(Auth::id()) : [],
            'showPersonalArea' => $showPersonalArea,
        ]);
    }
}
