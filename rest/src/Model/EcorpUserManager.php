<?php

namespace App\Model;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class EcorpUserManager
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManagerInterface;

    /**
     * EcorpUserManager constructor.
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManagerInterface
     */
    public function __construct(UserRepository $userRepository, EntityManagerInterface $entityManagerInterface)
    {
        $this->userRepository = $userRepository;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    public function createUser(User $user): User
    {
        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();

        return $user;
    }
}