<?php

namespace App\Controller;

use App\Repository\MobileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiMobileController extends AbstractController
{
    #[Route('/api/mobiles', name: 'app_api_mobile', methods: ['GET'])]
    public function getAllMobiles(MobileRepository $mobileRepository, SerializerInterface $serializer): JsonResponse
    {
        $mobileList = $mobileRepository->findAll();

        $jsonMobileList = $serializer->serialize($mobileList, 'json');

        return new JsonResponse($jsonMobileList, Response::HTTP_OK, [], true);
    }
}
