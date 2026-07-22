<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    public function __invoke()
    {
        $date = request()->input('date');

        return Inertia::render(
            component: 'Dashboard',
            props: $this->service->getForUser(request()->user(), $date)
        );
    }
}
