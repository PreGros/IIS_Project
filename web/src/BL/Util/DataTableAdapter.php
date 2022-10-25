<?php

declare(strict_types=1);

namespace App\BL\Util;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Adapter\ResultSetInterface;
use Omines\DataTablesBundle\Adapter\ArrayResultSet;
use Omines\DataTablesBundle\DataTableState;
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
    public function getData(DataTableState $state): ResultSetInterface
    {
        $length = $state->getLength() ?? 0;
        $this->data = $this->dataCallback->call($this->objectForCallback, $length > 0 ? $length : self::MAX_FETCH);
        // very basic implementation of sorting
        try {
            $oc = $state->getOrderBy()[0][0]->getName();
            $oo = \mb_strtolower($state->getOrderBy()[0][1]);

            \usort($this->data, function ($a, $b) use ($oc, $oo) {
                if ('desc' === $oo) {
                    return $b[$oc] <=> $a[$oc];
                }

                return $a[$oc] <=> $b[$oc];
            });
        } catch (\Throwable $exception) {
            // ignore exception
        }

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
        $page = $length > 0 ? array_slice($data, $state->getStart(), $state->getLength()) : $data;

        return new ArrayResultSet($page, count($this->data), count($data));
    }

    /**
     * @return \Generator
     */
    protected function processData(DataTableState $state, array $data, array $map)
    {
        $transformer = $state->getDataTable()->getTransformer();
        $search = $state->getGlobalSearch() ?: '';
        foreach ($data as $result) {
            if ($row = $this->processRow($state, $result, $map, $search)) {
                if (null !== $transformer) {
                    $row = call_user_func($transformer, $row, $result);
                }
                yield $row;
            }
        }
    }

    /**
     * @return array|null
     */
    protected function processRow(DataTableState $state, array $result, array $map, string $search)
    {
        $row = [];
        $match = empty($search);
        foreach ($state->getDataTable()->getColumns() as $column) {
            $value = (!empty($propertyPath = $map[$column->getName()]) && $this->accessor->isReadable($result, $propertyPath)) ? $this->accessor->getValue($result, $propertyPath) : null;
            $value = $column->transform($value, $result);
            if (!$match) {
                $match = (false !== mb_stripos($value, $search));
            }
            $row[$column->getName()] = $value;
        }

        return $match ? $row : null;
    }
}
