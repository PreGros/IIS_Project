<?php

namespace App\PL\DataTable\User;

use App\BL\User\UserManager;
use App\BL\Util\DataTableAdapter;
use App\BL\Util\DataTableState;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

class UserDataTable
{
    private DataTableFactory $factory;

    private UrlGeneratorInterface $router;

    private Security $security;

    private UserManager $userManager;

    private bool $allModifiable;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, UserManager $userManager, Security $security)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->userManager = $userManager;
        $this->security = $security;
    }

    public function create(bool $allModifiable = false): DataTable
    {
        $this->allModifiable = $allModifiable;

        $dataTable = $this->factory->create()
            ->add('nickname', TwigStringColumn::class, [
                'label' => 'NickName',
                'searchable' => true,
                'orderable' => true,
                'template' => '<a href="{{ row.info }}">{{ row.nickname }}</a>'
            ])
            ->add('email', TextColumn::class, [
                'label' => 'Email',
                'searchable' => true,
                'orderable' => true
            ]);
        
        $dataTable->add('action', TwigStringColumn::class, [
            'label' => 'Action',
            'searchable' => false,
            'orderable' => false,
            'template' => 
                '{% if row.canEdit %} <a href="{{ row.editUser }}" class="btn btn-primary">Edit</a>' .
                ' {% endif %}' .
                '{% if row.canModerate %}<a href="{% if row.isAdmin %} {{ row.demoteURL }} {% else %} {{ row.promoteURL }} {% endif %}" class="btn btn-secondary">{% if row.isAdmin %} Demote {% else %} Promote {% endif %}</a>{% endif %}' .
                ' ' .
                '{% if row.canEdit %} <a href="{{ row.deactivateURL }}" class="btn btn-danger" onclick="return confirm(\'Are you sure?\')">Deactivate</a>{% endif %}'
        ]);
            
        return $dataTable->createAdapter(DataTableAdapter::class, [
                'callback' => fn(DataTableState $state) => $this->parseTableData($state),
                'objectForCallback' => $this
            ]);
    }

    private function parseTableData(DataTableState $state): array
    {
        $tableData = [];
        /** @var \App\BL\User\UserModel */
        $currUser = $this->security->getUser();
        foreach ($this->userManager->getUsers($state) as $user){
            $tableData[] = [
                'email' => $user->getEmail(),
                'nickname' => $user->getNickname(),
                'info' => $this->router->generate('user_info', ['id' => $user->getId()]),
                'isAdmin' => $user->haveRole('ROLE_ADMIN'),
                'demoteURL' => $this->router->generate('user_demote', ['id' => $user->getId()]),
                'promoteURL' => $this->router->generate('user_promote', ['id' => $user->getId()]),
                'deactivateURL' => $this->router->generate('user_deactivate', ['id' => $user->getId()]),
                'editUser' => $this->router->generate('user_edit', ['id' => $user->getId()]),
                'canEdit' => $user->isCurrentUser($currUser?->getId()) || $this->allModifiable,
                'canModerate' => $this->allModifiable
            ];
        }
        return $tableData;
    }
}
