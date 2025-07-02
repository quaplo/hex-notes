# Database Migration Steps for User Domain Rich Model

## Current Status
- ✅ Rich Domain Model implemented for User domain
- ✅ UserStatus enum created with business logic
- ✅ Domain exceptions implemented 
- ✅ User entity refactored from anemic to rich model
- ✅ UserEntity mapping updated for status field
- ✅ UserRepository updated with proper mapping
- ✅ Tests passing in test environment
- ⏳ **Migration pending**: Database schema not updated in production

## Issue
```
SQLSTATE[42703]: Undefined column: 7 ERROR: column t0.status does not exist
```

## Solution Steps

### 1. Start Database (Docker)
```bash
# If using Docker Compose
docker-compose up -d db

# Or if using Docker directly
docker run -d --name postgres_db \
  -e POSTGRES_DB=hex_notes \
  -e POSTGRES_USER=user \
  -e POSTGRES_PASSWORD=password \
  -p 5432:5432 postgres:13
```

### 2. Run Migration
```bash
# Apply the migration we created
php bin/console doctrine:migrations:migrate

# Verify migration was applied
php bin/console doctrine:migrations:status
```

### 3. Verify Schema
```bash
# Check current schema
php bin/console doctrine:schema:validate

# Alternative: Force schema update (if needed)
php bin/console doctrine:schema:update --force
```

## Migration File Created
- `migrations/Version20250702183000.php`
- Adds `status` column with default value 'active'
- Creates index on status for performance
- Includes proper rollback in `down()` method

## What This Migration Does
```sql
ALTER TABLE users ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active';
CREATE INDEX IDX_users_status ON users (status);
```

## After Migration Complete
1. Verify all tests pass: `composer test`
2. Test User operations in production environment
3. Continue with next roadmap items from `.todo/domain_user_next_steps.md`

## Next Priority Items
1. ✅ **Rich Domain Model** - COMPLETED
2. 🔄 **More domain exceptions** - In progress
3. ⏳ **Remove UserService** (move to command handlers)
4. ⏳ **Command/Query Bus integration**