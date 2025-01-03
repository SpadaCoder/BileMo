<?php

namespace App\Controller;

use App\Entity\Mobile;
use App\Repository\MobileRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ApiMobileController extends AbstractController
{
    /**
   * Obtenir la liste de mobiles
   *
   * @OA\Response(
   *     response=Response::HTTP_OK,
   *     description="Renvoie tous les mobiles.",
   *     @OA\JsonContent(
   *        type="array",
   *        @OA\Items(ref=@Model(type=Mobile::class))
   *     )
   * )
   * @OA\Parameter(
   *     name="page",
   *     example="1",
   *     in="query",
   *     description="Sélectionner la page",
   *     @OA\Schema(type="int")
   * )
   * @OA\Parameter(
   *     name="limit",
   *     example="2",
   *     in="query",
   *     description="Nombre maximum de mobile à récupérer",
   *     @OA\Schema(type="int")
   * )
   * @OA\Tag(name="Mobile")
   * @Security(name="Bearer")
   */
    #[Route('/api/mobiles', name: 'app_api_mobile', methods: ['GET'])]
    public function getAllMobiles(MobileRepository $mobileRepository, SerializerInterface $serializer): JsonResponse
    {
        $mobileList = $mobileRepository->findAll();

        $jsonMobileList = $serializer->serialize($mobileList, 'json');

        return new JsonResponse($jsonMobileList, Response::HTTP_OK, [], true);
        
    }

    /**
   * Obtenir les détails d'un mobile
   *
   * @OA\Response(
   *     response=Response::HTTP_OK,
   *     description="Renvoie le mobile selon l'identifiant",
   *     @Model(type=Mobile::class)
   * )
   * @OA\Response (
   *   response=Response::HTTP_NOT_FOUND,
   *   description="Aucun produit trouvé pour cet identifiant",
   *     @OA\JsonContent(
   *        @OA\Property(
   *         property="error",
   *         type="string",
   *         example="Ce produit n'existe pas"
   *        )
   *     )
   * )
   *
   * @Security(name="Bearer")
   * @OA\Tag(name="Mobile")
   *
   */
    #[Route('/api/mobiles/{id}', name: 'app_api_detail_mobile', methods: ['GET'])]
    public function getDetailMobile(Mobile $mobile, SerializerInterface $serializer): JsonResponse
    {
            $jsonMobile = $serializer->serialize($mobile, 'json');

            return new JsonResponse($jsonMobile, Response::HTTP_OK, [], true);
    }
}
