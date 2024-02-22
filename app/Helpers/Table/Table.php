<?php

namespace App\Helpers\Table;

use App\Enums\SortOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class Table
{
    public Builder $builder;
    public array $columns;
    public array $searchCases;
    public bool $paginate;
    public bool $columnTextSearch;
    public int $itemsPerPage;
    public int $paginationIndex;
    public int $count;
    public string $sortProperty;
    public SortOrder $sortOrder;
    public Collection $collection;
    public Collection $paginatedCollection;

    protected function __construct(){}

    static function make(Builder $builder): TableBuilder
    {
        $static = new static();
        $static->searchCases = [];
        $static->paginate = true;
        $static->columnTextSearch = true;
        $static->paginationIndex = 1;
        $static->builder = $builder;
        $static->sortOrder = SortOrder::ASCENDING;
        return new TableBuilder($static);
    }

    function hasPreviousPage(): bool
    {
        return $this->paginationIndex > 1;
    }

    function hasNextPage(): int
    {
        return $this->paginationIndex < ($this->count - $this->itemsPerPage);
    }

    function to(): int
    {
        return $this->paginationIndex + $this->itemsPerPage;
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
        foreach ($this->paginatedCollection as $model){
            $row = [];
            foreach ($this->columns as $column){
                $row[] = [
                    'value' => $this->getValue($model, $column['property']),
                    'anchor' => $this->getAnchor($model, $column)
                ];
            }
            $rows[] = $row;
        }
        return $rows;
    }

    protected function getValue(Model $model, $property): string|null
    {
        return array_reduce(explode('.', $property),
            function ($o, $p) {
                return is_numeric($p) ? ($o[$p] ?? null) : ($o->$p ?? null);
            }, $model
        );
    }

    protected function getAnchor(Model $model, array $column): string|null
    {
        if(isset($column['route'])){
            $arguments = [];
            foreach ($column['routeArguments'] as $argument){
                $arguments[] = $this->getValue($model, $argument);
            }
            return route($column['route'], $arguments);
        }
        return null;
    }

    protected function paginateCollection(): Collection
    {
        return $this->collection->skip($this->paginationIndex - 1)->take($this->itemsPerPage);
    }

    protected function data_get($target, string $property): string
    {
        $data = data_get($target, $property);
        if($data instanceof UnitEnum){
            $data = $data->value;
        }
        return $data;
    }

    function create(): self
    {
        $this->collection = $this->builder->get();
        foreach ($this->searchCases as $property => $value){
            $this->collection = $this->collection->filter(function ($model) use ($property, $value){
                return str_contains($this->data_get($model, $property), $value);
            });
        }
        $this->collection = $this->sortOrder == SortOrder::DESCENDING ? $this->collection->sortByDesc($this->sortProperty) : $this->collection->sortBy($this->sortProperty);
        $this->paginatedCollection = $this->paginateCollection();
        $this->count = count($this->collection);
        return $this;
    }
}
