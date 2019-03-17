<?php declare(strict_types=1);

namespace GetNoticed\ComposerPatches\Converters;

use GetNoticed\ComposerPatches\ {
    Exception\ConsoleStyledException,
    Utils\ArrayUtils,
    Data\Patch,
    Data\PatchCondition,
    Data\PatchConstraint
};

class PatchConverter
{
    /**
     * @param array $patchesData
     *
     * @return \GetNoticed\ComposerPatches\Data\Patch[]
     */
    public static function convertPatches(array $patchesData): array
    {
        $patchIndex = 0;

        return array_map(
            function (array $patchData) use (&$patchIndex) {
                $name = (string)ArrayUtils::get($patchData, 'name');
                $description = (string)ArrayUtils::get($patchData, 'description');
                $target = (string)ArrayUtils::get($patchData, 'target');
                $conditions = ArrayUtils::get($patchData, 'conditions', []);
                $filePath = (string)ArrayUtils::get($patchData, 'filepath');
                $precision = (int)ArrayUtils::get($patchData, 'precision', 1);

                if (empty($name)) {
                    throw new ConsoleStyledException(
                        sprintf('<error>Error in patch #%d: %s</error>', $patchIndex, 'Name is required.')
                    );
                }

                if (empty($description)) {
                    throw new ConsoleStyledException(
                        sprintf('<error>Error in patch #%d: %s</error>', $patchIndex, 'Description is required.')
                    );
                }

                if (empty($target)) {
                    throw new ConsoleStyledException(
                        sprintf('<error>Error in patch #%d: %s</error>', $patchIndex, 'Target is required.')
                    );
                }

                if (is_array($conditions) !== true) {
                    if (empty($conditions)) {
                        $conditions = [];
                    } else {
                        throw new ConsoleStyledException(
                            sprintf(
                                '<error>Error in patch #%d: %s</error>',
                                $patchIndex,
                                'Conditions syntax invalid, please check README.'
                            )
                        );
                    }
                }

                if (empty($filePath)) {
                    throw new ConsoleStyledException(
                        sprintf('<error>Error in patch #%d: %s</error>', $patchIndex, 'Patch file is required.')
                    );
                }

                if (file_exists(realpath($filePath)) !== true) {
                    throw new ConsoleStyledException(
                        sprintf('<error>Error in patch #%d: %s</error>', $patchIndex, 'Patch not found: ' . $filePath)
                    );
                }

                if ($precision < 0) {
                    $precision = 0;
                }

                $patchConditions = [];

                foreach ($conditions as $conditionIndex => $condition) {
                    $conditionTarget = (string)ArrayUtils::get($condition, 'target');
                    $constraint = (string)ArrayUtils::get($condition, 'constraint');
                    $optional = (bool)ArrayUtils::get($condition, 'optional', false);

                    if (empty($conditionTarget)) {
                        throw new ConsoleStyledException(
                            sprintf(
                                '<error>Error in patch #%d, condition #%d: %s</error>',
                                $patchIndex,
                                $conditionIndex,
                                'Target is required'
                            )
                        );
                    }

                    if (empty($constraint)) {
                        throw new ConsoleStyledException(
                            sprintf(
                                '<error>Error in patch #%d, condition #%d: %s',
                                $patchIndex,
                                $conditionIndex,
                                'Constraint is required'
                            )
                        );
                    }

                    $patchConditions[] = new PatchCondition(
                        $conditionTarget,
                        new PatchConstraint($constraint),
                        $optional
                    );
                }

                $patchIndex++;

                return new Patch($name, $description, $target, $patchConditions, $filePath, $precision);
            },
            $patchesData
        );
    }
}
