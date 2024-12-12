<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerFixtures extends Fixture
{
  public const CUSTOMER_REFERENCE = 'customer-';
  private UserPasswordHasherInterface $userPasswordHasher;

  public function __construct(UserPasswordHasherInterface $userPasswordHasher)
  {
    $this->userPasswordHasher = $userPasswordHasher;
  }

  public function load(ObjectManager $manager): void
  {
      //admin user
      $customer = new Customer();
      $customer->setUsername("Company");
      $customer->setEmail("company@mail.com");
      $customer->setRoles(["ROLE_ADMIN"]);
      $customer->setPassword($this->userPasswordHasher->hashPassword($customer, "123456"));
      $manager->persist($customer);

      //admin user
      $customerAdmin = new Customer();
      $customerAdmin->setUsername("Admin");
      $customerAdmin->setEmail("admin@mail.com");
      $customerAdmin->setRoles(["ROLE_ADMIN"]);
      $customerAdmin->setPassword($this->userPasswordHasher->hashPassword($customerAdmin,"123456"));
      $manager->persist($customerAdmin);
      
      $this->addReference(self::CUSTOMER_REFERENCE.'1', $customer);
      $this->addReference(self::CUSTOMER_REFERENCE.'2', $customerAdmin);
      $manager->flush();

  }
}