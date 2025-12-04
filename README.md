# ProBuy - Multi-Tenant E-Commerce Platform

A comprehensive, API-only, multi-tenant e-commerce platform built with Laravel, PostgreSQL, and Redis. Designed for scalability, security, and maintainability with domain-driven design principles.

## 🚀 Features

### Multi-Tenancy
- **Row-level tenancy** with automatic query scoping
- Tenant-isolated media storage
- Per-tenant cache keys and queue jobs
- Strict data isolation with middleware enforcement

### E-Commerce Capabilities
- **Product Management**: Products, variants, categories, tags
- **Inventory System**: Real-time stock tracking, reservations with TTL, low-stock alerts
- **Order Processing**: Complete order lifecycle from cart to fulfillment
- **Payment Integration**: Ready for Stripe, PayPal, and other gateways
- **Shipping**: Carrier integration framework for label generation
- **Promotions**: Coupons with flexible conditions
- **Reviews & Ratings**: 5-star reviews with average rating on products
- **Wishlists**: Multiple wishlists per customer

### Security & Access Control
- **OAuth2 Authentication** via Laravel Passport
- **Role-Based Access Control** (RBAC) with Spatie Permissions
- **Activity Logging**: Comprehensive audit trails
- **Tenant Isolation**: Multiple layers of security

### Performance
- **Redis Caching**: Intelligent cache invalidation
- **Background Jobs**: Queue-driven processing for heavy operations
- **Database Optimization**: Composite indexes, JSONB for flexibility

### International Support
- **5 Languages**: English, Arabic, Spanish, German, Italian
- **Translation Tables**: Products and categories
- **RTL Support**: Ready for Arabic

### Advanced Features
- **Search**: Full-text search with advanced filtering
- **Reports**: Sales, inventory, customer, and product analytics
- **Notifications**: Email, SMS, and push notification support

## 📋 Requirements

- Docker & Docker Compose
- Git

## 🛠️ Installation

### 1. Clone Repository

```bash
git clone <repository-url> probuy
cd probuy
```

### 2. Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` if needed (default Docker settings work out of the box):

```env
APP_NAME=ProBuy
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=probuy
DB_USERNAME=probuy
DB_PASSWORD=probuy_secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

FILESYSTEM_DISK=tenant_media
MEDIA_DISK=tenant_media
```

### 3. Start Docker Services

```bash
# Start all containers in background
docker-compose up -d
```

This starts 7 services:
- **app** (PHP 8.2-FPM)
- **nginx** (Web server on port 8000)
- **postgres** (PostgreSQL 15)
- **redis** (Cache & queue)
- **queue** (Background worker)
- **scheduler** (Cron jobs)
- **horizon** (Queue monitor)

### 4. Install Dependencies & Setup Application

```bash
# Install Composer dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed database with demo data
docker-compose exec app php artisan db:seed

# Install Passport (OAuth2)
docker-compose exec app php artisan passport:install
```

### 5. Access the Application

- **API**: http://localhost:8000/api/v1
- **Telescope**: http://localhost:8000/telescope (development debugging)
- **Horizon**: http://localhost:8000/horizon (queue monitoring)

### 6. Get Demo Credentials

After seeding, check the terminal output for:
- Tenant IDs
- Owner credentials: `owner@{tenant-slug}.com` / `password`
- Admin credentials: `admin@{tenant-slug}.com` / `password`

## 🔑 Authentication

### Get Access Token

First, get your Passport client credentials:

```bash
docker-compose exec app php artisan passport:client --password
```

Then get an access token:

```bash
curl -X POST http://localhost:8000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "password",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET",
    "username": "owner@tenant-slug.com",
    "password": "password",
    "scope": "*"
  }'
```

### Using Access Token

```bash
curl http://localhost:8000/api/v1/products \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "X-Tenant-Id: YOUR_TENANT_ID"
```

## 🧪 Testing

### Run All Tests

```bash
docker-compose exec app php artisan test
```

### Run with Coverage

```bash
docker-compose exec app php artisan test --coverage
```

### Run Specific Test Suite

```bash
docker-compose exec app php artisan test --testsuite=Feature
docker-compose exec app php artisan test --testsuite=Unit
```

## 📦 Common Docker Commands

### Container Management

```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# Restart specific service
docker-compose restart app
docker-compose restart queue

# View running containers
docker-compose ps
```

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f queue
docker-compose logs -f postgres

# Last 100 lines
docker-compose logs --tail=100 app

# Application logs
docker-compose exec app tail -f storage/logs/laravel.log
docker-compose exec app tail -f storage/logs/orders.log
```

### Execute Commands

```bash
# Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan tinker

# Composer
docker-compose exec app composer install
docker-compose exec app composer update

# Access container shell
docker-compose exec app sh

# Database access
docker-compose exec postgres psql -U probuy -d probuy

# Redis access
docker-compose exec redis redis-cli
```

### Database Operations

```bash
# Fresh database with seed
docker-compose exec app php artisan migrate:fresh --seed

# Run specific seeder
docker-compose exec app php artisan db:seed --class=TenantSeeder

# Rollback migration
docker-compose exec app php artisan migrate:rollback
```

### Cache & Queue Operations

```bash
# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Restart queue workers
docker-compose exec app php artisan queue:restart

# Clear failed jobs
docker-compose exec app php artisan queue:flush

# Retry failed jobs
docker-compose exec app php artisan queue:retry all
```

### Code Quality

```bash
# Format code with Laravel Pint
docker-compose exec app vendor/bin/pint

# Run static analysis
docker-compose exec app vendor/bin/phpstan analyse
```

## 🎨 Key Features

### Search Products

```bash
curl "http://localhost:8000/api/v1/search?q=laptop&min_price=100&min_rating=4&in_stock=1" \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-Id: TENANT_ID"
```

### Submit Product Review

```bash
curl -X POST "http://localhost:8000/api/v1/products/{id}/reviews" \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-Id: TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "rating": 5,
    "title": "Great product!",
    "comment": "Excellent quality and fast delivery"
  }'
```

### Manage Wishlist

```bash
# Create wishlist
curl -X POST "http://localhost:8000/api/v1/wishlists" \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-Id: TENANT_ID" \
  -d '{"name": "My Favorites"}'

# Add product to wishlist
curl -X POST "http://localhost:8000/api/v1/wishlists/{id}/products" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"product_id": "PRODUCT_ID", "note": "Birthday gift idea"}'
```

### Multi-Language Requests

```bash
# Request in Arabic
curl "http://localhost:8000/api/v1/products" \
  -H "Accept-Language: ar" \
  -H "Authorization: Bearer TOKEN"

# Request in Spanish
curl "http://localhost:8000/api/v1/products" \
  -H "Accept-Language: es" \
  -H "Authorization: Bearer TOKEN"
```

### Get Reports

```bash
# Sales report
curl "http://localhost:8000/api/v1/reports/sales?from=2025-01-01&to=2025-12-31" \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-Id: TENANT_ID"

# Inventory report
curl "http://localhost:8000/api/v1/reports/inventory" \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-Id: TENANT_ID"
```

## 📖 Documentation

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Complete system architecture
- **[TODO.md](TODO.md)** - Development roadmap
- Check API endpoints in `routes/api.php`

## 🐛 Troubleshooting

### Containers Won't Start

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Permission Errors

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Issues

```bash
# Check database is running
docker-compose ps postgres

# Test connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo();
```

### Queue Not Processing

```bash
# Check queue worker
docker-compose ps queue

# Restart queue worker
docker-compose restart queue

# Check Horizon
# Visit http://localhost:8000/horizon
```

### Reset Everything

```bash
docker-compose down -v
docker-compose up -d
docker-compose exec app php artisan migrate:fresh --seed
docker-compose exec app php artisan passport:install
```

## 🚀 Development Workflow

1. **Make code changes** - Edit files in your IDE
2. **Run tests** - `docker-compose exec app php artisan test`
3. **Check Telescope** - http://localhost:8000/telescope
4. **View logs** - `docker-compose logs -f app`
5. **Commit changes** - Git workflow

## 📦 Production Deployment

### Build for Production

```bash
# Build production image
docker-compose -f docker-compose.prod.yml build

# Start production containers
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Optimize
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

### CI/CD

GitHub Actions automatically:
- Run tests on PR
- Check code quality
- Build Docker images
- Deploy to staging (develop branch)
- Deploy to production (main branch)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`docker-compose exec app php artisan test`)
5. Commit (`git commit -m 'Add amazing feature'`)
6. Push (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 🆘 Support

For issues and questions:
- Create an issue on GitHub
- Check `ARCHITECTURE.md` for system design details
- Review existing tests for usage examples

---

**Built with ❤️ using Laravel**
