<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Repository\AppUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ApiUserController extends AbstractController
{
    #[Route('/api/users', name: 'app_api_user', methods: ['GET'])]
    public function getAllMobiles(AppUserRepository $appUserRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $appUserRepository->findAll();

        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'show_users']);

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'app_api_detail_user', methods: ['GET'])]
    public function getDetailMobile(AppUser $appUser, SerializerInterface $serializer): JsonResponse
    {
            $jsonUser = $serializer->serialize($appUser, 'json', ['groups' => 'show_users']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }
}
