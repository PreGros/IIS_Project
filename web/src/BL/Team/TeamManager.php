<?php

namespace App\BL\Team;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

use App\DAL\Entity\Team;

class UserManager
{
    private EntityManagerInterface $entityManager;

    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        Security $security
    )
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function createTeam(){
        
    }
    
}
