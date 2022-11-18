<?php

declare(strict_types=1);

namespace App\BL\Util;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Adapter\ResultSetInterface;
use Omines\DataTablesBundle\Adapter\ArrayResultSet;
use Omines\DataTablesBundle\DataTableState as State;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;


class DataTableAdapter implements AdapterInterface
{
    protected const MAX_FETCH = 5000;
    /** @var array */
    private $data = [];

    private \Closure $dataCallback;

    private object $objectForCallback;

    private PropertyAccessor $accessor;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $this->dataCallback = \Closure::fromCallable($options['callback']);
        $this->objectForCallback = $options['objectForCallback'];
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(State $state): ResultSetInterface
    {
        $dataState = new DataTableState(
            $state->getLength() ?? 0,
            $state->getStart(),
            (($state->getOrderBy()[0] ?? [])[0] ?? null)?->getName() ?? '',
            (($state->getOrderBy()[0] ?? [])[1] ?? null) ?? '',
            $state->getGlobalSearch()
        );

        $this->data = $this->dataCallback->call(
            $this->objectForCallback,
            $dataState
        );

        $map = [];
        foreach ($state->getDataTable()->getColumns() as $column) {
            unset($propertyPath);
            if (empty($propertyPath = $column->getPropertyPath()) && !empty($field = $column->getField() ?? $column->getName())) {
                $propertyPath = "[$field]";
            }
            if (null !== $propertyPath) {
                $map[$column->getName()] = $propertyPath;
            }
        }

        $data = iterator_to_array($this->processData($state, $this->data, $map));
        //$page = $length > 0 ? array_slice($data, $state->getStart(), $state->getLength()) : $data;

        return new ArrayResultSet($data, totalFilteredRows: $dataState->getCount());
    }

    /**
     * @return \Generator
     */
    protected function processData(State $state, array $data, array $map)
    {
        $transformer = $state->getDataTable()->getTransformer();
        foreach ($data as $result) {
            $row = $this->processRow($state, $result, $map);
            if (null !== $transformer) {
                $row = call_user_func($transformer, $row, $result);
            }
            yield $row;
        }
    }

    /**
     * @return array
     */
    protected function processRow(State $state, array $result, array $map)
    {
        $row = [];
        foreach ($state->getDataTable()->getColumns() as $column) {
            $value = (!empty($propertyPath = $map[$column->getName()]) && $this->accessor->isReadable($result, $propertyPath)) ? $this->accessor->getValue($result, $propertyPath) : null;
            $value = $column->transform($value, $result);
            $row[$column->getName()] = $value;
        }

        return $row;
    }
}
