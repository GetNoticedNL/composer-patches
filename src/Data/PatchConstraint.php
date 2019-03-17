<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches\Data;

class PatchConstraint
{
    /**
     * @var string
     */
    private $constraint;
    
    public function __construct(string $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * @return string
     */
    public function getConstraint(): string
    {
        return $this->constraint;
    }
}
