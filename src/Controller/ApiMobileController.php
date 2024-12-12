<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiMobileController extends AbstractController
{
    #[Route('/api/mobiles', name: 'app_api_mobile')]
    public function getAllMobiles(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiMobileController.php',
        ]);
    }
}
