<?php

namespace App\DataFixtures;

use App\Entity\Mobile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MobileFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
      $mobilesNamebrand = [
        0 => 'Samsouche',
        1 => 'Pomme',
        2 => 'Guignool',
        3 => 'Ericsonne',
        4 => 'Ouahouais',
        5 => 'Bluesberry'
        ];

        for ($i = 0; $i <= 5; $i++) {
          $telephone = new Mobile();
          $telephone->setBrandname($mobilesNamebrand[$i]);
          $telephone->setModel("modèle de type" .$i);
          $telephone->setDescription("Je suis un mobile avec une description" .$i);
          $telephone->setPrice(rand(300,850));
          $telephone->setCreatedAt(new \DateTimeImmutable());
          $telephone->setPicture("photo du produit" .$i);
          $telephone->setStock($i);

          $manager->persist($telephone);
        }
        $manager->flush();
    }
}