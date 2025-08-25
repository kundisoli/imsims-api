# Inventory Management System

A comprehensive inventory management system built with Laravel, PostgreSQL, MongoDB, Redis, and React. This system provides a hybrid database approach for optimal performance and flexibility.

## Features

### Core Functionality
- **Product Management**: Complete CRUD operations for products with metadata support
- **Category Management**: Hierarchical category structure with tree navigation
- **Warehouse Management**: Multi-warehouse support with location tracking
- **Inventory Tracking**: Real-time inventory levels with automatic stock calculations
- **Stock Movements**: Complete audit trail of all inventory movements
- **Purchase Orders**: Full purchase order lifecycle management
- **Supplier Management**: Comprehensive supplier information and relationship tracking

### Technical Features
- **Hybrid Database**: PostgreSQL for relational data, MongoDB for flexible metadata
- **Redis Caching**: Multi-layer caching for optimal performance
- **Authentication & Authorization**: Role-based access control with Sanctum
- **API-First Design**: Complete REST API with proper documentation
- **Real-time Updates**: Live inventory updates and notifications
- **Performance Monitoring**: Built-in performance metrics and logging
- **Audit Logging**: Complete audit trail in MongoDB
- **Dockerized**: Full Docker setup for easy deployment

## Technology Stack

### Backend
- **Laravel 12**: PHP framework
- **PostgreSQL 16**: Primary relational database
- **MongoDB 7.0**: Document database for flexible data
- **Redis 7.2**: Caching and session storage
- **Laravel Sanctum**: API authentication

### Frontend
- **React 19**: UI framework
- **Inertia.js**: Server-side rendering
- **Tailwind CSS 4**: Styling
- **Radix UI**: Component library
- **TypeScript**: Type safety

### DevOps
- **Docker**: Containerization
- **Nginx**: Web server
- **Node.js 20**: Frontend build tools

## Installation

### Prerequisites
- Docker and Docker Compose
- Git

### Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd inventory-management-system
   ```

2. **Start the Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Install dependencies**
   ```bash
   # PHP dependencies
   docker-compose exec app composer install
   
   # Node dependencies
   docker-compose exec node npm install
   ```

4. **Set up the application**
   ```bash
   # Generate application key
   docker-compose exec app php artisan key:generate
   
   # Run migrations
   docker-compose exec app php artisan migrate
   
   # Seed the database
   docker-compose exec app php artisan db:seed
   ```

5. **Build frontend assets**
   ```bash
   docker-compose exec node npm run build
   ```

6. **Access the application**
   - Web Interface: http://localhost:8000
   - API: http://localhost:8000/api/v1

### Manual Installation

If you prefer to run without Docker:

1. **Environment Setup**
   - PHP 8.3+
   - PostgreSQL 16+
   - MongoDB 7.0+
   - Redis 7.2+
   - Node.js 20+

2. **Database Configuration**
   Update `.env` file with your database credentials:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=localhost
   DB_PORT=5432
   DB_DATABASE=inventory_db
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   MONGODB_HOST=localhost
   MONGODB_PORT=27017
   MONGODB_DATABASE=inventory_logs
   
   REDIS_HOST=localhost
   REDIS_PORT=6379
   ```

3. **Install and Configure**
   ```bash
   composer install
   npm install
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   npm run build
   ```

## Default Users

After seeding, you can log in with these default accounts:

| Role | Email | Password | Permissions |
|------|-------|----------|-------------|
| Admin | admin@inventory.com | password | Full system access |
| Manager | manager@inventory.com | password | Inventory management |
| Employee | employee@inventory.com | password | Basic operations |
| User | user@inventory.com | password | Read-only access |

## API Documentation

### Authentication

All API endpoints require authentication except for login/register. Use Laravel Sanctum tokens.

**Login**
```bash
POST /api/v1/login
Content-Type: application/json

{
  "email": "admin@inventory.com",
  "password": "password",
  "device_name": "web"
}
```

**Response**
```json
{
  "token": "1|token_here",
  "user": {
    "id": 1,
    "name": "System Administrator",
    "email": "admin@inventory.com",
    "role": "admin",
    "permissions": ["access_inventory", "manage_products", ...]
  }
}
```

### Core Endpoints

#### Products
- `GET /api/v1/products` - List products
- `POST /api/v1/products` - Create product
- `GET /api/v1/products/{id}` - Get product details
- `PUT /api/v1/products/{id}` - Update product
- `DELETE /api/v1/products/{id}` - Delete product
- `GET /api/v1/products/{id}/stock-summary` - Get stock summary

#### Inventory
- `GET /api/v1/inventory/overview` - Inventory dashboard data
- `GET /api/v1/inventory/warehouse/{id}` - Warehouse inventory
- `POST /api/v1/inventory/adjust-stock` - Adjust stock levels
- `POST /api/v1/inventory/transfer-stock` - Transfer between locations

#### Categories
- `GET /api/v1/categories` - List categories
- `GET /api/v1/categories/tree/all` - Category tree structure
- `POST /api/v1/categories` - Create category

#### Warehouses
- `GET /api/v1/warehouses` - List warehouses
- `GET /api/v1/warehouses/{id}/inventory-summary` - Warehouse summary

## Database Schema

### PostgreSQL Tables
- `users` - User accounts and roles
- `categories` - Product categories (hierarchical)
- `suppliers` - Supplier information
- `warehouses` - Warehouse locations
- `locations` - Storage locations within warehouses
- `products` - Product catalog
- `inventory_records` - Current stock levels
- `stock_movements` - Inventory movement history
- `purchase_orders` - Purchase order management
- `purchase_order_items` - Purchase order line items

### MongoDB Collections
- `inventory_activities` - Detailed activity logs
- `product_metadata` - Flexible product data (images, tags, specs)
- `audit_logs` - System audit trail
- `performance_metrics` - Performance monitoring data

## Caching Strategy

The system implements a multi-layer caching strategy:

1. **Redis Application Cache**
   - Product data (1 hour TTL)
   - Category trees (24 hour TTL)
   - Inventory overviews (5 minute TTL)

2. **Query Result Caching**
   - Database query results
   - API response caching

3. **Browser Caching**
   - Static assets
   - API responses with appropriate headers

### Cache Management

```bash
# Warm up caches
php artisan cache:warm-up

# Clear all caches
php artisan cache:clear

# Clear specific cache tags
php artisan tinker
>>> Cache::tags(['products'])->flush();
```

## Performance Monitoring

The system includes built-in performance monitoring:

- **Response Time Tracking**: All API requests logged
- **Database Query Monitoring**: Slow query detection
- **Cache Hit/Miss Ratios**: Cache performance metrics
- **Memory Usage Tracking**: Resource utilization

Access metrics through the admin dashboard or MongoDB directly.

## Security Features

- **Role-Based Access Control**: Granular permission system
- **API Rate Limiting**: Prevent abuse
- **CSRF Protection**: Built-in Laravel protection
- **SQL Injection Prevention**: Eloquent ORM protection
- **XSS Protection**: Input sanitization
- **Audit Logging**: Complete action trail

## Development

### Running Tests
```bash
# PHP tests
docker-compose exec app php artisan test

# Frontend tests
docker-compose exec node npm test
```

### Code Quality
```bash
# PHP formatting
docker-compose exec app ./vendor/bin/pint

# TypeScript checking
docker-compose exec node npm run types
```

### Database Migrations
```bash
# Create migration
docker-compose exec app php artisan make:migration create_new_table

# Run migrations
docker-compose exec app php artisan migrate

# Rollback
docker-compose exec app php artisan migrate:rollback
```

## Deployment

### Production Setup

1. **Environment Variables**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=your_generated_key
   
   # Use production database credentials
   # Enable Redis password
   # Configure proper mail settings
   ```

2. **Optimization**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```

3. **Security**
   - Enable HTTPS
   - Configure firewall
   - Set up database backups
   - Monitor logs

### Docker Production

Use the production Docker Compose configuration:
```bash
docker-compose -f docker-compose.prod.yml up -d
```

## Monitoring & Maintenance

### Health Checks
- Database connectivity
- Redis availability
- MongoDB connection
- File system permissions
- Cache performance

### Backup Strategy
- Daily PostgreSQL dumps
- MongoDB replica sets
- Redis persistence
- File storage backups

### Log Management
- Application logs (Laravel)
- Web server logs (Nginx)
- Database logs
- Performance metrics

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   ```bash
   # Check database status
   docker-compose ps
   
   # Restart database
   docker-compose restart postgres
   ```

2. **Cache Issues**
   ```bash
   # Clear all caches
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

3. **Permission Errors**
   ```bash
   # Fix file permissions
   docker-compose exec app chown -R www:www storage bootstrap/cache
   ```

### Debug Mode

Enable debug mode for development:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

### Coding Standards
- PSR-12 for PHP
- ESLint configuration for TypeScript
- Conventional commits for git messages

## License

This project is licensed under the MIT License. See LICENSE file for details.

## Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the API examples

## Changelog

### Version 1.0.0
- Initial release
- Complete inventory management system
- Multi-database architecture
- Redis caching implementation
- Role-based authentication
- React frontend with TypeScript
- Docker containerization
- Comprehensive API documentation