<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Customer;
use App\Repository\AppUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ApiUserController extends AbstractController
{
    #[Route('/api/users', name: 'app_api_user', methods: ['GET'])]
    public function getAllUsers(AppUserRepository $appUserRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page',1);
        $limit = $request->get('limit',1);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($appUserRepository, $page, $limit, $serializer) {
            echo ("L'ELEMENT N'EST PAS ENCORE EN CACHE !\n");
            $item->tag("usersCache");
            $userList = $appUserRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($userList, 'json', ['groups' => 'show_users']);
        });      

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'app_api_detail_user', methods: ['GET'])]
    public function getDetailUser(AppUser $appUser, SerializerInterface $serializer): JsonResponse
    {
        $jsonUser = $serializer->serialize($appUser, 'json', ['groups' => 'show_users']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'app_api_delete_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un utilisateur')]
    public function deleteUser(AppUser $appUser, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["usersCache"]);
        $em->remove($appUser);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/users', name: 'app_api_create_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un utilisateur')]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse {
        // Décoder les données JSON pour récupérer l'ID du customer
        $data = json_decode($request->getContent(), true);

        // Désérialiser l'utilisateur à partir des données JSON
        $user = $serializer->deserialize($request->getContent(), AppUser::class, 'json');
        $user->setCreatedAt(new \DateTimeImmutable());

        // On vérifie les erreurs
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Charger l'entité Customer correspondante
        $customer = $em->getRepository(Customer::class)->find($data['customer']);
        $user->setCustomer($customer);

        // Sauvegarder l'utilisateur
        $em->persist($user);
        $em->flush();

        // Générer la réponse JSON
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'show_users']);

        // Générer l'URL de l'utilisateur créé
        $location = $urlGenerator->generate('app_api_detail_user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
