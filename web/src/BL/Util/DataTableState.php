<?php

namespace App\BL\Util;

class DataTableState
{
    private int $limit;

    private int $start;

    private int $count;

    private string $orderColumn;

    private string $orderType;

    private string $globalSearch;

    public function __construct(int $limit, int $start, string $orderColumn, string $orderType, string $globalSearch)
    {
        $this->limit = $limit;
        $this->start = $start;
        $this->orderColumn = $orderColumn;
        $this->orderType = $orderType;
        $this->globalSearch = $globalSearch;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getOrderColumn(): string
    {
        return $this->orderColumn;
    }

    public function isAsceding(): bool
    {
        return strtolower($this->orderType) === "asc";
    }

    public function getSearch(): string
    {
        return StringUtil::shave($this->globalSearch);
    }

    public function setCount(int $count)
    {
        $this->count = $count;
    }

    public function getCount(): ?int
    {
        return $this->count ?? null;
    }
}
