<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Relations;

use Strux\Component\Database\ORM\Model;

class OwnsManyPoly extends OwnsMany
{
    protected string $typeColumn;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey, string $typeColumn)
    {
        $this->typeColumn = $typeColumn;
        parent::__construct($related, $parent, $foreignKey, $localKey);
        
        $this->getQuery()->where($this->typeColumn, get_class($parent));
    }
}
