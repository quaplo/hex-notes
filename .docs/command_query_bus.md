Perfektná otázka! Pozrime si konkrétny problém a riešenie:

## 🚨 **Aktuálny problém s ProjectController**

Pozri si [`ProjectController`](src/Infrastructure/Http/Controller/ProjectController.php:35-46) - má **10 dependencies** v konstruktore:

```php
public function __construct(
    private readonly RegisterProjectHandler $registerProjectHandler,           // 1
    private readonly RenameProjectHandler $renameProjectHandler,               // 2
    private readonly DeleteProjectHandler $deleteProjectHandler,               // 3
    private readonly GetProjectHandler $getProjectHandler,                     // 4
    private readonly GetProjectHistoryHandler $getProjectHistoryHandler,       // 5
    private readonly ProjectDtoMapper $projectDtoMapper,                       // 6
    private readonly SerializerInterface $serializer,                          // 7
    private readonly GetProjectFullDetailHandler $getProjectFullDetailHandler, // 8
    private readonly AddProjectWorkerHandler $addProjectWorkerHandler,         // 9
    private readonly RemoveProjectWorkerHandler $removeProjectWorkerHandler,   // 10
) {}
```

### ❌ **Problémy tohto prístupu**:

1. **Porušenie Single Responsibility** - controller vie o všetkých handleroch
2. **Ťažká testovateľnosť** - musíš mock-ovať 10 dependencies
3. **Nízka škálovateľnosť** - každý nový endpoint = nový dependency
4. **Tight coupling** - controller je úzko spojený s konkrétnymi handlermi
5. **Violation of Open/Closed Principle** - pridanie nového handlera vyžaduje zmenu controller-a

---

## ✅ **Command/Query Bus Pattern - Riešenie**

### **Princíp fungowania**:

**Command Bus** a **Query Bus** sú **mediator pattern-y** ktoré:
- Prijmú command/query object
- Nájdu správny handler pre daný typ
- Zavolajú handler a vrátia výsledok

### **Výhody**:

1. **Decoupling** - controller nevie o konkrétnych handleroch
2. **Single Responsibility** - controller iba routuje HTTP requesty
3. **Easy testing** - mock iba 2-3 dependencies namiesto 10
4. **Scalability** - nové handlery nepotrebujú zmeny v controller-i
5. **Clean architecture** - jasné oddelenie concerns

---

## 🏗️ **Implementácia Command/Query Bus**

### **1. Command Bus Interface**
```php
// src/Shared/Application/CommandBus.php
interface CommandBus {
    public function dispatch(object $command): mixed;
}
```

### **2. Query Bus Interface**
```php
// src/Shared/Application/QueryBus.php
interface QueryBus {
    public function dispatch(object $query): mixed;
}
```

### **3. Symfony Messenger implementácia**
```php
// src/Infrastructure/Bus/SymfonyCommandBus.php
class SymfonyCommandBus implements CommandBus {
    public function __construct(
        private readonly MessageBusInterface $commandBus
    ) {}
    
    public function dispatch(object $command): mixed {
        return $this->commandBus->dispatch($command)->last(HandledStamp::class)?->getResult();
    }
}
```

### **4. Refaktorovaný Controller**
```php
// src/Infrastructure/Http/Controller/ProjectController.php
final class ProjectController {
    public function __construct(
        private readonly CommandBus $commandBus,           // iba 3 dependencies!
        private readonly QueryBus $queryBus,
        private readonly SerializerInterface $serializer,
    ) {}

    #[Route('/api/projects', methods: ['POST'])]
    public function create(Request $request): JsonResponse {
        $dto = $this->serializer->deserialize(/* ... */);
        
        $command = RegisterProjectCommand::fromPrimitives($dto->name, $dto->ownerId);
        $project = $this->commandBus->dispatch($command);  // ⭐ Bus finds handler automatically
        
        return new JsonResponse($project, 201);
    }
    
    #[Route('/api/projects/{id}', methods: ['GET'])]
    public function detail(string $id): JsonResponse {
        $query = GetProjectQuery::fromPrimitives($id);
        $project = $this->queryBus->dispatch($query);      // ⭐ Bus finds handler automatically
        
        return new JsonResponse($project);
    }
}
```

---

## 🔧 **Ako Bus nájde správny Handler?**

### **Symfony Messenger config**
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction
            query.bus:
                default_middleware: allow_no_handlers
        routing:
            'App\Project\Application\Command\RegisterProjectCommand': command.bus
            'App\Project\Application\Command\RenameProjectCommand': command.bus
            'App\Project\Application\Query\GetProjectQuery': query.bus
```

### **Handler registration** (auto-discovery)
```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        
    App\Project\Application\Command\:
        resource: '../src/Project/Application/Command/*Handler.php'
        tags:
            - { name: messenger.message_handler, bus: command.bus }
            
    App\Project\Application\Query\:
        resource: '../src/Project/Application/Query/*Handler.php' 
        tags:
            - { name: messenger.message_handler, bus: query.bus }
```

---

## 📊 **Porovnanie: Pred vs Po**

### **❌ Pred (aktuálne)**
```php
class ProjectController {
    // 10 dependencies v konstruktore!
    public function __construct(/*...*/) {}
    
    public function create(Request $request): JsonResponse {
        // Direct handler call
        $project = ($this->registerProjectHandler)($command);
    }
}
```

### **✅ Po (s Bus)**
```php
class ProjectController {
    // iba 3 dependencies!
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly SerializerInterface $serializer,
    ) {}
    
    public function create(Request $request): JsonResponse {
        // Bus finds handler automatically
        $project = $this->commandBus->dispatch($command);
    }
}
```

---

## 🎯 **Ďalšie výhody Bus pattern-u**

### **1. Cross-cutting concerns**
```php
// Môžeš pridať middleware pre všetky commands
- Validation middleware
- Transaction middleware  
- Logging middleware
- Authorization middleware
```

### **2. Easy testing**
```php
class ProjectControllerTest {
    public function test_create_project() {
        $commandBus = $this->createMock(CommandBus::class);  // iba 1 mock!
        $commandBus->expects($this->once())
                   ->method('dispatch')
                   ->with($this->isInstanceOf(RegisterProjectCommand::class));
                   
        $controller = new ProjectController($commandBus, $queryBus, $serializer);
    }
}
```

### **3. Multiple handlers** (ak potrebuješ)
```php
// Môžeš mať viacero handlerov pre jeden command
class SendEmailAfterProjectCreated implements MessageHandlerInterface {
    public function __invoke(RegisterProjectCommand $command) {
        // Send notification email
    }
}
```

---

## 🚀 **Kedy implementovať Command/Query Bus?**

### **✅ Odporúčam implementovať ak**:
- Controller má 5+ handler dependencies
- Chceš pridať cross-cutting concerns (logging, validation)
- Plánuješ rozšíriť projekt o ďalšie moduly
- Chceš lepšiu testovateľnosť

### **❌ Nemusíš implementovať ak**:
- Malý projekt s 2-3 endpoint-mi
- Neočakávaš rozšírenie
- Jednoduchosť je priorita

---

## 💡 **Záver pre tvoj projekt**

**Command/Query Bus by bolo ideálne** lebo:
1. Máš už **10 dependencies** v controller-i
2. Používaš **CQRS pattern** (commands + queries)
3. Projekt je **dobře strukturovaný** pre DDD
4. **Symfony Messenger** je už súčasť Symfony

Toto by bol **posledný krok** k **perfektnej DDD/CQRS architektúre**! 🎯

Chceš aby som ti to implementoval?