# Ďalšie príležitosti na refaktoring 🔧

## ✅ Dokončené oblasti:
- **User Domain**: Rich domain model, Command/Query Bus, domain exceptions
- **Project Domain**: Event Sourcing, DDD, kompletná architektúra
- **Database**: Zjednotená konfigurácia, migrácie funkčné
- **Bus Pattern**: Implementovaný pre obe domény

## 🎯 Identifikované možnosti na zlepšenie:

### 1. 📋 Infrastructure Layer Optimalizácia

#### A) **DTO Pattern konzistencia**
- `ProjectController` používa rozličné prístupy k DTO deserializácii
- `UserController` má iný pattern ako `ProjectController`
- **Riešenie**: Unifikovať DTO handling pattern

#### B) **Validation Layer chýba**
- Controllery majú primitívnu validáciu (`isset($data['name'])`)
- Chýba konzistentný validation framework
- **Riešenie**: Symfony Validator integration

#### C) **Exception Handling**
- Controllers majú manuálne exception handling
- Chýba centralizovaný exception handler
- **Riešenie**: Global exception listener

### 2. 🏗️ Shared Kernel rozšírenie

#### A) **Value Objects rozšírenie**
- Len `Email` a `Uuid` sú implementované
- User doména môže benefitovať z ďalších VOs
- **Riešenie**: `UserProfile`, `ProjectName`, `WorkerRole`

#### B) **Command/Query štruktúra**
- Commands/Queries nemajú spoločnú interface
- Chýba validation a metadata
- **Riešenie**: `CommandInterface`, `QueryInterface`

#### C) **Domain Events rozšírenie**
- Project má events, User domain nemá
- Chýba cross-domain communication
- **Riešenie**: User domain events

### 3. 🧪 Testing Infrastructure

#### A) **Test Doubles chýbajú pre User domain**
- Project má `InMemoryProjectRepository`
- User domain testuje proti reálnej DB
- **Riešenie**: `InMemoryUserRepository`

#### B) **Integration testy sú limitované**
- Len základné controller testy
- Chýbajú end-to-end scenarios
- **Riešenie**: Business scenario tests

### 4. 📊 Performance & Monitoring

#### A) **Doctrine ORM optimalizácia**
- User repository volá `flush()` pri každom `save()`
- Project používa Event Store (async)
- **Riešenie**: Unit of Work pattern, batch operations

#### B) **Query optimalizácia**
- Chýbajú indexy pre často používané queries
- N+1 problem možný pri relacionálnych dotazoch
- **Riešenie**: Query optimization

### 5. 🔐 Security & Authorization

#### A) **Authorization chýba**
- Controllers nemajú access control
- Domain entities sú verejne prístupné
- **Riešenie**: Security Voters, role-based access

#### B) **Input sanitization**
- Primitive input validation
- XSS protection chýba
- **Riešenie**: Input validation rules

### 6. 🚀 DevOps & Configuration

#### A) **Environment configurácie**
- Mixovanie .env súborov
- Hardcoded values v kóde
- **Riešenie**: Proper config management

#### B) **Logging chýba**
- Žiadne business operation logs
- Debug info nie je trackovaná
- **Riešenie**: Structured logging

## 📋 Prioritizované akcie:

### 🔥 Vysoká priorita:
1. **DTO Pattern unifikácia** - lepšie user experience
2. **Validation Layer** - robustnosť aplikácie  
3. **Exception Handling** - lepšie error responses
4. **User Domain Events** - konzistencia s Project domain

### ⚡ Stredná priorita:
5. **Test Infrastructure rozšírenie** - kvalita kódu
6. **Value Objects rozšírenie** - domain modeling
7. **Doctrine optimalizácia** - performance

### 📈 Nízka priorita:
8. **Authorization** - security (ak nie production ready)
9. **Monitoring/Logging** - observability
10. **DevOps optimalizácia** - developer experience

## 🎯 Navrhovaný postup:

1. **Začneme s Infrastructure Layer** - najviac viditeľné zlepšenia
2. **Pokračujeme User Domain Events** - kompletná DDD konzistencia  
3. **Přidáme Testing Infrastructure** - kvalita a udržateľnosť
4. **Optimalizujeme Performance** - škálovateľnosť

Každá položka je nezávislá a možno ju implementovať postupne bez breaking changes.