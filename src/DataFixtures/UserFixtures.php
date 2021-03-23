<?php

namespace App\DataFixtures;

use App\Entity\Apprentice;
use App\Entity\Expert;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        //Admin
        $user = new User();
        $user->setUsername('alexma');
        $password = $this->encoder->encodePassword($user, 'alexma');
        $user->setPassword($password);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setName('Alex');
        $user->setLastname('Martínez');
        $user->setEmail('al@gmail.com');
        $user->setAddress("C/ Hola");
        $user->setPhone("666666666");
        $manager->persist($user);

        //Exeperto
        $user2 = new User();
        $user2->setUsername('carlosmo');
        $password2 = $this->encoder->encodePassword($user2, 'carlosmo');
        $user2->setPassword($password2);
        $user2->setRoles(['ROLE_USER']);
        $user2->setName('Carlos');
        $user2->setLastname('Mora');
        $user2->setEmail('ca@gmail.com');
        $user2->setAddress("C/ Que tal");
        $user2->setPhone("777777777");
        $manager->persist($user2);
        $expert = new Expert();
        $expert->setUserdata($user2);
        $expert->setUsername($user2->getUsername());
        $manager->persist($expert);
        //Aprendiz
        $user3 = new User();
        $user3->setUsername('jaumeba');
        $password3 = $this->encoder->encodePassword($user3, 'jaumeba');
        $user3->setPassword($password3);
        $user3->setRoles(['ROLE_USER']);
        $user3->setName('Jaume');
        $user3->setLastname('Barrios');
        $user3->setEmail('ba@gmail.com');
        $user3->setAddress("C/ Genial");
        $user3->setPhone("555555555");
        $manager->persist($user3);
        $apprentice = new Apprentice();
        $apprentice->setUserdata($user3);
        $apprentice->setUsername($user3->getUsername());
        $manager->persist($apprentice);

        $manager->flush();
    }
}
