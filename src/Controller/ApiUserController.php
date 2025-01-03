<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Customer;
use App\Repository\AppUserRepository;
use App\Security\Voter\UserVoter;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ApiUserController extends AbstractController
{


    /**
   * Liste des utilisateurs d'un client enregistré
   * @OA\Response(
   *     response=Response::HTTP_OK,
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
    public function getAllUsers(AppUserRepository $appUser, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache, TokenStorageInterface $token): JsonResponse
    {
        $customer = $token->getToken()->getUser();

        if (!$customer) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à ces données.');
    }

        $page = $request->get('page',1);
        $limit = $request->get('limit',1);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($appUser, $customer, $page, $limit, $serializer){
            $item->tag("usersCache");
            $userList = $appUser->findAllWithPagination($customer, $page, $limit);
            $context = SerializationContext::create()->setGroups(["show_users"]);

            return $serializer->serialize($userList, 'json', $context);
        });      

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    /**
   * Affiche le détail d'un utilisateur
   * @OA\Response(
   *     response=Response::HTTP_OK,
   *     description="Renvoie l'utilisateur selon l'identifiant",
   *     @Model(type=User::class, groups={"show_users"})
   * )
   *
   * @OA\Response(
   *     response=Response::HTTP_UNAUTHORIZED,
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
   *   response=Response::HTTP_NOT_FOUND,
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
    public function getDetailUser(AppUser $appUser, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $this->denyAccessUnlessGranted('AppUserView',$appUser);

        $idCache = "getUser" . $appUser->getId();

        // Vérifier si les données sont déjà en cache.
        $jsonUser = $cache->get($idCache, function (ItemInterface $item) use ($appUser, $serializer) {
            // Spécifier que l'élément de cache doit être invalidé lorsque le cache "usersCache" est vidé
            $item->tag("usersCache");

            // Sérialiser l'utilisateur et le renvoyer.
            $context = SerializationContext::create()->setGroups(["show_users"]);
            return $serializer->serialize($appUser, 'json', $context);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
   * Supprimer un utilisateur pour un client enregistré
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
   */
    #[Route('/api/users/{id}', name: 'app_api_delete_user', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour créer un utilisateur')]
    public function deleteUser(AppUser $appUser, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('AppUserDelete', $appUser);

        $em->remove($appUser);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
   * Créer un nouvel utilisateur pour un client enregistré
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
   *     response=Response::HTTP_CREATED,
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
   *     response=Response::HTTP_UNAUTHORIZED,
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
   *     response=Response::HTTP_CONFLICT,
   *     description="Elément déjà existant",
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
   */
    #[Route('/api/users', name: 'app_api_create_user', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Vous n\'avez pas les droits suffisants pour créer un utilisateur')]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TokenStorageInterface $token
    ): JsonResponse {

        // Désérialiser l'utilisateur à partir des données JSON.
        $user = $serializer->deserialize($request->getContent(), AppUser::class, 'json');
        $user->setCreatedAt(new \DateTimeImmutable());

        // On vérifie les erreurs.
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        // Charger l'entité Customer correspondante.
        $customer = $token->getToken()->getUser();
        $user->setCustomer($customer);

        // Sauvegarder l'utilisateur.
        $em->persist($user);
        $em->flush();

        // Générer la réponse JSON.
        $context = SerializationContext::create()->setGroups(["show_users"]);
        $jsonUser = $serializer->serialize($user, 'json', $context);

        // Générer l'URL de l'utilisateur créé.
        $location = $urlGenerator->generate('app_api_detail_user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
