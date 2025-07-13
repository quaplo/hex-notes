<?php

declare(strict_types=1);

use App\Project\Domain\ValueObject\ProjectName;

describe('ProjectName Value Object', function (): void {

    test('project name can be created with valid string', function (): void {
        $name = new ProjectName('Valid Project Name');

        expect((string)$name)->toBe('Valid Project Name');
    });

    test('project name throws exception for empty string', function (): void {
        expect(fn(): ProjectName => new ProjectName(''))
            ->toThrow(InvalidArgumentException::class, 'Project name cannot be empty');
    });

    test('project name throws exception for whitespace only string', function (): void {
        expect(fn(): ProjectName => new ProjectName('   '))
            ->toThrow(InvalidArgumentException::class, 'Project name cannot be empty');
    });

    test('project names are equal when values match', function (): void {
        $name1 = new ProjectName('Same Name');
        $name2 = new ProjectName('Same Name');

        expect((string)$name1)->toBe((string)$name2);
    });

    test('project names are not equal when values differ', function (): void {
        $name1 = new ProjectName('Name One');
        $name2 = new ProjectName('Name Two');

        expect((string)$name1)->not->toBe((string)$name2);
    });

    test('project name accepts minimum valid length', function (): void {
        $name = new ProjectName('A'); // Minimum 1 znak

        expect((string)$name)->toBe('A');
    });

    test('project name accepts long string', function (): void {
        $longName = str_repeat('a', 100); // Test rozumnej dĺžky
        $name = new ProjectName($longName);

        expect((string)$name)->toBe($longName);
    });

    test('project name supports unicode characters', function (): void {
        $name = new ProjectName('Projekt s čeština ü ñ');

        expect((string)$name)->toBe('Projekt s čeština ü ñ');
    });
});
