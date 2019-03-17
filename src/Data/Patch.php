<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches\Data;

class Patch
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $targetName;

    /**
     * @var \GetNoticed\ComposerPatches\Data\PatchCondition[]
     */
    private $conditions = [];

    /**
     * @var string
     */
    private $filePath;

    /**
     * Any numeric value greater than or equals 0, default is always 1 (default git-apply -p<n> value)
     *
     * @see https://git-scm.com/docs/git-apply (-p<n> option)
     *
     * @var int
     */
    private $precision = 1;

    public function __construct(
        string $name,
        string $description,
        string $targetName,
        array $conditions,
        string $filePath,
        int $precision = 1
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->targetName = $targetName;
        $this->conditions = $conditions;
        $this->filePath = $filePath;
        $this->precision = $precision;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getTargetName(): string
    {
        return $this->targetName;
    }

    /**
     * @return \GetNoticed\ComposerPatches\Data\PatchCondition[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return int
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }
}
