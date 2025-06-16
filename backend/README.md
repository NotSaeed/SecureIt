# SecureIt Backend

A comprehensive PHP-based backend for the SecureIt password manager application.

## Features

- **User Management**: Registration, authentication, profile management
- **Vault Operations**: Secure storage and retrieval of passwords and sensitive data
- **Password Generation**: Strong password, passphrase, and username generation
- **Security Analysis**: Password strength checking, breach detection, duplicate detection
- **Two-Factor Authentication**: TOTP-based 2FA with backup codes
- **Secure Send**: Encrypted temporary message/file sharing
- **Reports**: Security reports, breach analysis, activity reports
- **Data Export**: Vault export in multiple formats (JSON, CSV, XML)

## Database Structure

The application uses MySQL with the following main tables:
- `users` - User accounts and authentication
- `vaults` - Encrypted password/data storage
- `folders` - Organization folders for vault items
- `sends` - Temporary encrypted messages/files
- `generator_history` - Password generation history
- `security_reports` - Saved security analysis reports
- `migrations` - Database schema version tracking

## API Endpoints

### Authentication (`/api/auth.php`)
- `POST` - Register new user
- `POST` - User login/logout
- `GET` - Get user profile
- `POST` - Setup/enable/disable 2FA

### Vault Management (`/api/vault.php`)
- `GET` - List all vault items
- `POST` - Create new vault item
- `PUT` - Update existing item
- `DELETE` - Delete vault item
- `GET` - Search vault items
- `GET` - Security analysis

### Password Generator (`/api/generator.php`)
- `POST` - Generate password
- `POST` - Generate passphrase
- `POST` - Generate username
- `POST` - Check password strength
- `GET` - Generation history

### Secure Send (`/api/send.php`)
- `POST` - Create new send
- `GET` - List user's sends
- `GET` - Retrieve send by link
- `DELETE` - Delete send

### Reports (`/api/reports.php`)
- `GET` - Security report
- `GET` - Breach report
- `GET` - Password strength report
- `GET` - Activity report
- `GET` - Export vault data

## Setup Instructions

### 1. Database Setup
```bash
# Run migrations to create database structure
php migrate.php up

# Check migration status
php migrate.php status

# Reset database (caution: destroys all data)
php migrate.php fresh
```

### 2. Configuration
Update database credentials in `/config/database.php`:
```php
const DB_HOST = 'localhost';
const DB_NAME = 'secureit';
const DB_USER = 'root';
const DB_PASS = '';
```

### 3. Testing
```bash
# Test all backend classes
php test.php

# Run API demo
php demo.php
```

### 4. Security Configuration
- Change default encryption keys in production
- Use environment variables for sensitive configuration
- Enable HTTPS for all API endpoints
- Implement rate limiting for authentication endpoints

## Class Architecture

### Core Classes
- **Database**: PDO-based database connection and query handling
- **User**: User management, authentication, profile operations
- **Vault**: Encrypted storage and retrieval of sensitive data
- **EncryptionHelper**: AES-256-GCM encryption/decryption utilities

### Security Classes
- **SecurityManager**: Password analysis, breach checking, security scoring
- **Authenticator**: Two-factor authentication (TOTP) implementation
- **PasswordGenerator**: Secure password/passphrase/username generation

### Utility Classes
- **SendManager**: Temporary encrypted message/file sharing
- **ReportManager**: Security reports and data export functionality

## Security Features

### Encryption
- AES-256-GCM encryption for all sensitive data
- Unique initialization vectors for each encryption operation
- Authentication tags to prevent tampering

### Password Security
- Argon2ID password hashing
- Password strength analysis
- Breach detection via Have I Been Pwned API
- Duplicate password detection

### Two-Factor Authentication
- RFC 6238 compliant TOTP implementation
- QR code generation for authenticator apps
- Backup codes for account recovery

### Access Control
- Session-based authentication
- User isolation (data scoping by user ID)
- Input validation and sanitization

## Development

### Adding New Features
1. Create new migration if database changes are needed
2. Implement business logic in appropriate class
3. Add API endpoint in relevant API file
4. Update documentation

### Migration Commands
```bash
php migrate.php up      # Run pending migrations
php migrate.php down    # Rollback last migration
php migrate.php fresh   # Reset and re-run all migrations
php migrate.php status  # Show migration status
```

### Testing
- Use `test.php` to verify class functionality
- Use `demo.php` to test API endpoints
- All sensitive data is properly encrypted in database
- Session management works correctly

## Dependencies

- PHP 7.4+ with OpenSSL extension
- MySQL 5.7+ or MariaDB 10.2+
- PDO MySQL extension
- cURL extension (for breach checking)

## Production Deployment

1. Use environment variables for configuration
2. Enable HTTPS and HSTS headers
3. Implement proper error logging
4. Set up database backups
5. Use a reverse proxy (nginx/Apache) with rate limiting
6. Monitor for security vulnerabilities
7. Regular security audits and penetration testing

## License

This is a proprietary password manager system. Unauthorized use is prohibited.
