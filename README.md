# Iridium

## User Import API

This is a Laravel-based REST API for batch importing users from CSV files into the database.

---

## Overview

The **User Import API** provides a robust endpoint to import multiple users from a CSV file. The service includes validation, error handling, transaction support, and comprehensive logging for audit trails.

### Key Features

- **Batch CSV Import**: Upload CSV files with user data
- **Data Validation**: Strict validation rules for user fields
- **Transaction Support**: Atomic database operations (all-or-nothing)
- **Error Logging**: Detailed logs for failed records
- **Response Reporting**: Returns detailed error information for records that failed validation

---

## API Endpoint

### Import Users

**Endpoint**: `POST /api/import-users`

**Description**: Imports users from a CSV file into the database.

#### Request

**Method**: `POST`

**URL**: `/api/import-users`

**Content-Type**: `multipart/form-data`

**Request Body**:
```
file: <CSV file>
```

#### CSV File Format

The CSV file must contain the following headers and columns (in any order):

| Column | Type | Constraints | Description |
|--------|------|-----------|-------------|
| `user_id` | String | Required, Unique | Unique identifier for the user |
| `username` | String | Required, Alphanumeric, 3-20 chars | Username for the user account |
| `email` | String | Required, Valid Email, Unique | User's email address |
| `password` | String | Required, 6-20 chars | Plain text password (will be hashed) |

#### Example CSV

```csv
user_id,username,email,password
USR001,john_doe,john@example.com,SecurePass123
USR002,jane_smith,jane@example.com,AnotherPass456
USR003,bob_jones,bob@example.com,BobPassword789
```

#### Validation Rules

- **user_id**: Must be unique and not already exist in the database
- **username**: Must be alphanumeric, between 3-20 characters
- **email**: Must be a valid email format and unique in the database
- **password**: Must be between 6-20 characters

#### Response

**Success (HTTP 200)**:
```json
{
  "message": "imported successfully, below are records which couldn't complete",
  "record": [
    {
      "row": {
        "user_id": "USR002",
        "username": "jane",
        "email": "invalid-email",
        "password": "pass"
      },
      "errors": [
        "The email field must be a valid email.",
        "The password must be at least 6 characters."
      ]
    }
  ]
}
```

**Error (HTTP 500)**:
```json
{
  "message": "Failed to import users",
  "error": "<exception message>"
}
```

#### Response Fields

- `message`: Summary message of the import operation
- `record`: Array of failed records with validation errors
  - `row`: The row data that failed
  - `errors`: Array of validation error messages for that row



## Implementation Details

### Service Architecture

**Controller**: `App\Http\Controllers\UserImportController`
- Validates the uploaded file
- Initiates the import process with database transaction
- Returns formatted response with error details

**Service**: `App\Services\UserImportService`
- Parses CSV file line by line
- Validates each row according to defined rules
- Creates user records with hashed passwords
- Logs import statistics and errors
- Maintains transaction integrity

### Database Transaction Behavior

- All rows are imported within a database transaction
- If any unhandled error occurs, the entire import is rolled back
- Validation errors do NOT cause rollback (failed records are reported but success records are committed)

### Password Handling

Passwords are hashed using Laravel's `Hash::make()` method (BCrypt) before storing in the database. Plain text passwords in the CSV are never stored.

### Logging

The service logs:
- Overall import statistics (total, completed, failed)
- Individual validation failures with row data
- Any exceptions encountered during import

**Log Location**: `storage/logs/laravel.log`

---

## Error Handling

### Common Errors

**1. Invalid File Format**
```json
{
  "message": "Failed to import users",
  "error": "The file field must be a file of type: csv."
}
```

**2. Missing Required Column**
```json
{
  "message": "imported successfully, below are records which couldn't complete",
  "record": [
    {
      "row": {...},
      "errors": [
        "The user_id field is required.",
        "The username field is required."
      ]
    }
  ]
}
```

**3. Duplicate Email/User ID**
```json
{
  "message": "imported successfully, below are records which couldn't complete",
  "record": [
    {
      "row": {
        "user_id": "USR001",
        "username": "john_doe",
        "email": "john@example.com",
        "password": "password123"
      },
      "errors": [
        "The user id has already been taken.",
        "The email has already been taken."
      ]
    }
  ]
}
