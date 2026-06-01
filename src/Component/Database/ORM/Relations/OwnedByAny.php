<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Relations;

use Strux\Component\Database\ORM\Model;

class OwnedByAny extends OwnedBy
{
    protected string $typeColumn;

    public function __construct(Model $related, Model $parent, string $foreignKey, string $ownerKey, string $typeColumn)
    {
        $this->typeColumn = $typeColumn;
        parent::__construct($related, $parent, $foreignKey, $ownerKey);
    }
    
    public function getResults(): ?Model
    {
        if (empty($this->parent->{$this->typeColumn}) || empty($this->parent->{$this->foreignKey})) {
            return null;
        }
        
        return parent::getResults();
    }
}
