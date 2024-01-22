<?php

namespace App\Helpers\Fields;

use App\Enums\FieldPosition;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

class Fields implements IteratorAggregate
{
    public array $fields;

    function __construct(Field|callable ...$objects)
    {
        foreach ($objects as $object){
            if($object instanceof Field){
                $this->fields[] = $object;
            } elseif(call_user_func($object) instanceof Field) {
                $this->fields[] = call_user_func($object);
            } elseif(call_user_func($object) instanceof Fields){
                foreach (call_user_func($object) as $field){
                    $this->fields[] = $field;
                }
            }
        }
    }

    function insideGrid(): self
    {
        foreach($this->fields as $field){
            if($field->position !== FieldPosition::INSIDE_GRID){
                unset($this->fields[array_search($field, $this->fields)]);
            }
        }
        return $this;
    }

    function outsideGrid(): self
    {
        foreach($this->fields as $field){
            if($field->position !== FieldPosition::OUTSIDE_GRID){
                unset($this->fields[array_search($field, $this->fields)]);
            }
        }
        return $this;
    }

    function getIterator(): Traversable
    {
        return new ArrayIterator($this->fields);
    }
}
