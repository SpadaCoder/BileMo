<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Customer;
use App\Repository\AppUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;


class ApiUserController extends AbstractController
{
    /**
   * Liste des utilisateurs d'un client enregistré
   * @Route("/api/users", name="app_api_user", methods={"GET"})
   * @OA\Response(
   *     response=200,
   *     description="Renvoie la liste des utilisateurs d'un client enregistré",
   *     @OA\JsonContent(
   *        type="array",
   *        @OA\Items(ref=@Model(type=User::class, groups={"show_users"}))
   *     )
   * )
   * )
   * @OA\Response(
   *     response=401,
   *     description="Jeton JWT non autorisé et expiré",
   *     @OA\JsonContent(
   *        @OA\Property(
   *         property="code",
   *         type="integer",
   *         example="401"
   *        ),
   *        @OA\Property(
   *         property="message",
   *         type="string",
   *         example="Jeton JWT expiré"
   *        ),
   *     )
   * )
   * @OA\Parameter(
   *     name="page",
   *     example="1",
   *     in="query",
   *     description="Page sélectionnée",
   *     @OA\Schema(type="int")
   * )
   * @OA\Parameter(
   *     name="limit",
   *     example="2",
   *     in="query",
   *     description="Nombre max d'élément à récupérer souhaité",
   *     @OA\Schema(type="int")
   * )
   *
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   */
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
            $context = SerializationContext::create()->setGroups(["show_users"]);
            return $serializer->serialize($userList, 'json', $context);
        });      

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    /**
   * Affiche le détail d'un utilisateur
   * @Route("/api/user/{id}", name="app_api_detail_user", methods={"GET"})
   * @OA\Response(
   *     response=Response::HTTP_OK,
   *     description="Renvoie l'utilisateur selon l'identifiant",
   *     @Model(type=User::class, groups={"show_users"})
   * )
   *
   * @OA\Response(
   *     response=401,
   *     description="Jeton JWT non autorisé et expiré",
   *     @OA\JsonContent(
   *        @OA\Property(
   *         property="code",
   *         type="integer",
   *         example="401"
   *        ),
   *        @OA\Property(
   *         property="message",
   *         type="string",
   *         example="Jeton JWT expiré"
   *        ),
   *     )
   * )
   * @OA\Response (
   *   response=404,
   *   description="Aucun utilisateur trouvé pour cet identifiant",
   *     @OA\JsonContent(
   *        @OA\Property(
   *         property="error",
   *         type="string",
   *         example="Cet utilisateur n'existe pas"
   *        )
   *     )
   * )
   * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   */
    #[Route('/api/users/{id}', name: 'app_api_detail_user', methods: ['GET'])]
    public function getDetailUser(AppUser $appUser, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(["show_users"]);
        $jsonUser = $serializer->serialize($appUser, 'json', $context);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
   * Supprimer un utilisateur pour un client enregistré
   * @Route("/api/user/{id}", name="app_api_delete_user", methods={"DELETE"})
   *
   * @OA\Response(
   *     response=Response::HTTP_NO_CONTENT,
   *     description="Aucun contenu"
   * )
   ** @OA\Response(
   *     response=Response::HTTP_UNAUTHORIZED,
   *     description="Non autorisé"
   * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   * @IsGranted("ROLE_ADMIN")
   */
    #[Route('/api/users/{id}', name: 'app_api_delete_user', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un utilisateur')]
    public function deleteUser(AppUser $appUser, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["usersCache"]);
        $em->remove($appUser);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
   * Créer un nouvel utilisateur pour un client enregistré
   * @Route("/api/user", name="app_api_create_user", methods={"POST"})
   *
   * @OA\RequestBody (
   *      required=true,
   *      @OA\MediaType(
   *        mediaType="application/json",
   *        @OA\Schema (
   *          @OA\Property(
   *            property="firstname",
   *            description="le prénom du nouvel utilisateur",
   *            type="string",
   *            example="Sam"
   *          ),
   *          @OA\Property(
   *            property="lastname",
   *            description="le nom du nouvel utilisateur",
   *            type="string",
   *            example="Sung"
   *          ),
   *          @OA\Property(
   *            property="email",
   *            description="email du nouvel utilisateur",
   *            type="email",
   *            example="sam.sung@galaxymail.com"
   *          )
   *        )
   *      )
   *   )
   *
   *
   * @OA\Response(
   *     response=201,
   *     description="Créer un nouvel utilisateur",
   *     @OA\JsonContent(
   *        @OA\Property(
   *          property="id",
   *          type="integer",
   *          example="43"
   *          ),
   *        @OA\Property(
   *          property="firstname",
   *          type="string",
   *          example="Sam"
   *          ),
   *          @OA\Property(
   *          property="lastname",
   *          type="string",
   *          example="Sung"
   *          ),
   *          @OA\Property(
   *          property="email",
   *          type="string",
   *          example="sam.sung@galaxymail.com"
   *          )
   *     )
   * )
   * @OA\Response(
   *     response=401,
   *     description="Jeton JWT non autorisé et expiré",
   *     @OA\JsonContent(
   *        @OA\Property(
   *         property="code",
   *         type="integer",
   *         example="401"
   *        ),
   *        @OA\Property(
   *         property="message",
   *         type="string",
   *         example="Jeton JWT expiré"
   *        ),
   *     )
   * )
   * @OA\Response(
   *     response=409,
   *     description="Entity already exist",
   *     @OA\JsonContent(
   *        @OA\Property(
   *         property="errors",
   *         type="array",
   *         @OA\Items(
   *          type="string",
   *          example="Email déjà utilisé"
   *          )
   *        )
   *     )
   * )
   * @OA\Tag(name="Users")
   * @Security(name="Bearer")
   * @IsGranted("ROLE_ADMIN")
   */
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
        $context = SerializationContext::create()->setGroups(["show_users"]);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        // Générer l'URL de l'utilisateur créé
        $location = $urlGenerator->generate('app_api_detail_user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
