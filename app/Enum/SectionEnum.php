<?php

namespace App\Enum;

class SectionEnum
{
    public const GettingStarted = 1;
    public const Applications = 2;
    public const Billing = 3;
    public const Cart = 4;

    public static function getList(): array
    {
        return [
            self::GettingStarted => 'Getting Started',
            self::Applications   => 'Applications',
            self::Billing        => 'Billing',
            self::Cart           => 'Cart',
        ];
    }

    public static function getSteps(int $sectionId): array
    {
        $steps = [
            self::Applications => [
                [
                    'step_number' => 1,
                    'title' => 'Step',
                    'description' => 'Go to Applications from the Control Panel, and open the Applications section.',
                ],
                [
                    'step_number' => 2,
                    'title' => 'Step',
                    'description' => 'Click on "Create New Application" button in the top right corner.',
                ],
                [
                    'step_number' => 3,
                    'title' => 'Step',
                    'description' => 'Fill in the required fields including application name and description.',
                ],
                [
                    'step_number' => 4,
                    'title' => 'Step',
                    'description' => 'Click "Submit" to create your new application.',
                ],
            ],
            // Add other steps for other sections here...
        ];

        return $steps[$sectionId] ?? [];
    }

    public static function isValid(int $value): bool
    {
        return array_key_exists($value, self::getList());
    }

    public static function getLabel(int $value): ?string
    {
        return self::getList()[$value] ?? null;
    }
}