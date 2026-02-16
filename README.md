# Job Management API

A RESTful API built with Symfony for managing jobs and assignments with JWT authentication.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Documentation](#api-documentation)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
- [Usage Examples](#usage-examples)
- [Database Schema](#database-schema)
- [Security](#security)
- [Contributing](#contributing)

## Overview

The Job Management API is a comprehensive system that allows users to create, manage, and assign jobs. Users can register, authenticate, create jobs, assign them to themselves, and track their completion status.

## Features

- **User Authentication**: Secure registration and login with JWT tokens
- **Job Management**: Create, read, update, and delete jobs
- **Job Assignments**: Assign jobs to users with scheduled dates
- **Status Tracking**: Track job and assignment statuses
- **Timezone Support**: Support for UK, Mexico, and India timezones
- **Rating System**: Rate completed assignments (1-5 stars)
- **RESTful Design**: Clean and intuitive API endpoints
- **API Documentation**: Auto-generated Swagger/OpenAPI documentation

## Technology Stack

- **Framework**: Symfony 6.x
- **Language**: PHP 8.1+
- **Database**: MySQL/PostgreSQL (via Doctrine ORM)
- **Authentication**: JWT (JSON Web Tokens) via LexikJWTAuthenticationBundle
- **API Documentation**: NelmioApiDocBundle (Swagger/OpenAPI)
- **ORM**: Doctrine

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL or PostgreSQL
- OpenSSL (for JWT key generation)

### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd job-management-api
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment variables**
   ```bash
   cp .env .env.local
   ```
   Edit `.env.local` and configure your database connection:
   ```
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/job_management?serverVersion=8.0"
   ```

4. **Generate JWT keys**
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

5. **Create database**
   ```bash
   php bin/console doctrine:database:create
   ```

6. **Run migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

7. **Start the server**
   ```bash
   symfony server:start
   # or
   php -S localhost:8000 -t public
   ```

## Configuration

### JWT Configuration

The JWT bundle requires RSA keys. Generate them using:
```bash
php bin/console lexik:jwt:generate-keypair
```

Keys will be stored in `config/jwt/` directory.

### Database Configuration

Configure your database in `.env.local`:
```
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name?serverVersion=8.0"
```

### CORS Configuration (Optional)

If you need to enable CORS for frontend applications, install and configure `nelmio/cors-bundle`:
```bash
composer require nelmio/cors-bundle
```

## API Documentation

Once the application is running, access the interactive API documentation at:
```
http://localhost:8000/api/doc
```

The documentation is auto-generated using NelmioApiDocBundle and provides:
- Interactive API testing
- Request/response examples
- Schema definitions
- Authentication requirements

## Authentication

This API uses JWT (JSON Web Tokens) for authentication.

### Registration
```bash
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "name": "John Doe",
  "timezone": "UK"
}
```

### Login
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "name": "John Doe",
      "timezone": "UK"
    }
  }
}
```

### Using the Token

Include the JWT token in the Authorization header for all protected endpoints:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

## API Endpoints

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/register` | Register a new user | No |
| POST | `/api/auth/login` | Login user | No |
| GET | `/api/auth/me` | Get current user info | Yes |

### Jobs

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/jobs` | List all jobs | Yes |
| GET | `/api/jobs/available` | List available jobs | Yes |
| GET | `/api/jobs/{id}` | Get job by ID | Yes |
| POST | `/api/jobs` | Create new job | Yes |
| PUT | `/api/jobs/{id}` | Update job (full) | Yes |
| PATCH | `/api/jobs/{id}` | Update job (partial) | Yes |
| DELETE | `/api/jobs/{id}` | Delete job | Yes |

### Job Assignments

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/api/assignments` | List user's assignments | Yes |
| GET | `/api/assignments/my` | List user's assignments | Yes |
| GET | `/api/assignments/{id}` | Get assignment by ID | Yes |
| POST | `/api/assignments` | Create assignment | Yes |
| POST | `/api/assignments/{id}/complete` | Complete assignment | Yes |
| DELETE | `/api/assignments/{id}` | Delete assignment | Yes |

## Usage Examples

### Create a Job

```bash
curl -X POST http://localhost:8000/api/jobs \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Plumber needed",
    "description": "Fix kitchen sink leak",
    "location": "New York, NY"
  }'
```

### Get Available Jobs

```bash
curl -X GET http://localhost:8000/api/jobs/available \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Assign a Job

```bash
curl -X POST http://localhost:8000/api/assignments \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "job_id": 1,
    "scheduled_date": "2024-12-31 10:00:00"
  }'
```

### Complete an Assignment

```bash
curl -X POST http://localhost:8000/api/assignments/1/complete \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "assessment": "Job completed successfully. Customer was satisfied.",
    "rating": 5
  }'
```

### Filter Jobs by Status

```bash
curl -X GET "http://localhost:8000/api/jobs?status=available" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Database Schema

### Users Table
- `id` (Primary Key)
- `email` (Unique)
- `password` (Hashed)
- `name`
- `timezone` (UK, MEXICO, INDIA)
- `roles` (JSON)
- `created_at`

### Jobs Table
- `id` (Primary Key)
- `title`
- `description`
- `location`
- `status` (available, assigned, completed, cancelled)
- `created_at`
- `updated_at`

### Job Assignments Table
- `id` (Primary Key)
- `user_id` (Foreign Key → Users)
- `job_id` (Foreign Key → Jobs)
- `scheduled_date`
- `completed_at`
- `assessment`
- `rating` (1-5)
- `status` (scheduled, completed)
- `created_at`
- `updated_at`

## Security

### Password Security
- Passwords are hashed using Symfony's `UserPasswordHasher`
- Minimum password length: 8 characters

### JWT Security
- JWT tokens expire after a configurable time (default: 1 hour)
- Tokens are signed using RSA keys
- All protected endpoints require valid JWT tokens

### Access Control
- Users can only view and manage their own assignments
- All job operations require authentication
- Role-based access control (ROLE_USER)

### Input Validation
- Email format validation
- Required field validation
- Status enum validation
- Rating range validation (1-5)
- Timezone validation

## Error Handling

The API returns consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": "Specific error message"
  }
}
```

### HTTP Status Codes

- `200 OK` - Successful GET, PUT, PATCH, DELETE
- `201 Created` - Successful POST
- `400 Bad Request` - Invalid input data
- `401 Unauthorized` - Missing or invalid authentication
- `403 Forbidden` - Access denied to resource
- `404 Not Found` - Resource not found
- `409 Conflict` - Resource already exists (e.g., email in use)

## Testing

Run tests using PHPUnit:
```bash
php bin/phpunit
```

## Development

### Code Style

Follow PSR-12 coding standards. Use PHP CS Fixer:
```bash
composer require --dev friendsofphp/php-cs-fixer
vendor/bin/php-cs-fixer fix src
```

### Debug Mode

Enable debug mode in `.env.local`:
```
APP_ENV=dev
APP_DEBUG=true
```

### Logging

Logs are stored in `var/log/`:
- `dev.log` - Development logs
- `prod.log` - Production logs

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License.

## Support

For issues and questions:
- Open an issue on GitHub
- Contact the development team

## Roadmap

Future enhancements:
- [ ] Email notifications for job assignments
- [ ] File upload support for job documentation
- [ ] Job categories and tags
- [ ] Advanced search and filtering
- [ ] User profiles with avatars
- [ ] Job comments and discussions
- [ ] Real-time notifications
- [ ] Mobile app integration
- [ ] Analytics dashboard
- [ ] Multi-language support

## Acknowledgments

- Symfony Framework
- Doctrine ORM
- LexikJWTAuthenticationBundle
- NelmioApiDocBundle