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

class UserDataTable
{
    private DataTableFactory $factory;

    private UrlGeneratorInterface $router;

    private UserManager $userManager;

    public function __construct(DataTableFactory $dataTableFactory, UrlGeneratorInterface $router, UserManager $userManager)
    {
        $this->factory = $dataTableFactory;
        $this->router = $router;
        $this->userManager = $userManager;
    }

    public function create(): DataTable
    {
        return $this->factory->create()
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
            ])
            ->add('action', TwigStringColumn::class, [
                'label' => 'Akce',
                'searchable' => false,
                'orderable' => false,
                'template' => 
                    '<a href="{% if row.isAdmin %} {{ row.demoteURL }} {% else %} {{ row.promoteURL }} {% endif %}" class="btn btn-secondary">{% if row.isAdmin %} Demote {% else %} Promote {% endif %}</a>' .
                    ' ' .
                    '<a href="{{ row.deleteURL }}" class="btn btn-danger" onclick="return confirm(\'U sure?\')">Delete</a>'
                ])
            ->createAdapter(DataTableAdapter::class, [
                'callback' => fn(DataTableState $state) => $this->parseTableData($state),
                'objectForCallback' => $this
            ]);
    }

    private function parseTableData(DataTableState $state): array
    {
        $tableData = [];
        foreach ($this->userManager->getUsers($state) as $user){
            $tableData[] = [
                'email' => $user->getEmail(),
                'nickname' => $user->getNickname(),
                'info' => $this->router->generate('user_info', ['id' => $user->getId()]),
                'isAdmin' => $user->haveRole('ROLE_ADMIN'),
                'demoteURL' => $this->router->generate('user_demote', ['id' => $user->getId()]),
                'promoteURL' => $this->router->generate('user_promote', ['id' => $user->getId()]),
                'deleteURL' => $this->router->generate('user_delete', ['id' => $user->getId()])
            ];
        }
        return $tableData;
    }
}
