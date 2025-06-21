# Robert CAT Tool - Setup and Testing Guide

## Overview

This guide will help you set up and test the Robert CAT Tool, a Computer-Assisted Translation management system built with PHP and React.

## Prerequisites

- **PHP 8.0+** with PDO and MySQL extensions
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Node.js 16+** and **npm**
- **Web server** (Apache/Nginx) or PHP built-in server
- **Composer** (for PHP dependencies)

## Project Structure

```
robert-php-dev-test/
├── api/
│   └── translations.php          # RESTful API endpoints
├── database/
│   └── schema.sql               # Database schema and sample data
├── docs/
│   └── Architecture.md          # System architecture documentation
├── frontend/
│   ├── public/
│   │   └── index.html           # React app HTML template
│   ├── src/
│   │   ├── App.js               # Main React component
│   │   ├── App.css              # Main app styles
│   │   ├── TranslationList.js   # Translation units list component
│   │   ├── TranslationList.css  # List component styles
│   │   ├── TranslationForm.js   # Add translation form component
│   │   ├── TranslationForm.css  # Form component styles
│   │   └── index.js             # React entry point
│   └── package.json             # React dependencies
├── src/
│   ├── TranslationUnit.php      # Core PHP class
│   └── usage_example.php        # Usage examples
├── tests/
│   └── TranslationUnitTest.php  # PHPUnit tests
├── README.md                    # Project overview
└── SETUP.md                     # This file
```

## Setup Instructions

### 1. Database Setup

1. **Create MySQL database:**
   ```sql
   CREATE DATABASE robert_cat_tool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import the schema:**
   ```bash
   mysql -u your_username -p robert_cat_tool < database/schema.sql
   ```

3. **Verify the setup:**
   ```sql
   USE robert_cat_tool;
   SHOW TABLES;
   SELECT COUNT(*) FROM translation_units;
   ```

### 2. PHP Backend Setup

1. **Configure database connection:**
   Edit the database configuration in `api/translations.php`:
   ```php
   $dbConfig = [
       'host' => 'localhost',
       'dbname' => 'robert_cat_tool',
       'username' => 'your_username',
       'password' => 'your_password',
       'charset' => 'utf8mb4'
   ];
   ```

2. **Set up web server:**
   
   **Option A: Using PHP built-in server (for development):**
   ```bash
   cd robert-php-dev-test
   php -S localhost:8000
   ```
   
   **Option B: Using Apache/Nginx:**
   - Point your web server to the project directory
   - Ensure the `api/` directory is accessible

3. **Test the API:**
   ```bash
   # Test GET endpoint
   curl http://localhost:8000/api/translations.php
   
   # Test POST endpoint
   curl -X POST http://localhost:8000/api/translations.php \
     -H "Content-Type: application/json" \
     -d '{"source_text":"Hello world","source_language":"en","target_language":"es"}'
   ```

### 3. React Frontend Setup

1. **Install dependencies:**
   ```bash
   cd frontend
   npm install
   ```

2. **Start the development server:**
   ```bash
   npm start
   ```
   
   The React app will be available at `http://localhost:3000`

3. **Build for production:**
   ```bash
   npm run build
   ```

## Testing Instructions

### 1. PHP Unit Tests

1. **Install PHPUnit:**
   ```bash
   composer require --dev phpunit/phpunit
   ```

2. **Run the tests:**
   ```bash
   # From the project root
   ./vendor/bin/phpunit tests/TranslationUnitTest.php
   
   # Or if PHPUnit is installed globally
   phpunit tests/TranslationUnitTest.php
   ```

3. **Expected test results:**
   ```
   PHPUnit 9.5.0 by Sebastian Bergmann and contributors.
   
   ...................                                             19 / 19 (100%)
   
   Time: 00:00.123, Memory: 6.00 MB
   
   OK (19 tests, 45 assertions)
   ```

### 2. API Testing

1. **Test all CRUD operations:**
   ```bash
   # 1. Create a translation unit
   curl -X POST http://localhost:8000/api/translations.php \
     -H "Content-Type: application/json" \
     -d '{
       "source_text": "Welcome to our website!",
       "source_language": "en",
       "target_language": "es",
       "context": "Website greeting",
       "segment_type": "sentence"
     }'
   
   # 2. Get all translation units
   curl http://localhost:8000/api/translations.php
   
   # 3. Get specific translation unit (replace {id} with actual ID)
   curl http://localhost:8000/api/translations.php/{id}
   
   # 4. Update translation unit
   curl -X PUT http://localhost:8000/api/translations.php/{id} \
     -H "Content-Type: application/json" \
     -d '{
       "translated_text": "¡Bienvenido a nuestro sitio web!",
       "translation_method": "human",
       "confidence_score": 1.0
     }'
   
   # 5. Delete translation unit
   curl -X DELETE http://localhost:8000/api/translations.php/{id}
   ```

### 3. Frontend Testing

1. **Manual testing:**
   - Open `http://localhost:3000` in your browser
   - Navigate between "Translation Units" and "Add New Unit" views
   - Test adding new translation units
   - Test editing existing translations
   - Test filtering and searching

2. **Automated testing:**
   ```bash
   cd frontend
   npm test
   ```

### 4. Integration Testing

1. **Test the complete workflow:**
   - Add a new translation unit via the React form
   - Verify it appears in the translation list
   - Edit the translation
   - Verify the history is tracked
   - Test filtering by status, language, etc.

2. **Test error handling:**
   - Try to add translation units with invalid data
   - Test API endpoints with missing required fields
   - Verify proper error messages are displayed

## Usage Examples

### PHP Usage Example

Run the usage example to see the TranslationUnit class in action:

```bash
php src/usage_example.php
```

This will demonstrate:
- Adding translation units
- Retrieving units by ID
- Updating translations with history tracking
- Searching and filtering
- Error handling

### API Usage Examples

**Create a new translation unit:**
```bash
curl -X POST http://localhost:8000/api/translations.php \
  -H "Content-Type: application/json" \
  -d '{
    "source_text": "The weather is beautiful today.",
    "source_language": "en",
    "target_language": "es",
    "context": "Weather context",
    "segment_type": "sentence"
  }'
```

**Get translation units with filters:**
```bash
curl "http://localhost:8000/api/translations.php?status=completed&source_language=en&limit=5"
```

**Update a translation:**
```bash
curl -X PUT http://localhost:8000/api/translations.php/1 \
  -H "Content-Type: application/json" \
  -d '{
    "translated_text": "El clima está hermoso hoy.",
    "translation_method": "human",
    "confidence_score": 1.0,
    "change_reason": "Initial translation"
  }'
```

## Troubleshooting

### Common Issues

1. **Database connection errors:**
   - Verify MySQL is running
   - Check database credentials in `api/translations.php`
   - Ensure the database exists

2. **CORS errors in React:**
   - The API includes CORS headers, but ensure your web server is configured correctly
   - Check that the API URL in React components matches your setup

3. **PHP errors:**
   - Ensure PHP has PDO and MySQL extensions enabled
   - Check PHP error logs for detailed error messages

4. **React build errors:**
   - Clear node_modules and reinstall: `rm -rf node_modules && npm install`
   - Check for syntax errors in React components

### Performance Optimization

1. **Database optimization:**
   - Add indexes for frequently queried columns
   - Use connection pooling for high-traffic scenarios

2. **Frontend optimization:**
   - Build the React app for production: `npm run build`
   - Serve static files through a CDN

3. **API optimization:**
   - Implement caching for frequently accessed data
   - Add pagination for large datasets

## Security Considerations

1. **Database security:**
   - Use strong passwords for database access
   - Limit database user permissions
   - Enable SSL for database connections

2. **API security:**
   - Implement authentication and authorization
   - Validate all input data
   - Use HTTPS in production

3. **Frontend security:**
   - Sanitize user inputs
   - Implement CSRF protection
   - Use Content Security Policy headers

## Deployment

### Production Deployment

1. **Backend deployment:**
   - Set up a production web server (Apache/Nginx)
   - Configure SSL certificates
   - Set up database backups
   - Configure environment variables

2. **Frontend deployment:**
   - Build the React app: `npm run build`
   - Serve static files through a web server
   - Configure API proxy or CORS

3. **Monitoring:**
   - Set up error logging
   - Monitor database performance
   - Implement health checks

## Support

For issues and questions:
1. Check the troubleshooting section above
2. Review the architecture documentation in `docs/Architecture.md`
3. Run the test suite to verify functionality
4. Check the usage examples for reference

## License

This project is part of the Robert CAT Tool assessment. Please refer to the main README.md for licensing information. 