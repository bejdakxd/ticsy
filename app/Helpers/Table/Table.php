<?php

namespace App\Helpers\Table;

use App\Enums\SortOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class Table
{
    const DEFAULT_ITEMS_PER_PAGE = 25;

    public Builder $builder;
    public array $columns;
    public array $searchCases;
    public bool $paginate;
    public bool $columnSearch;
    public int $itemsPerPage;
    public int $paginationIndex;
    public int $modelCount;
    public string $sortByProperty;
    public SortOrder $sortOrder;
    public Collection $models;
    public Collection $paginatedModels;

    protected function __construct(){}

    static function make(Builder $builder): TableBuilder
    {
        $static = new static();
        $static->searchCases = [];
        $static->paginate = true;
        $static->columnSearch = true;
        $static->itemsPerPage = $static::DEFAULT_ITEMS_PER_PAGE;
        $static->paginationIndex = 1;
        $static->builder = $builder;
        $static->sortOrder = SortOrder::ASCENDING;
        return new TableBuilder($static);
    }

    function isPreviousPage(): bool
    {
        return $this->paginationIndex > 1;
    }

    function isNextPage(): int
    {
        return $this->paginationIndex < ($this->modelCount - $this->itemsPerPage);
    }

    function getHeaders(): array
    {
        $headers = [];
        foreach (array_keys($this->columns) as $header){
            $headers[] = [
                'header' => $header,
                'property' => $this->columns[$header]['property'],
            ];
        }
        return $headers;
    }

    function getRows(): array
    {
        $rows = [];
        foreach ($this->paginatedModels as $model){
            $row = [];
            foreach ($this->columns as $column){
                $row[] = [
                    'value' => $this->getValue($model, $column['property']),
                    'anchor' => $column['route'] ? route($column['route'][0], $this->getValue($model, $column['route'][1])) : null,
                ];
            }
            $rows[] = $row;
        }
        return $rows;
    }

    function create(): self
    {
        $this->models = $this->builder->get();
        foreach ($this->searchCases as $property => $value){
            $this->models = $this->models->filter(function ($model) use ($property, $value){
                return str_contains($this->data_get($model, $property), $value);
            });
        }
        $this->models = $this->sortOrder == SortOrder::DESCENDING ? $this->models->sortByDesc($this->sortByProperty) : $this->models->sortBy($this->sortByProperty);
        $this->paginatedModels = $this->getPaginated();
        $this->modelCount = count($this->models);
        return $this;
    }

    function addSearchCase(string $property, string $value): self
    {
        $this->searchCases[$property] = $value;
        return $this;
    }

    function to(): int
    {
        return $this->paginationIndex + $this->itemsPerPage;
    }

    protected function getValue($model, $property): string|null
    {
        return array_reduce(explode('.', $property),
            function ($o, $p) {
                return is_numeric($p) ? ($o[$p] ?? null) : ($o->$p ?? null);
            }, $model
        );
    }

    protected function getPaginated(): Collection
    {
        return $this->models->skip($this->paginationIndex - 1)->take($this->itemsPerPage);
    }

    protected function data_get($target, string $property): string
    {
        $data = data_get($target, $property);
        if($data instanceof UnitEnum){
            $data = $data->value;
        }
        return $data;
    }
}
