<?php

declare(strict_types=1);

namespace App\Support\Tenant;

final class TableColumn
{
    private ?string $name = null;

    private bool $orderableFlag = true;

    private bool $searchableFlag = false;

    private ?string $classNameValue = null;

    private ?string $widthValue = null;

    private ?int $priorityValue = null;

    private bool $visibleFlag = true;

    private function __construct(
        private readonly string $data,
        private readonly string $title,
    ) {
    }

    public static function make(string $data, string $title): self
    {
        return new self($data, $title);
    }

    public static function index(string $title = '#'): self
    {
        return self::make('DT_RowIndex', $title)->orderable(false)->searchable(false);
    }

    public static function actions(string $title = 'Actions'): self
    {
        return self::make('actions', $title)
            ->orderable(false)
            ->searchable(false)
            ->priority(1)
            ->className('t-actions');
    }

    public static function select(string $title = ''): self
    {
        return self::make('select', $title)
            ->orderable(false)
            ->searchable(false)
            ->className('t-select-col')
            ->width('34px');
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function orderable(bool $orderable = true): self
    {
        $this->orderableFlag = $orderable;

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchableFlag = $searchable;

        return $this;
    }

    public function className(string $className): self
    {
        $this->classNameValue = $className;

        return $this;
    }

    public function width(string $width): self
    {
        $this->widthValue = $width;

        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priorityValue = $priority;

        return $this;
    }

    public function visible(bool $visible = true): self
    {
        $this->visibleFlag = $visible;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'data' => $this->data,
            'name' => $this->name,
            'title' => $this->title,
            'orderable' => $this->orderableFlag,
            'searchable' => $this->searchableFlag,
            'className' => $this->classNameValue,
            'width' => $this->widthValue,
            'responsivePriority' => $this->priorityValue,
            'visible' => $this->visibleFlag,
        ], fn ($value) => $value !== null);
    }
}
