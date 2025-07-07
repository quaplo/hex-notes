# Rector - Automated PHP Refactoring Tool

Rector je nainštalovaný a nakonfigurovaný pre tento projekt. Pomáha s automatickým refaktorovaním a modernizáciou PHP kódu.

## Dostupné príkazy

### Kontrola zmien (bez aplikovania)
```bash
# Základná kontrola s progress barom a diff výstupom
composer rector:dry-run

# Rýchla kontrola bez diff-ov (iba zhrnutie)
composer rector:check

# Alternatívne priamo cez vendor/bin
vendor/bin/rector --dry-run
vendor/bin/rector --dry-run --no-diffs
```

### Aplikovanie zmien
```bash
# Aplikuje všetky navrhované zmeny
composer rector

# Alternatívne priamo cez vendor/bin
vendor/bin/rector
```

### Kontrola špecifických súborov/adresárov
```bash
# Len src adresár
vendor/bin/rector src --dry-run

# Špecifický súbor
vendor/bin/rector src/Project/Domain/Model/Project.php --dry-run

# Viacero ciest
vendor/bin/rector src tests --dry-run
```

## Konfigurácia (rector.php)

Aktuálna konfigurácia zahŕňa:

### Podporované PHP verzie a sady pravidiel
- **LevelSetList::UP_TO_PHP_82** - Modernizácia na PHP 8.2
- **SetList::CODE_QUALITY** - Zlepšenie kvality kódu
- **SetList::DEAD_CODE** - Odstránenie nepoužívaného kódu
- **SetList::EARLY_RETURN** - Early return patterns
- **SetList::TYPE_DECLARATION** - Type declarations

### Symfony špecifické pravidlá
- **SymfonySetList::SYMFONY_72** - Symfony 7.2 kompatibilita
- **SymfonySetList::SYMFONY_CODE_QUALITY** - Symfony coding standards
- **SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION** - Constructor injection patterns

### Doctrine špecifické pravidlá
- **DoctrineSetList::DOCTRINE_ORM_214** - Doctrine ORM 2.14 kompatibilita
- **DoctrineSetList::DOCTRINE_CODE_QUALITY** - Doctrine best practices

### Vylúčené adresáre
- `var/` - cache a log súbory
- `vendor/` - dependencies
- `migrations/` - database migrácie
- `config/bootstrap.php` - bootstrap súbor

### Ďalšie nastavenia
- **Use statements preferované** - Rector preferuje krátke názvy tried s use statements namiesto FQDN
- **Automatické importy** - pridáva potrebné use statements
- **Paralelné spracovanie** - zapnuté pre rýchlejšie vykonanie
- **Odstránenie nepoužívaných importov** - automatické čistenie

## Príklady zlepšení, ktoré Rector navrhuje

### 1. Explicitné boolean porovnania s use statements
```php
// Pred
if (!$project) {
    throw new ProjectNotFoundException($id);
}

// Po (používa existujúci use statement)
if (!$project instanceof Project) {
    throw new ProjectNotFoundException($id);
}
```

### 2. Modernizácia class referencií
```php
// Pred
match (get_class($event)) {
    ProjectCreatedEvent::class => $this->handle($event)
}

// Po
match ($event::class) {
    ProjectCreatedEvent::class => $this->handle($event)
}
```

### 3. Readonly properties
```php
// Pred
final class Handler
{
    public function __construct(
        private readonly Repository $repository
    ) {}
}

// Po
final readonly class Handler
{
    public function __construct(
        private Repository $repository
    ) {}
}
```

### 4. Arrow functions s return types
```php
// Pred
array_map(function ($item) {
    return $item->getId();
}, $items);

// Po
array_map(fn($item): string => $item->getId(), $items);
```

### 5. Void return types pre testy
```php
// Pred
test('should work', function () {
    expect(true)->toBeTrue();
});

// Po
test('should work', function (): void {
    expect(true)->toBeTrue();
});
```

## Odporúčaný workflow

1. **Pravidelná kontrola**: Spustiť `composer rector:check` pred commitom
2. **Postupná aplikácia**: Aplikovať zmeny po častiach pre ľahšie review
3. **Testovanie**: Po aplikácii zmien vždy spustiť testy
4. **CI/CD integrácia**: Pridať rector check do CI pipeline

## Užitočné parametre

```bash
# Výstup do súboru (JSON formát)
vendor/bin/rector --dry-run --output-format=json > rector-report.json

# Verbose output pre debugging
vendor/bin/rector --dry-run -v

# Bez diff-ov (iba zhrnutie)
vendor/bin/rector --dry-run --no-diffs

# Bez progress baru
vendor/bin/rector --dry-run --no-progress-bar

# Paralelné spracovanie (štandardne zapnuté)
vendor/bin/rector --parallel

# Konkrétne pravidlá
vendor/bin/rector --dry-run --only="Rector\CodeQuality\Rector\Class_\ExplicitBoolCompareRector"
```

## Integrácia s IDE

Pre VS Code môžete pridať tieto task definitions do `.vscode/tasks.json`:

```json
{
    "version": "2.0.0",
    "tasks": [
        {
            "label": "Rector: Check",
            "type": "shell",
            "command": "composer rector:check",
            "group": "build",
            "presentation": {
                "echo": true,
                "reveal": "always"
            }
        },
        {
            "label": "Rector: Apply",
            "type": "shell", 
            "command": "composer rector",
            "group": "build"
        }
    ]
}
```

## Customizácia

Pre pridanie vlastných pravidiel alebo úpravu konfigurácie upravte súbor `rector.php`:

```php
// Pridanie vlastných pravidiel
$rectorConfig->rule(SomeCustomRector::class);

// Vylúčenie konkrétnych pravidiel
$rectorConfig->skip([
    SomeRector::class => [
        __DIR__ . '/src/Legacy'
    ]
]);

// Konfigurácia konkrétneho pravidla  
$rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
    'OldClass' => 'NewClass'
]);