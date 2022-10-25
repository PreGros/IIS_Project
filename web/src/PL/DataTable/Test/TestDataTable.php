<?php

namespace App\PL\DataTable\Test;

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;

class TestDataTable
{
    private DataTableFactory $factory;

    public function __construct(DataTableFactory $dataTableFactory)
    {
        $this->factory = $dataTableFactory;
    }

    public function create(): DataTable
    {
        return $this->factory->create()
            ->add('firstName', TextColumn::class, ['label' => 'Jméno', 'searchable' => true, 'orderable' => true])
            ->add('lastName', TextColumn::class, ['label' => 'Příjmení', 'searchable' => true, 'orderable' => true])
            ->createAdapter(ArrayAdapter::class, [
                ['firstName' => 'Test', 'lastName' => 'Data'],
                ['firstName' => 'TestData', 'lastName' => 'DataTest'],
                ['firstName' => 'Data', 'lastName' => 'Test']
            ]);
    }
}
