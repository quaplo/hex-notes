<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return new RedirectResponse('/api/doc');
    }
}