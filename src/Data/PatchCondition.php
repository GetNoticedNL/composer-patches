<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches\Data;

class PatchCondition
{
    /**
     * @var string
     */
    private $targetName;

    /**
     * @var \GetNoticed\ComposerPatches\Data\PatchConstraint
     */
    private $constraint;

    /**
     * @var bool
     */
    private $optional = false;

    public function __construct(string $targetName, PatchConstraint $constraint, bool $optional = false)
    {
        $this->targetName = $targetName;
        $this->constraint = $constraint;
        $this->optional = $optional;
    }

    /**
     * @return string
     */
    public function getTargetName(): string
    {
        return $this->targetName;
    }

    /**
     * @return \GetNoticed\ComposerPatches\Data\PatchConstraint
     */
    public function getConstraint(): PatchConstraint
    {
        return $this->constraint;
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }
}
