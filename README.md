# PHP Private Core

A lightweight, all-in-one PHP core library for building modern websites and robust APIs. It provides essential tools for database management, HTTP request handling, and client-side control—all written in plain PHP without external dependencies.

## ✨ Features

- **Database Management**: Simple PDO-based MySQL operations (CRUD, aggregations, queries)
- **HTTP Utilities**: Request/response handling, JSON/HTML/text output, redirects
- **Security Headers**: Pre-configured security and performance headers
- **Environment Control**: Apache environment variable management
- **Connection Configuration**: CORS, timeouts, and keep-alive settings

## 🚀 Installation

1. **Clone the repository**:
```bash
git clone https://github.com/aminranjibar2007/PrivateCorePhp.git
cd PrivateCorePhp
```

2. **Include in your project**:
```php
require_once 'path/to/core/Core.php';
```

3. **Initialize**:
```php
use core\Core;

$core = new Core("V1.0.0");
$core->setPhpConfigs(); // Apply security and performance settings
```

## 📖 Basic Usage

### Database Connection
```php
$core->connectMySqlDatabase(
    host: 'localhost',
    database: 'myapp',
    username: 'root',
    password: 'password',
    charset: 'utf8mb4'
);
```

### CRUD Operations
```php
// Insert
$core->insert('users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Select
$users = $core->select('users', ['id', 'name'], ['active' => 1]);

// Update
$core->update('users', ['name' => 'Jane Doe'], 'id = ?', [1]);

// Delete
$core->delete('users', 'id = ?', [5]);
```

### Aggregation Methods
```php
$total = $core->count('users');
$avgAge = $core->avg('users', 'age');
$maxScore = $core->max('scores', 'points', 'user_id = ?', [123]);
```

### HTTP Responses
```php
// JSON Response
$core->printJson([
    'success' => true,
    'users' => $users
], 200, "Users retrieved successfully");

// HTML Response
$core->printHtml('<h1>Welcome!</h1>');

// Text Response
$core->printText('Plain text response');

// Redirect
$core->redirect('https://example.com/dashboard');
```

### Request Handling
```php
// Get request method
$method = $core->getRequestMethod(); // GET, POST, etc.

// Get POST body
$rawData = $core->getBodyDataOnPost();

// Set response code
$core->responseSetStatusCode(201);
```

### Environment Variables
```php
$core->setEnv('APP_ENV', 'production');
$environment = $core->getEnv('APP_ENV');
```

### Connection Configuration
```php
$core->configureConnection(
    keep: true,     // Keep-Alive connection
    timeOut: 30     // 30 second timeout
);
```

## 🔧 Available Methods

### Database Methods
- `connectMySqlDatabase()` – Establish MySQL connection
- `insert()` – Insert new records
- `select()` – Query records with conditions
- `update()` – Update existing records
- `delete()` – Delete records
- `count()` – Count records
- `sum()` – Sum column values
- `avg()` – Calculate average
- `min()`/`max()` – Find minimum/maximum
- `query()` – Raw SQL queries
- `get()`/`find()` – Get single record by ID
- `findAll()` – Get all records
- `save()` – Insert or update record

### HTTP Methods
- `printJson()` – JSON response with status and message
- `printHtml()` – HTML response
- `printText()` – Plain text response
- `redirect()` – 301 redirect
- `responseSetStatusCode()` – Set HTTP status code
- `getRequestMethod()` – Get current HTTP method
- `getBodyDataOnPost()` – Get raw POST data

### Configuration Methods
- `setPhpConfigs()` – Apply security and performance settings
- `configureConnection()` – Configure connection parameters
- `setEnv()`/`getEnv()` – Environment variable management
- `coreFlush()` – Flush output buffer
- `coreClose()` – Terminate script

## ⚙️ Configuration

### Security Headers
The library automatically sets:
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- X-Content-Type-Options: nosniff
- Removes X-Powered-By header

### PHP Settings
Default configuration includes:
- Error logging (not displaying)
- Memory limit: 80M
- Execution time: 16 seconds
- OPcache optimization
- Secure session handling

## 📝 Example: Simple API Endpoint

```php
<?php
require_once 'core/Core.php';

use core\Core;

$core = new Core();
$core->setPhpConfigs();
$core->configureConnection(true, 30);

// Connect to database
$core->connectMySqlDatabase('localhost', 'api_db', 'user', 'pass');

// Handle different request methods
switch ($core->getRequestMethod()) {
    case 'GET':
        $users = $core->select('users');
        $core->printJson($users, 200, "Users list");
        break;
        
    case 'POST':
        $data = json_decode($core->getBodyDataOnPost(), true);
        $success = $core->insert('users', $data);
        $core->printJson([], $success ? 201 : 500, $success ? "User created" : "Error");
        break;
        
    default:
        $core->printJson([], 405, "Method not allowed");
}
```

## 🛡️ Error Handling

The library uses PDO exceptions for database errors. For HTTP errors, use the appropriate status codes:

```php
try {
    $core->connectMySqlDatabase(...);
} catch (PDOException $e) {
    $core->printJson(['error' => $e->getMessage()], 500, "Database error");
}

// Custom error responses
if (!$userExists) {
    $core->printJson([], 404, "User not found");
}
```

## 🔄 Extending

The class is marked `final` but you can:
1. Create a wrapper class
2. Use composition instead of inheritance
3. Fork and modify for specific needs

## 📄 License

GNU LNC

---

**Note**: This is a foundational library. Always validate and sanitize user input, implement proper authentication, and follow security best practices in production applications.

Ready to simplify your PHP development? Start building with PHP Private Core today!
