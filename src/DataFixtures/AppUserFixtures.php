<?php

namespace App\DataFixtures;

use App\Entity\AppUser;
use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AppUserFixtures extends Fixture implements DependentFixtureInterface
{
  public const USER_REFERENCE = 'normal-user-';

  public function load(ObjectManager $manager): void
  {
    for ($i = 0; $i < 10; $i++) {
      $user = new AppUser();
      $user->setFirstname("userfirstname" . $i);
      $user->setLastname("Itineris" . $i);
      $user->setEmail("test-" . $i . "@mail.com");
      $user->setCreatedAt(new \DateTimeImmutable());
      $user->setCustomer($this->getReference(CustomerFixtures::CUSTOMER_REFERENCE . '1', Customer::class));
      $manager->persist($user);
      $this->addReference(self::USER_REFERENCE . $i, $user);
    }

    for ($i = 0; $i < 5; $i++) {
      $user = new AppUser();
      $user->setFirstname("username" . $i);
      $user->setLastname("Numerobis" . $i);
      $user->setEmail("test2-" . $i . "@mail.com");
      $user->setCreatedAt(new \DateTimeImmutable());
      $user->setCustomer($this->getReference(CustomerFixtures::CUSTOMER_REFERENCE . '2', Customer::class));
      $manager->persist($user);
      $this->setReference(self::USER_REFERENCE . $i, $user);
    }

    $manager->flush();
  }

  public function getDependencies(): array
  {
    return [
      CustomerFixtures::class,
    ];
  }
}
