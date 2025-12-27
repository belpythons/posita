<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Display the main POS dashboard or redirect logic.
     */
    public function index(): Response
    {
        return Inertia::render('Pos/Index');
    }
}
