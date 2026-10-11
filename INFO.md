
---

> # PART 1 — `database/schema.sql`

---

### Line: `CREATE DATABASE IF NOT EXISTS property_marketplace`

**What it does:** Creates a new database called `property_marketplace`. If it already exists, it skips without error.

**Keywords:**
- `CREATE DATABASE` → SQL command that makes a new database
- `IF NOT EXISTS` → guards against the error that would happen if the database already exists

---

### Line: `CHARACTER SET utf8mb4`

**What it does:** Sets the character encoding for the database to `utf8mb4`.

**Keywords:**
- `CHARACTER SET` → the encoding rules that determine which letters/symbols can be stored
- `utf8mb4` → Unicode Transformation Format, 8-bit, 4-byte — supports every character including emojis and non-English scripts. The "4-byte" part is important because plain `utf8` in MySQL only supports 3 bytes and cannot store emojis

---

### Line: `COLLATE utf8mb4_unicode_ci;`

**What it does:** Sets the collation (sorting and comparison rules). `ci` means case-insensitive.

**Keywords:**
- `COLLATE` → rules for how text is sorted and compared
- `utf8mb4_unicode_ci` → the specific collation. `unicode` means Unicode-based rules, `ci` = case-insensitive
- Case-insensitive → "Ali" and "ali" are treated as equal when comparing

---

### Line: `USE property_marketplace;`

**What it does:** Tells MySQL "all subsequent commands go into this database."

**Keywords:**
- `USE` → switches the active database context for the session

---

### Line: `CREATE TABLE IF NOT EXISTS admins (`

**What it does:** Creates the `admins` table.

**Keywords:**
- `CREATE TABLE` → makes a new table (like a spreadsheet)
- `admins` → the name of the table
- `IF NOT EXISTS` → skip the error if it already exists

---

### Line: `admin_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`

**What it does:** Defines the primary key column for admins.

**Keywords:**
- `INT` → integer data type (whole number)
- `UNSIGNED` → cannot be negative (0 to 4 billion instead of -2 billion to +2 billion)
- `AUTO_INCREMENT` → MySQL automatically assigns the next number (1, 2, 3…) when a row is inserted
- `PRIMARY KEY` → the unique identifier for each row — no duplicates allowed, and it's the fastest way to look up a row

---

### Line: `username VARCHAR(50) NOT NULL UNIQUE,`

**What it does:** Defines a text column for usernames.

**Keywords:**
- `VARCHAR(50)` → variable-length string, max 50 characters. VARCHAR = Variable Character
- `NOT NULL` → must have a value — cannot be empty
- `UNIQUE` → no two rows can have the same value

---

### Line: `password VARCHAR(255) NOT NULL,`

**What it does:** Stores the password hash.

**Keywords:**
- `255` → the standard length because bcrypt hashes are 60 characters, and 255 gives room for algorithm upgrades
- We store hashes, never plain-text passwords

---

### Line: `created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP`

**What it does:** Records when the row was created.

**Keywords:**
- `TIMESTAMP` → date + time data type
- `DEFAULT CURRENT_TIMESTAMP` → if not provided, use the current moment automatically
- `CURRENT_TIMESTAMP` → MySQL's built-in function for the current date/time

---

### Line: `) ENGINE=InnoDB;`

**What it does:** Uses the InnoDB storage engine for the table.

**Keywords:**
- `ENGINE` → the internal storage mechanism for the table
- `InnoDB` → the standard engine that supports transactions, foreign keys, and row-level locking. The alternative (MyISAM) doesn't support foreign keys

---

### Line: `CREATE TABLE IF NOT EXISTS dealers (`

**What it does:** Creates the `dealers` table for dealer accounts.

---

### Line: `dealer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,`

**What it does:** Primary key for dealers.

**Keywords:** Same as `admin_id` — see above.

---

### Line: `email VARCHAR(100) NOT NULL UNIQUE,`

**What it does:** Email must be unique across dealers.

**Keywords:**
- `UNIQUE` → enforced at the database level. Even if PHP misses a duplicate, MySQL rejects it

---

### Line: `whatsapp VARCHAR(20) NULL,`

**What it does:** Optional WhatsApp number.

**Keywords:**
- `NULL` → explicitly allows the column to have no value. This is different from empty string `""`

---

### Line: `full_address TEXT NOT NULL,`

**What it does:** Stores the full address. This is a private, admin-only field.

**Keywords:**
- `TEXT` → like VARCHAR but with a much larger limit (65,535 chars). Used for long content
- Private field → never returned by public API endpoints

---

### Line: `province VARCHAR(100) NOT NULL,`

### Line: `city VARCHAR(100) NOT NULL,`

**What it does:** Two separate location columns.

**Keywords:**
- Why split? Because we search by each independently. A single "location" field can't be filtered by province OR city separately

---

### Line: `verification_status ENUM('pending', 'approved', 'rejected')`

**What it does:** Only three values allowed.

**Keywords:**
- `ENUM` → a data type that restricts values to a predefined list. MySQL enforces it — nothing else can be inserted
- This is a state machine in the DB — the dealer can only be in one of three states

---

### Line: `updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,`

**What it does:** Auto-updates the timestamp whenever the row changes.

**Keywords:**
- `ON UPDATE CURRENT_TIMESTAMP` → MySQL automatically refreshes this column on any UPDATE query

---

### Line: `INDEX idx_dealers_province (province),`

**What it does:** Creates a database index on the `province` column.

**Keywords:**
- `INDEX` → a lookup structure (like a book's index) that speeds up queries filtering on that column
- `idx_dealers_province` → the name of the index
- Trade-off: faster SELECTs, slightly slower INSERTs (because the index must be updated)

---

### Line: `properties` table — key lines

```sql
property_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
```
**What:** Primary key.

```sql
dealer_id INT UNSIGNED NOT NULL,
```
**What:** Foreign key column pointing to `dealers.dealer_id`.
**Keywords:**
- Foreign key column → a column whose values must match the primary key of another table
- It's not called a "foreign key" until the CONSTRAINT is defined below

```sql
price DECIMAL(15,2) NOT NULL,
```
**What:** Price with 15 digits total, 2 decimal places.
**Keywords:**
- `DECIMAL(15,2)` → exact numeric type. 13 digits before decimal, 2 after
- **Why not FLOAT?** → FLOAT uses binary floating point and can produce rounding errors (0.1 + 0.2 = 0.30000000004). DECIMAL is exact — critical for money

```sql
property_status ENUM('unsold', 'sold') NOT NULL DEFAULT 'unsold',
```
**What:** Only two states.

```sql
views INT UNSIGNED NOT NULL DEFAULT 0,
```
**What:** View counter starts at 0.

```sql
CONSTRAINT fk_properties_dealer
    FOREIGN KEY (dealer_id)
    REFERENCES dealers(dealer_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
```
**What:** Creates the foreign key relationship.
**Keywords:**
- `CONSTRAINT` → names the rule so you can refer to it later (e.g., to drop it)
- `fk_properties_dealer` → the constraint name (convention: `fk_<child>_<parent>`)
- `FOREIGN KEY` → declares that `dealer_id` refers to another table
- `REFERENCES` → the target (parent) table and column
- `ON UPDATE CASCADE` → if the parent's ID changes, the child's foreign key auto-updates
- `ON DELETE CASCADE` → if the parent is deleted, all child rows are auto-deleted. This is the "cascade delete" pattern

```sql
INDEX idx_properties_dealer_id (dealer_id),
```
**What:** Index on the foreign key.
**Keywords:**
- Why index foreign keys? → "Find all properties for dealer X" becomes fast. Without it, MySQL scans the entire table

---

### Line: `property_images` table

```sql
image_path VARCHAR(255) NOT NULL,
```
**What:** Stores the URL or relative path to the image.
**Keywords:**
- Polymorphic field → can hold either a local path (`uploads/...`) or a full URL (`https://...`). The `imageUrl()` function in the frontend handles both

```sql
CONSTRAINT fk_property_images_property
    FOREIGN KEY (property_id)
    REFERENCES properties(property_id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
```
**What:** Cascade chain — deleting a property auto-deletes its images.
**Keywords:**
- Cascade chain → Dealer → Properties → Property Images. Delete the dealer, everything below is gone

---

# PART 2 — Backend Files

---

## 2.1 `backend/config/config.php`

### Line: `<?php`

**What it does:** Opening PHP tag. Everything after this is treated as PHP code.
**Keywords:**
- `<?php` → must be the very first line — any whitespace before it gets sent to the browser and can break JSON output

---

### Line: `require_once __DIR__ . '/secrets.php';`

**What it does:** Loads the secrets file once.

**Keywords:**
- `require_once` → load this file; if it's already loaded, skip. If the file is missing, fatal error
- `__DIR__` → a "magic constant" that resolves to the absolute directory of the current file. Unlike `dirname(__FILE__)`, it doesn't hit the filesystem
- `.` → the dot operator for string concatenation in PHP

---

### Line: `define('ADMIN_CONTACT_NUMBER', '+923466053334');`

**What it does:** Defines a global constant.

**Keywords:**
- `define('NAME', value)` → creates a constant that cannot be redefined or changed
- Constants vs variables → constants have no `$` prefix and are global by default

---

## 2.2 `backend/config/session.php`

### Line: `if (session_status() === PHP_SESSION_NONE) {`

**What it does:** Checks if a session hasn't been started yet.

**Keywords:**
- `session_status()` → built-in function returning the current session state
- `PHP_SESSION_NONE` → the constant value that means "no session active"
- `===` → strict comparison (value AND type must match). Safer than `==`

---

### Line: `ini_set('session.cookie_httponly', 1);`

**What it does:** Makes the session cookie unreadable by JavaScript.

**Keywords:**
- `ini_set()` → changes a PHP configuration setting at runtime
- `session.cookie_httponly` → the config directive name. Setting it to `1` means "yes, enable HttpOnly"
- **HttpOnly** → a cookie flag that blocks `document.cookie` from reading it. Prevents XSS-based session theft

---

### Line: `ini_set('session.cookie_samesite', 'Lax');`

**What it does:** Cookie is not sent on cross-site requests.

**Keywords:**
- **SameSite** → modern browser cookie flag
- `Lax` → cookie is sent on top-level navigations (clicking a link) but not on cross-site AJAX/fetch requests
- Alternative values: `Strict` (never cross-site), `None` (always, requires Secure)

---

### Line: `ini_set('session.use_strict_mode', 1);`

**What it does:** Rejects uninitialized session IDs.

**Keywords:**
- **Session fixation** → attack where the attacker sets a known session ID, then tricks the victim into using it. Strict mode rejects any session ID that wasn't created by the server
- Session fixation mitigation

---

### Line: `ini_set('session.use_only_cookies', 1);`

**What it does:** Session ID only via cookies, never via URL.

**Keywords:**
- Historically PHP allowed `?PHPSESSID=abc123` in URLs. This is insecure (leaks in browser history, server logs). This directive disables that

---

### Line: `if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {`

**What it does:** Checks if the current request came over HTTPS.

**Keywords:**
- `$_SERVER` → superglobal array containing request/server info
- `$_SERVER['HTTPS']` → set to `"on"` or `"1"` if HTTPS, otherwise unset
- `empty()` → returns true if the value doesn't exist OR is `""`, `0`, `null`, or `false`

---

### Line: `ini_set('session.cookie_secure', 1);`

**What it does:** Marks the cookie Secure — browser only sends it over HTTPS.

**Keywords:**
- **Secure flag** → prevents the cookie from being transmitted over plain HTTP. Critical on live sites

---

## 2.3 `backend/classes/Database.php`

### Line: `class Database`

**What it does:** Declares a class.

**Keywords:**
- `class` → a blueprint for objects
- **OOP** → Object-Oriented Programming — organizing code into reusable classes

---

### Line: `private string $host = DB_HOST;`

**What it does:** Declares a typed property.

**Keywords:**
- `private` → visibility. Only code inside this class can access it. Alternatives: `public`, `protected`
- `string` → type declaration (PHP 7.4+). Guarantees this property is always a string
- `$host` → the property name (variable inside a class)
- `DB_HOST` → a constant from config.php

---

### Line: `private ?PDO $connection = null;`

**What it does:** Property that can hold either a PDO object or null.

**Keywords:**
- `?PDO` → nullable type. The `?` prefix allows null in addition to the declared type
- `PDO` → PHP Data Objects — the class for database access
- Starts as null — we create the connection lazily

---

### Line: `public function getConnection(): PDO`

**What it does:** Method that returns a PDO.

**Keywords:**
- `public function` → visibility + function definition
- `(): PDO` → return type declaration — this method MUST return a PDO object, or PHP throws an error
- This is a form of self-documenting code

---

### Line: `if ($this->connection === null) {`

**What it does:** Only create the connection if not already created.

**Keywords:**
- `$this->connection` → refers to the current object's property
- Lazy initialization → don't create the expensive resource until needed

---

### Line: `try {`

**What it does:** Begins a block where exceptions may occur.

**Keywords:**
- **Exception** → an error object thrown by code
- `try...catch` → pattern for handling errors without crashing the app

---

### Line: `$this->connection = new PDO(`

**What it does:** Creates a new PDO connection.

**Keywords:**
- `new` → keyword to instantiate a class
- `PDO(...)` → the class constructor

---

### Line: `"mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",`

**What it does:** The Data Source Name (DSN) string.

**Keywords:**
- **DSN** → a connection string that tells PDO which driver, host, database, and charset to use
- `mysql:` → the PDO driver
- `{$this->host}` → string interpolation — PHP replaces this with the property value inside a double-quoted string

---

### Line: `$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);`

**What it does:** Makes PDO throw exceptions on errors.

**Keywords:**
- `setAttribute()` → configures a PDO option
- `PDO::ATTR_ERRMODE` → the "error handling" setting
- `PDO::ERRMODE_EXCEPTION` → throw exceptions instead of silent failures or warnings
- The `::` is PHP's "scope resolution operator" for accessing class constants

---

### Line: `} catch (PDOException $e) {`

**What it does:** Catches database errors.

**Keywords:**
- `catch` → handles the exception from the try block
- `PDOException` → the specific exception class PDO throws
- `$e` → the exception object

---

### Line: `throw new Exception("Database connection failed.");`

**What it does:** Throws a generic error.

**Keywords:**
- `throw` → raises a new exception
- Why generic? → Never expose the real DB error to the client. It could reveal table names, credentials, or internals

---

## 2.4 `backend/classes/Dealer.php`

### Line: `class Dealer`

**What it does:** Class for dealer-related DB operations.

---

### Line: `private PDO $db;`

**What it does:** Stores the PDO connection.

---

### Line: `public function __construct(PDO $db)`

**What it does:** Constructor — runs automatically when `new Dealer($pdo)` is called.

**Keywords:**
- `__construct` → PHP's magic method for object initialization
- **Dependency Injection** → the caller provides the PDO. The class doesn't create its own. This makes the class testable (you can pass a fake PDO in tests)

---

### Line: `public function usernameExists(string $username): bool`

**What it does:** Returns true/false depending on whether the username exists.

**Keywords:**
- `bool` → return type
- `string $username` → parameter with type hint

---

### Line: `$sql = "SELECT dealer_id FROM dealers WHERE username = :username LIMIT 1";`

**What it does:** SQL query with a named placeholder.

**Keywords:**
- `:username` → a named placeholder (not the actual value). Prevents SQL injection
- `LIMIT 1` → stop after finding one row — faster because the DB doesn't scan the whole table

---

### Line: `$stmt = $this->db->prepare($sql);`

**What it does:** Prepares the SQL.

**Keywords:**
- **Prepared statement** → the DB parses and plans the query with placeholders, then values are sent separately. This means user input can never be interpreted as SQL
- SQL injection defense

---

### Line: `$stmt->execute([':username' => $username]);`

**What it does:** Binds the value and runs the query.

**Keywords:**
- `execute([...])` → associative array mapping placeholders to values

---

### Line: `return $stmt->fetch() !== false;`

**What it does:** Returns true if a row was found, false otherwise.

**Keywords:**
- `fetch()` → returns the next row as an array, or `false` if none
- `!== false` → strict inequality — correctly handles edge cases like an empty array

---

### Line: `public function create(array $data): int`

**What it does:** Inserts a new dealer.

**Keywords:**
- `array $data` → parameter typed as array
- `: int` → returns the new ID as an integer

---

### Line: `return (int) $this->db->lastInsertId();`

**What it does:** Returns the auto-generated ID from the last INSERT.

**Keywords:**
- `lastInsertId()` → PDO method that returns the AUTO_INCREMENT value from the last INSERT
- `(int)` → type cast — convert string to integer

---

## 2.5 `backend/classes/Admin.php`

### Line: `public function verifyPassword(string $password, string $hashedPassword): bool`

**What it does:** Checks a plain password against a hash.

**Keywords:**
- `password_verify()` → PHP function that hashes the input with the same algorithm and salt, then compares. This is a constant-time comparison resistant to timing attacks

---

## 2.6 `backend/classes/Auth.php`

### Line: `public function findDealerByUsername(string $username): ?array`

**What it does:** Returns dealer row or null.

**Keywords:**
- `?array` → nullable array return type

---

### Line: `return $dealer ?: null;`

**What it does:** Return the dealer or null.

**Keywords:**
- `?:` → elvis operator — if the left side is truthy, return it; otherwise return the right side
- `false` is falsy → if `fetch()` returned `false`, we return `null` instead

---

## 2.7 `backend/classes/Property.php`

### Line: `public function create(int $dealerId, array $data): int`

**What it does:** Inserts a new property.

**Keywords:**
- Hardcoded `'unsold'` in SQL → the caller cannot override the initial status. Business rule enforced at the data layer

---

### Line: `public function update(int $propertyId, int $dealerId, array $data): bool`

**What it does:** Updates a property if the dealer owns it.

**Keywords:**
- The `WHERE property_id = :id AND dealer_id = :dealer_id` clause enforces ownership at the SQL level. Even if a caller tries to update someone else's property, the WHERE clause fails to match and no rows change

---

### Line: `return $stmt->rowCount() > 0;`

**What it does:** Returns true if at least one row was updated.

**Keywords:**
- `rowCount()` → number of rows affected by the last statement

---

### Line: `public function findAllWithDealer(): array`

**What it does:** Admin-only. Joins properties with dealers.

**Keywords:**
- `INNER JOIN` → combines rows from two tables where the join condition matches
- `AS` → aliasing a column (`d.username AS dealer_username`). Renames the column in the result so it doesn't clash with `p.username`

---

## 2.8 `backend/middleware/require-dealer.php`

### Line: `require_once __DIR__ . '/../config/session.php';`

**What it does:** Loads session hardening.

**Keywords:**
- `..` → parent directory. This file is in `middleware/`, so `../config/` goes up one level then into `config/`

---

### Line: `if (session_status() === PHP_SESSION_NONE) { session_start(); }`

**What it does:** Starts the session if not already started.

**Keywords:**
- `session_start()` → loads the session data from the cookie ID, or creates a new session

---

### Line: `if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dealer' || !isset($_SESSION['dealer_id']))`

**What it does:** Checks all three conditions for a valid dealer session.

**Keywords:**
- `$_SESSION` → superglobal array holding session data
- `isset()` → returns true if the variable exists and isn't null
- `||` → logical OR — the whole condition is true if ANY part fails
- `!` → logical NOT

---

### Line: `http_response_code(401);`

**What it does:** Sets the HTTP status code to 401.

**Keywords:**
- `401 Unauthorized` → standard code for "you must log in"
- `403 Forbidden` → used when logged in but not allowed (different from 401)

---

### Line: `echo json_encode([...]); exit;`

**What it does:** Returns JSON error and stops.

**Keywords:**
- `json_encode()` → converts a PHP array to a JSON string
- `exit` → terminates the script immediately. Nothing after it runs. This is critical — without it, the protected endpoint code below would still execute

---

## 2.9 `backend/middleware/require-admin.php`

Same as `require-dealer.php` but checks `role === 'admin'` and `admin_id`.
**Keywords:**
- **RBAC** (Role-Based Access Control) → permissions based on the user's role

---

## 2.10 `backend/api/auth/register-dealer.php`

### Line: `header('Content-Type: application/json');`

**What it does:** Sets the response Content-Type header.

**Keywords:**
- `header()` → sends an HTTP header. Must be called before any output
- `Content-Type` → tells the client what format the response is
- `application/json` → MIME type for JSON

---

### Line: `if ($_SERVER['REQUEST_METHOD'] !== 'POST') {`

**What it does:** Allows only POST requests.

**Keywords:**
- `$_SERVER['REQUEST_METHOD']` → the HTTP method (`GET`, `POST`, `PUT`, etc.)
- REST convention → different methods for different actions

---

### Line: `http_response_code(405);`

**What it does:** Returns "Method Not Allowed."

**Keywords:**
- `405` → HTTP status for "you used the wrong HTTP method"

---

### Line: `$input = json_decode(file_get_contents('php://input'), true);`

**What it does:** Reads the raw request body and decodes JSON.

**Keywords:**
- `file_get_contents()` → reads a file's contents into a string
- `php://input` → a special stream that represents the raw POST body
- `json_decode($str, true)` → parses JSON. The `true` makes it return an associative array instead of stdClass objects

---

### Line: `if (!is_array($input)) {`

**What it does:** Validates that decoding succeeded.

**Keywords:**
- `is_array()` → PHP function that checks the type

---

### Line: `$username = trim($input['username'] ?? '');`

**What it does:** Safely extracts the username.

**Keywords:**
- `trim()` → removes leading/trailing whitespace
- `??` → null coalescing operator. Returns the left value if it exists and isn't null, otherwise the right value
- `$input['username'] ?? ''` → "the username if it exists, otherwise empty string"

---

### Line: `foreach ($requiredFields as $field => $value) {`

**What it does:** Loops through each required field.

**Keywords:**
- `foreach` → iterates over arrays
- `$field => $value` → gets both key and value

---

### Line: `if ($value === '') {`

**What it does:** Checks for empty string.

**Keywords:**
- `===` → strict comparison (both type and value match)

---

### Line: `if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {`

**What it does:** Regex validation of the username.

**Keywords:**
- `preg_match()` → PHP function for regex matching
- `/.../ ` → regex delimiters
- `^` → start of string
- `[A-Za-z0-9_]` → character class — any letter, digit, or underscore
- `{3,50}` → quantifier — 3 to 50 repetitions
- `$` → end of string

---

### Line: `if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {`

**What it does:** Built-in email validation.

**Keywords:**
- `filter_var()` → PHP function for validating/filtering values
- `FILTER_VALIDATE_EMAIL` → the built-in email rule
- Better than a custom regex because it follows RFC standards

---

### Line: `$hashedPassword = password_hash($password, PASSWORD_DEFAULT);`

**What it does:** Hashes the password.

**Keywords:**
- `password_hash()` → one-way hashing function
- `PASSWORD_DEFAULT` → PHP chooses the strongest available algorithm (currently bcrypt)
- Never reversible — you cannot decrypt a hash

---

### Line: `http_response_code(409);`

**What it does:** Returns 409 Conflict for duplicate username/email.

**Keywords:**
- `409 Conflict` → used when the request conflicts with the current state (e.g., duplicate key)

---

### Line: `http_response_code(201);`

**What it does:** Returns 201 Created after successful insert.

**Keywords:**
- `201 Created` → the standard success code for creating a new resource

---

## 2.11 `backend/api/auth/login-dealer.php`

### Line: `session_start(); session_regenerate_id(true);`

**What it does:** Starts the session and generates a fresh ID.

**Keywords:**
- `session_regenerate_id(true)` → replaces the current session ID with a new one and deletes the old session file. The `true` argument means "delete old session data too"
- This is a session fixation defense — even if an attacker knew the previous session ID, they can't use it now

---

### Line: `$_SESSION['dealer_id'] = (int) $dealer['dealer_id'];`

**What it does:** Stores the dealer's ID in the session.

**Keywords:**
- `$_SESSION['key'] = value` → writes to the session
- Session data is stored on the server — only the session ID goes to the browser

---

### Line: `if ($dealer['verification_status'] === 'pending') {`

**What it does:** Blocks pending dealers.

**Keywords:**
- `403 Forbidden` → correct HTTP code for "you're logged in but not allowed yet"

---

## 2.12 `backend/api/auth/login-admin.php`

### Line: `sleep(1);`

**What it does:** Pauses for 1 second before responding.

**Keywords:**
- `sleep(n)` → pauses PHP execution for n seconds
- Why? → slows down brute-force attacks. 1000 attempts = 16+ minutes
- Timing attack mitigation → attacker can't tell "user not found" from "wrong password" by measuring response time

---

## 2.13 `backend/api/auth/logout.php`

### Line: `$_SESSION = [];`

**What it does:** Clears all session variables.

**Keywords:**
- `[]` → empty array literal — overwrites the session with an empty array

---

### Line: `setcookie(session_name(), '', time() - 42000, ...);`

**What it does:** Tells the browser to delete the session cookie.

**Keywords:**
- `setcookie()` → sends a Set-Cookie header
- `session_name()` → returns the current session cookie name (usually `PHPSESSID`)
- Setting the value to `''` and expiry to the past → browser deletes the cookie

---

### Line: `session_destroy();`

**What it does:** Destroys the session on the server.

**Keywords:**
- `session_destroy()` → deletes the session file on the server. Once this runs, the session ID can't be used again

---

## 2.14 `backend/api/public/list-properties.php`

### Line: `$where = ["d.verification_status = 'approved'"];`

**What it does:** Base WHERE clause — only approved dealers.

**Keywords:**
- **Dynamic SQL** → building the query programmatically based on input
- Admin-only fields like `full_address` are never selected → privacy by default

---

### Line: `$tokens = array_filter(array_map('trim', explode(',', $q)));`

**What it does:** Splits the search query into tokens.

**Keywords:**
- `explode(',', $q)` → split string by comma into array
- `array_map('trim', $arr)` → apply `trim` to every element
- `array_filter($arr)` → remove empty entries
- These are functional programming helpers

---

### Line: `$where[] = "(p.province LIKE {$key} OR p.city LIKE {$key})";`

**What it does:** Adds an OR condition to search both province and city.

**Keywords:**
- `[]` after a variable → append to array
- `LIKE` → SQL pattern matching
- `%token%` → wildcards: match anywhere in the string

---

### Line: `$params[$key] = '%' . $token . '%';`

**What it does:** Binds the wildcard-wrapped token.

**Keywords:**
- `%` → SQL wildcard meaning "any characters"
- Building the value outside the SQL means the wildcards are treated as data, not SQL

---

### Line: `$sql = "... WHERE " . implode(' AND ', $where) . "...";`

**What it does:** Joins all WHERE fragments with `AND`.

**Keywords:**
- `implode(' AND ', $where)` → join array elements with `" AND "` between them
- Dynamic query assembly → the final SQL is built at runtime

---

### Line: `foreach ($rows as &$row) { ... } unset($row);`

**What it does:** Modifies rows by reference.

**Keywords:**
- `&$row` → pass by reference — modifying `$row` modifies the original element in the array
- **`unset($row)`** → this is critical. In PHP, after a `foreach` by reference, `$row` still points to the last element. `unset()` breaks that reference, preventing bugs in later code

---

## 2.15 `backend/api/public/get-property.php`

### Line: `if (isset($_GET['count_view']) && $_GET['count_view'] === '1') {`

**What it does:** Only increments the view counter when told to.

**Keywords:**
- `$_GET` → superglobal array with URL query string values
- Why the flag? → browsers send multiple requests per page load (prefetch, reload). Deduplication is done on the frontend with `sessionStorage` — only the first visit per tab sends `?count_view=1`

---

### Line: `$pdo->prepare("UPDATE properties SET views = views + 1 WHERE property_id = :id")->execute([...]);`

**What it does:** Atomically increments the view count.

**Keywords:**
- `views = views + 1` → atomic increment on the DB side
- Atomic → concurrent requests won't lose counts (no read-modify-write race)

---

## 2.16 `backend/api/public/list-locations.php`

### Line: `SELECT DISTINCT p.province FROM ...`

**What it does:** Returns unique provinces.

**Keywords:**
- `DISTINCT` → removes duplicates from the result
- Used for the autocomplete `<datalist>` on the search bar

---

## 2.17–2.32 — Dealer and Admin endpoints

These follow the same patterns as above:
- **Auth middleware** at the top (`require-dealer` or `require-admin`)
- **Method check** (POST or GET)
- **Input validation**
- **Business logic** via class methods
- **JSON response** with proper status code

### `upload-property-image.php` — critical lines:

```php
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
```
**What:** Reads the actual MIME type of the file.
**Keywords:**
- `finfo` → the Fileinfo class
- `FILEINFO_MIME_TYPE` → the constant that says "return just the MIME type"
- **Never trust `$_FILES['image']['type']`** — that comes from the client and can be spoofed. `finfo` reads the file's actual bytes

```php
$filename = bin2hex(random_bytes(8)) . '.' . $ext;
```
**What:** Generates a random filename.
**Keywords:**
- `random_bytes(8)` → 8 cryptographically secure random bytes
- `bin2hex()` → converts binary bytes to hex string (16 chars)
- Why random? → prevents filename collisions and guessing attacks

```php
move_uploaded_file($file['tmp_name'], $targetPath);
```
**What:** Moves the temp file to its final location.
**Keywords:**
- `$_FILES['image']['tmp_name']` → the temp path where PHP stored the upload
- `move_uploaded_file()` → the ONLY safe way to move an uploaded file. It verifies the file was actually uploaded (prevents tricking PHP into moving arbitrary files)

---

# PART 3 — Frontend File Purposes

(Same as before — brief)

| File | Purpose |
|---|---|
| `style.css` | Design system + all page styles |
| `api.js` | Global API helper object |
| `partials.js` | Injects navbar + footer |
| `navbar-*.html` | Three navbar variants |
| `footer.html` | Shared footer |
| 14 HTML pages | (see previous list) |

---

# PART 4 — `api.js` — Function by Function with Keywords

### `function base(path)`

```javascript
if (!path.startsWith('/')) path = '/' + path;
```
**Keywords:**
- `startsWith()` → string method
- `!` → logical NOT

### `async function get(path)`

```javascript
const res = await fetch(base(path), {
    method: 'GET',
    credentials: 'include',
    headers: { 'Accept': 'application/json' }
});
```
**Keywords:**
- `async` → marks a function that returns a Promise
- `await` → pauses execution until the Promise resolves
- `fetch()` → built-in HTTP client
- `credentials: 'include'` → send cookies with the request
- `headers` → HTTP headers to send

### `async function post(path, body)`

```javascript
body: JSON.stringify(body || {})
```
**Keywords:**
- `JSON.stringify()` → converts a JS object to a JSON string
- `||` → fallback (use empty object if `body` is undefined)

### `async function parseResponse(res)`

```javascript
try { data = await res.json(); }
catch (_) { data = { success: false, ... }; }
data.httpStatus = res.status;
```
**Keywords:**
- `res.json()` → reads the response body and parses as JSON. Returns a Promise
- `res.status` → HTTP status code
- `catch (_)` → the underscore is a convention meaning "I don't use this argument"
- Graceful degradation → never let a parse failure crash the page

### `function getQuery(name)`

```javascript
const params = new URLSearchParams(window.location.search);
return params.get(name);
```
**Keywords:**
- `URLSearchParams` → modern class for parsing query strings
- `window.location.search` → the `?query=string` part of the URL

### `function escapeHTML(str)`

```javascript
return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    ...
```
**Keywords:**
- `String(str)` → ensures we're working with a string
- `.replace()` → string method (regex, replacement)
- `/&/g` → regex with `g` flag = global (all occurrences)
- XSS defense → encoding `<` as `&lt;` prevents the browser from interpreting it as a tag

### `function formatPrice(value)`

```javascript
const formatted = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 }).format(num);
return 'Rs. ' + formatted;
```
**Keywords:**
- `Intl.NumberFormat('en-IN', ...)` → locale-aware number formatter
- `'en-IN'` → Indian English locale, which uses the lakh/crore system (1,20,00,000)
- `maximumFractionDigits: 0` → no decimals in output

### `function imageUrl(path)`

```javascript
if (path.startsWith('http')) return path;
if (path.startsWith('/')) return path;
return '/' + path;
```
**Keywords:**
- Polymorphic → handles both local paths and full URLs
- Handles migration: old DB rows have local paths, new ones have ImgBB URLs. Both render correctly

### `function showToast(message, type)`

```javascript
toast._hideTimer = setTimeout(function () { toast.style.display = 'none'; }, 3000);
```
**Keywords:**
- `setTimeout(callback, ms)` → runs a function after a delay
- `_hideTimer` → the underscore prefix conventionally means "internal/private"
- Storing the timer ID lets you cancel it later with `clearTimeout`

### `async function requireAuth(role)`

```javascript
const endpoint = role === 'admin' ? '/admin/my-profile.php' : '/dealer/my-profile.php';
...
if (!result.success || result.httpStatus === 401) {
    redirect(loginPage);
    return null;
}
```
**Keywords:**
- Ternary `? :` → short if/else
- Page guard → this is the frontend equivalent of backend middleware. It's a UX convenience — real security is on the backend

### `async function logout(role)`

```javascript
try { await post('/auth/logout.php', {}); } catch (_) { /* ignore */ }
redirect(role === 'admin' ? ADMIN_LOGIN_PAGE : DEALER_LOGIN_PAGE);
```
**Keywords:**
- `catch (_)` → swallow the error if logout fails. The user wants to log out regardless

### `function setFieldError(inputEl, message)`

```javascript
inputEl.classList.add('is-invalid');
```
**Keywords:**
- `classList.add()` → DOM class manipulation
- `is-invalid` → a Bootstrap class that shows red border + feedback

### `global.API = { ... }`

```javascript
global.API = { base: base, get: get, ... };
```
**Keywords:**
- `global` → here, `window` (the browser's global object)
- Exposes the object so any page can use `API.get(...)` etc.

---

# PART 5 — Other Stuff

---

## `.htaccess` (three files)

**Purpose:** Apache config files. Rules that run before PHP.

**Keywords:**
- `.htaccess` → "hypertext access" — per-directory Apache config
- Executed per request → every request hits these rules
- Only works on Apache (not Nginx, not PHP's built-in dev server)

### `Require all denied`

**What it does:** Blocks all requests to the folder.
**Keywords:**
- `Require all denied` → Apache 2.4 syntax
- `Order Deny,Allow` / `Deny from all` → Apache 2.2 syntax (legacy fallback)

### `Header set X-Content-Type-Options "nosniff"`

**What it does:** Prevents MIME sniffing.
**Keywords:**
- **MIME sniffing** → browsers guessing a file's type by content. If a `.txt` file contained HTML, the browser might render it as HTML
- `nosniff` → disables this guessing

### `Header set X-Frame-Options "SAMEORIGIN"`

**What it does:** Prevents your site from being embedded in iframes on other sites.
**Keywords:**
- **Clickjacking** → attacker embeds your site in a transparent iframe over a fake button
- `SAMEORIGIN` → only allow framing from the same origin

### `Header set Referrer-Policy "strict-origin-when-cross-origin"`

**What it does:** Controls how much referrer info is sent to external sites.
**Keywords:**
- **Referrer** → the URL of the page that made the request
- Prevents leaking full URLs (which might contain sensitive query strings) to external sites

---

## `robots.txt`

**Purpose:** Tells search engine crawlers which paths NOT to index.

**Keywords:**
- `User-agent: *` → apply to all crawlers
- `Disallow: /path/` → tell crawlers not to index this path
- **Polite request only** → malicious bots ignore it. Real security is `.htaccess` + middleware

---

## `.gitignore`

**Purpose:** Tells Git which files to never track.

**Keywords:**
- Every line is a pattern
- `secrets.php` → real credentials
- `uploads/` → user data (regenerated on server)
- `.vscode/` → personal settings
- `*.log` → logs
- Protects against accidentally committing secrets

---

# PART 6 — Additional Concepts to Know

---

## Session vs Cookie

**Session:**
- Stored on the server
- Identified by a session ID
- Cannot be read by the client

**Cookie:**
- Stored in the browser
- Sent with every request
- Can be marked HttpOnly (JS can't read it) and Secure (only HTTPS)

**Our pattern:** Server stores session data, browser holds only `PHPSESSID` cookie.

---

## Authentication vs Authorization

**Authentication** → "Who are you?" — happens at login
**Authorization** → "What can you do?" — happens at middleware

Both are needed. Login alone isn't enough.

---

## SQL Injection Example

**Vulnerable:**
```php
$sql = "SELECT * FROM dealers WHERE username = '$username'";
```
If `$username = "' OR '1'='1"`, the query becomes:
```sql
SELECT * FROM dealers WHERE username = '' OR '1'='1'
```
This returns ALL dealers.

**Safe:**
```php
$stmt = $pdo->prepare("SELECT * FROM dealers WHERE username = :username");
$stmt->execute([':username' => $username]);
```
The placeholder means the DB treats the value as data only.

---

## Password Hashing

- **One-way** — cannot be reversed
- **Salted** — the same password produces different hashes for different users
- `password_hash()` → creates the hash
- `password_verify()` → compares plain vs hash

---

## Foreign Key Cascade

```sql
ON DELETE CASCADE
```
Delete the parent → children auto-delete. Enforced at the DB level, not in code.

---

## HTTP Status Codes

| Code | Meaning |
|---|---|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (not logged in) |
| 403 | Forbidden (logged in but denied) |
| 404 | Not Found |
| 405 | Method Not Allowed |
| 409 | Conflict |
| 500 | Server Error |

---

## Dependency Injection

Pass dependencies INTO a class rather than creating them inside:

```php
$dealer = new Dealer($pdo);
```

**Benefits:**
- Testable (pass a fake PDO)
- Decoupled (class doesn't hardcode DB creation)
- Reusable

---

## Middleware Pattern

A file included at the top of an endpoint that runs BEFORE the endpoint logic:

```php
require_once __DIR__ . '/../../middleware/require-dealer.php';
```

- If unauthorized → 401, exit
- If authorized → continue

Same pattern used by Laravel, Express, Django.

---

## XSS Prevention

**Attack:** `<script>alert('hacked')</script>` as a property title
**Defense:** Escape before injecting into HTML. `API.escapeHTML()` converts `<` to `&lt;`

---

## CSRF Prevention

**Attack:** Malicious site triggers actions on your site using the victim's session
**Defense:** `SameSite=Lax` cookie flag

---

## Session Fixation

**Attack:** Attacker sets a known session ID, tricks victim into using it
**Defense:** `session_regenerate_id(true)` after login

---

## Prepared Statements

The DB parses the SQL **before** values are sent. Values can never be interpreted as SQL. This is the correct way to prevent injection — never build SQL by string concatenation with user input.

---



# Option A — Line-by-Line with "Why"

I'll do these in pairs. This response covers `Auth.php` + `login-dealer.php` — the two files that define how dealer authentication works.

Read slowly. The **Why** is what interviewers ask about.

---

# File 1 — `backend/classes/Auth.php`

## Purpose of the file

This file handles **dealer authentication**. It's the only class that knows how to look up a dealer by username and verify a password against the stored hash.

**Why a separate class?** Because both `login-dealer.php` and any future endpoint that needs to verify dealer credentials will use it. Centralizing authentication logic means one place to fix bugs or add features (like two-factor auth later).

---

### Line: `<?php`

**Does:** Opens the PHP code block.

**Why:** Every PHP file must start with this tag. Anything before it (including whitespace) would be sent to the browser as plain text — which would break JSON responses.

**Keyword:** PHP opening tag.

---

### Line: (blank line)

**Does:** Nothing. Just visual spacing.

**Why:** Readability. Grouping things visually helps whoever reads the file next.

---

### Line: `class Auth`

**Does:** Declares a class named `Auth`.

**Why:** "Auth" is short for authentication. Naming it clearly tells anyone reading the code: *this class deals with login/verification logic.*

**Keyword:** class declaration, naming convention.

---

### Line: `{`

**Does:** Opens the class body.

**Why:** PHP uses braces `{}` to group code blocks.

---

### Line: `    private PDO $db;`

**Does:** Declares a private property to hold the PDO database connection.

**Why:**
- `private` → **Encapsulation**. Outside code can't touch this directly. Only methods inside `Auth` can use it.
- `PDO` → typed as a PDO object (PHP 7.4+ typed properties). If we accidentally assign a string here, PHP throws an error immediately.
- `$db` → the property name.

**Keywords:** encapsulation, visibility, typed property, dependency.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    public function __construct(PDO $db)`

**Does:** Declares the constructor, which receives a `PDO` object.

**Why:**
- `__construct` → PHP's **magic method** that runs automatically when you write `new Auth($pdo)`.
- `PDO $db` → the parameter is typed. You can't pass a string or array — only a PDO object.
- **Dependency Injection** → the class doesn't create its own DB connection. It receives one from outside. This is critical for:
  - **Testing** — you can pass a mock PDO to test the class without a real database.
  - **Flexibility** — the same class works with any PDO instance, not just one specific config.

**Keywords:** constructor, magic method, dependency injection, type hint.

---

### Line: `    {`

**Does:** Opens the constructor body.

---

### Line: `        $this->db = $db;`

**Does:** Stores the received PDO in the class property.

**Why:**
- `$this` → refers to the current object (the specific `Auth` instance).
- `$this->db` → the class property we declared above.
- `= $db` → the parameter value from outside.
- Without this line, `$this->db` would stay uninitialized, and every method using it would crash.

**Keywords:** `$this`, property assignment, dependency storage.

---

### Line: `    }`

**Does:** Closes the constructor.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    /**`

### Line: `     * Find a dealer by username.`

### Line: `     */`

**Does:** A doc-block comment (also called PHPDoc).

**Why:** This is not just a comment — IDEs and tools read these. They power:
- **Auto-completion** — your editor shows the description when you hover.
- **Type hints** — if the docblock has `@return array`, the IDE can warn if you use the result wrong.
- **Documentation generators** — tools like phpDocumentor turn these into HTML docs.

**Keywords:** PHPDoc, docblock, self-documenting code.

---

### Line: `    public function findDealerByUsername(string $username): ?array`

**Does:** Declares the method with one parameter and a nullable return type.

**Why:**
- `public` → callable from outside the class.
- `string $username` → **type hint** — the parameter must be a string. Passing `123` (int) would be auto-coerced in weak mode, but passing an array would throw a `TypeError`.
- `: ?array` → **return type declaration**. The `?` makes it nullable:
  - If found → returns the dealer row as an array.
  - If not found → returns `null`.
- **Why nullable?** A dealer with that username might not exist. Returning `null` is the clean way to signal "nothing found." The caller can check `if ($dealer === null)`.

**Keywords:** return type, nullable type, type hint, self-documenting.

---

### Line: `    {`

**Does:** Opens the method body.

---

### Line: `        $sql = "`

**Does:** Starts a SQL string using a **heredoc-like multi-line syntax** (`"` on its own line means the string continues until the closing `"`).

**Why multi-line?**
- Long SQL is hard to read on one line.
- Multi-line makes the intent obvious: SELECT this, FROM this, WHERE this.
- Easy to add/remove columns without reformatting.

---

### Line: `            SELECT *`

**Does:** Selects all columns from the dealers table.

**Why `*` (all columns)?**
- Because we need the **full dealer row** — including the hashed password, verification_status, business_name, etc.
- The login flow uses: `password` (to verify), `verification_status` (to allow/deny), `dealer_id` (to set the session), `username` (for the response).
- Selecting all is simpler than listing every column.

**Trade-off:** In a huge table with 50 columns, this would be inefficient. In our case (12 columns), it's fine.

---

### Line: `            FROM dealers`

**Does:** Specifies the table.

---

### Line: `            WHERE username = :username`

**Does:** Filters by username using a **named placeholder**.

**Why `:username` and not the actual value?**
- **SQL injection prevention** — if we wrote `WHERE username = '$username'` and the user typed `' OR '1'='1`, they'd bypass the login.
- With a placeholder, MySQL treats the value as **data only**, never as SQL code.
- The value is bound separately in the `execute()` call below.

**Keyword:** prepared statement, parameter binding, placeholder.

---

### Line: `            LIMIT 1`

**Does:** Stops after finding one matching row.

**Why?**
- Usernames are UNIQUE at the DB level, so at most 1 match is possible.
- `LIMIT 1` tells MySQL: "Once you find one, stop scanning."
- **Performance:** Without `LIMIT 1`, MySQL scans the entire `dealers` table. With 10,000 dealers, that's a visible delay.

**Keyword:** query optimization.

---

### Line: `        ";`

**Does:** Closes the multi-line SQL string.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        $stmt = $this->db->prepare($sql);`

**Does:** Prepares the SQL statement.

**Why prepare?**
- `prepare()` sends the SQL **structure** to MySQL first (without values). MySQL parses it, plans the query, and returns a "prepared statement" handle.
- **Key security benefit:** the SQL is parsed **before** any user data arrives. It's impossible for user input to be interpreted as SQL because MySQL has already decided what the query shape is.

**Keywords:** prepared statement, SQL injection prevention.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        $stmt->execute([`

**Does:** Executes the prepared statement, binding values.

**Why an array?**
- Named placeholders (`:username`) need a matching array with the same keys.
- PDO binds each value into its placeholder position.

---

### Line: `            ':username' => $username`

**Does:** Maps the `:username` placeholder to the `$username` variable's value.

**Why the leading colon?**
- Placeholders in the SQL are `:username` (with colon).
- In the binding array, the key must also be `':username'` (with colon).
- PDO matches them together.

**Keyword:** parameter binding.

---

### Line: `        ]);`

**Does:** Closes the array and calls `execute()`.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        $dealer = $stmt->fetch();`

**Does:** Retrieves the first row from the result set.

**Why `fetch()`?**
- It returns the row as an **associative array** (thanks to `PDO::FETCH_ASSOC` set globally in `Database.php`).
- So `$dealer['username']`, `$dealer['password']`, etc. all work.
- If no row was found, `fetch()` returns `false`.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        return $dealer ?: null;`

**Does:** Returns the dealer array, or `null` if nothing was found.

**Why `?:`?**
- `?:` is the **elvis operator** (short ternary).
- It means: "if the left side is truthy, return it; otherwise return the right side."
- `$dealer` will be `false` if fetch returned nothing. So `$dealer ?: null` returns `null`.
- Without `?:`, we'd return `false` — but the function signature says `?array`, which means "array or null." Returning `false` would violate the type.

**Keyword:** elvis operator, ternary, type consistency.

---

### Line: `    }`

**Does:** Closes the method.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    /**`

### Line: `     * Verify a password against its hash.`

### Line: `     */`

**Does:** Docblock for the next method.

---

### Line: `    public function verifyPassword(`

### Line: `        string $password,`

### Line: `        string $hashedPassword`

### Line: `    ): bool {`

**Does:** Declares a method that takes two strings and returns a boolean.

**Why two separate parameters instead of one array?**
- Explicit is better than implicit. Anyone reading the call `verifyPassword($plain, $hash)` immediately understands the order.
- Type hints catch mistakes at runtime.

---

### Line: `        return password_verify($password, $hashedPassword);`

**Does:** Calls PHP's built-in password verification function.

**Why `password_verify()`?**
- It's the **only correct way** to check a password against a hash.
- Under the hood: it re-hashes the plain password using the same salt and algorithm that's embedded in the stored hash, then does a **constant-time comparison**.
- **Constant-time comparison** means the function takes the same amount of time whether the passwords match or not. This defeats **timing attacks** where an attacker measures how long the comparison takes to guess the hash byte by byte.

**Why not just compare hashes?**
- You could try `if (password_hash($plain) === $storedHash)` — but that won't work because bcrypt generates a different salt every time.
- `password_verify()` knows how to extract the salt from the stored hash and use it to re-hash for comparison.

**Keyword:** password hashing, bcrypt, timing attack, constant-time comparison.

---

### Line: `    }`

### Line: `}`

**Does:** Closes method and class.

---

## Full `Auth.php` summary — what to say in an interview

> "Auth is a small class that centralizes dealer authentication. It uses dependency injection to receive a PDO, has a method to look up dealers by username with a prepared statement, and a method to verify passwords using PHP's built-in `password_verify`. The class exists so that all login logic lives in one place."

---

# File 2 — `backend/api/auth/login-dealer.php`

## Purpose of the file

This is the **HTTP endpoint** that handles dealer login. It:
1. Receives POST JSON with username + password
2. Validates the input
3. Uses the `Auth` class to look up the dealer
4. Verifies the password
5. Checks the dealer's verification status
6. Creates a session on success
7. Returns JSON with different statuses for different outcomes

---

### Line: `<?php`

**Does:** Opens PHP.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `header('Content-Type: application/json');`

**Does:** Sends an HTTP header declaring the response is JSON.

**Why before any output?**
- HTTP headers must be sent before the body. If any whitespace or HTML is echoed first, the header call fails with "Headers already sent."
- The blank lines above are **fine** because they're inside PHP tags — they don't produce output.

**Why JSON?**
- The frontend expects JSON and calls `res.json()` on the response.
- If we sent HTML, the frontend would fail to parse it.

**Keyword:** HTTP header, content negotiation, MIME type.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `require_once __DIR__ . '/../../classes/Database.php';`

**Does:** Loads the Database class file.

**Why `require_once` instead of `require`?**
- If the file was already loaded earlier in the request, `require_once` skips it. This prevents "cannot redeclare class" errors.

**Why `__DIR__ . '/../../'`?**
- `__DIR__` = the absolute path of the **current file's directory**, which is `backend/api/auth/`.
- `../..` = go up two folders → `backend/`.
- Then `classes/Database.php` → `backend/classes/Database.php`.
- Using `__DIR__` makes the path **absolute and independent of the current working directory**. If we used a relative path, it would break if the script was called from a different directory.

**Keyword:** `__DIR__` magic constant, absolute paths, `require_once`.

---

### Line: `require_once __DIR__ . '/../../classes/Auth.php';`

**Does:** Loads the Auth class.

**Why we need it:** The endpoint will create an `Auth` instance to verify credentials.

---

### Line: `require_once __DIR__ . '/../../config/config.php';`

**Does:** Loads constants like `ADMIN_CONTACT_NUMBER` and `ADMIN_CONTACT_EMAIL`.

**Why here?** The endpoint includes the contact number in pending/rejected responses, so it needs those constants.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `// Only allow POST`

**Does:** A comment — ignored by PHP.

**Why:** Documents intent for the next block.

---

### Line: `if ($_SERVER['REQUEST_METHOD'] !== 'POST') {`

**Does:** Checks the HTTP method.

**Why only POST?**
- Logins contain credentials in the request body. GET requests would put credentials in the URL, which gets logged everywhere (browser history, server logs, proxies).
- REST convention: POST is for actions that change state or transmit sensitive data.

**Keyword:** HTTP method, `$_SERVER`, REST.

---

### Line: `    http_response_code(405);`

**Does:** Returns 405 Method Not Allowed.

**Why 405 and not 400?**
- `400 Bad Request` → the request is malformed.
- `405 Method Not Allowed` → the method is wrong (you used GET instead of POST).
- Specific codes help the frontend distinguish error types.

**Keyword:** HTTP status code.

---

### Line: `    echo json_encode([`

### Line: `        'success' => false,`

### Line: `        'message' => 'Only POST requests are allowed.'`

### Line: `    ]);`

**Does:** Returns a JSON error object.

**Why `success: false`?**
- The frontend checks `if (res.success)` to decide whether to treat the response as successful.
- Consistent JSON shape across all endpoints means the frontend doesn't need to handle each endpoint differently.

**Keyword:** API contract, consistent response shape.

---

### Line: `    exit;`

**Does:** Immediately stops the script.

**Why `exit`?**
- **Critical for security.** Without it, PHP would continue running the rest of the file — reading input, checking credentials, creating sessions — as if the method check passed.
- This is the same pattern we saw in middleware: **fail fast, don't continue**.
- Think of it as a wall: `exit` means "this is the last line you'll run."

**Keyword:** early exit, flow control, fail-safe.

---

### Line: `}`

**Does:** Closes the if block.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `// Read JSON request body`

**Does:** Comment.

---

### Line: `$input = json_decode(file_get_contents('php://input'), true);`

**Does:** Reads the raw POST body and parses it as JSON.

**Breaking this down:**
- `file_get_contents('php://input')` → reads the raw body. `php://input` is a special PHP stream that gives access to the POST data as a string.
- `json_decode($string, true)` → parses JSON. The `true` means "return associative arrays" instead of stdClass objects.
  - Without `true`: you'd access values with `$input->username`
  - With `true`: you access with `$input['username']` — simpler for our code style.

**Why read raw JSON instead of `$_POST`?**
- `$_POST` only works for `application/x-www-form-urlencoded` and `multipart/form-data`.
- When the frontend sends `Content-Type: application/json`, the body isn't populated into `$_POST`. You must read the raw stream.

**Keyword:** `php://input`, JSON parsing, content type.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `if (!is_array($input)) {`

**Does:** Checks that JSON parsing succeeded.

**Why?**
- If the body was empty or malformed, `json_decode` returns `null`.
- `!is_array(null)` → true → we return an error.

**Keyword:** input validation, defensive programming.

---

### Line: `    http_response_code(400);`

**Does:** Returns 400 Bad Request.

**Why 400?**
- The request body was invalid. 400 is the correct status.

---

### Line: `    echo json_encode([`

### Line: `        'success' => false,`

### Line: `        'message' => 'Invalid JSON request.'`

### Line: `    ]);`

**Does:** Error response.

---

### Line: `    exit;`

**Does:** Stops execution.

**Why:** Same reason as before — never let execution continue with invalid data.

---

### Line: `}`

**Does:** Closes the if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `// Get input values`

**Does:** Comment.

---

### Line: `$username = trim($input['username'] ?? '');`

**Does:** Extracts the username, trims whitespace, defaults to empty string.

**Breaking down:**
- `$input['username']` → get the value from the array.
- `?? ''` → the **null coalescing operator**. If `$input['username']` is missing or `null`, use `''` instead.
  - This prevents "Undefined array key" warnings.
- `trim()` → removes leading/trailing spaces. So `" UBAID "` becomes `"UBAID"`.
  - Prevents a common user error: accidentally typing a space.

**Keyword:** null coalescing operator, sanitization.

---

### Line: `$password = $input['password'] ?? '';`

**Does:** Extracts the password. **No trim.**

**Why no `trim` on the password?**
- Because spaces might be part of the password.
- If a user sets their password as `"my password"`, trimming would break it.
- We only trim fields where spaces are obviously accidental (username, email).

**Keyword:** context-aware sanitization.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `// Required fields`

**Does:** Comment.

---

### Line: `if ($username === '' || $password === '') {`

**Does:** Checks both required fields.

**Why `===` (strict equality)?**
- `===` checks both value AND type. `'' === ''` is true, but `0 === ''` is false.
- Safer than `==` which does type juggling (`0 == ''` is true in PHP!).

**Keyword:** strict comparison, type juggling, loose vs strict equality.

---

### Line: `    http_response_code(400);`

**Does:** 400 Bad Request.

---

### Line: `    echo json_encode([`

### Line: `        'success' => false,`

### Line: `        'message' => 'Username and password are required.'`

### Line: `    ]);`

**Does:** Error response.

---

### Line: `    exit;`

**Does:** Stop.

---

### Line: `}`

**Does:** Close if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `try {`

**Does:** Begins a try block — code that might throw an exception.

**Why a try block here?**
- Anything after this could throw:
  - Database connection might fail.
  - Query might fail.
  - Class method might throw.
- Wrapping it means we can **gracefully handle errors** and return JSON instead of crashing.

**Keyword:** exception handling, try-catch.

---

### Line: `    $database = new Database();`

**Does:** Creates a Database instance.

**Why this pattern?**
- The `Database` class encapsulates connection creation. Every endpoint uses the same class.
- If we ever need to change how connections are made (add logging, retry logic), we do it in one place.

**Keyword:** encapsulation, single source of truth.

---

### Line: `    $pdo = $database->getConnection();`

**Does:** Gets the PDO connection.

**Why a method call and not a property access?**
- `getConnection()` **lazily creates** the connection only when first called.
- If an endpoint calls it 5 times, only one PDO object is created. The rest get the cached one.
- This is a **lazy initialization** pattern.

**Keyword:** lazy initialization, caching.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    $auth = new Auth($pdo);`

**Does:** Creates an Auth instance, passing the PDO.

**Why pass the PDO?**
- **Dependency injection** — the Auth class doesn't create its own DB.
- Same PDO connection is shared between classes — efficient and consistent.

**Keyword:** dependency injection.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Find dealer`

**Does:** Comment.

---

### Line: `    $dealer = $auth->findDealerByUsername($username);`

**Does:** Looks up the dealer by username.

**Why use the Auth class instead of writing SQL here?**
- The SQL lives in one place (`Auth.php`). If we ever change how dealers are looked up (e.g., allow login by email too), we change one file.
- The endpoint stays focused on HTTP handling, not SQL.

**Keyword:** separation of concerns.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Case 1: Not registered`

**Does:** Comment.

---

### Line: `    if ($dealer === null) {`

**Does:** Handles the "no such dealer" case.

**Why check for null and not `!$dealer`?**
- `$dealer` could be an array (found) or `null` (not found).
- `!$dealer` would be true for an empty array too — but our function returns `null` on no match, so both work.
- `=== null` is more explicit and less surprising.

**Keyword:** explicit comparison.

---

### Line: `        http_response_code(404);`

**Does:** Returns 404 Not Found.

**Why 404?**
- The requested "resource" (a dealer with that username) doesn't exist.
- The frontend can distinguish this from a wrong password.

---

### Line: `        echo json_encode([`

### Line: `            'success' => false,`

### Line: `            'status' => 'not_registered',`

### Line: `            'message' => 'No account found. You haven\'t registered yet.'`

### Line: `        ]);`

**Does:** Returns a JSON error with a machine-readable `status` field.

**Why `status`?**
- The `message` is for the user to read.
- The `status` is for the **frontend logic** to branch on. Example:
  ```
  if (res.status === 'not_registered') showRegisterLink();
  else if (res.status === 'pending') showPendingCard();
  ```
- **Never** make the frontend parse the human-readable message to decide what to do. That's brittle — change the wording, break the code.

**Keyword:** machine-readable status, stable API contract.

---

### Line: `        exit;`

**Does:** Stop.

---

### Line: `    }`

**Does:** Close if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Case 2: Wrong password`

### Line: `    if (!$auth->verifyPassword($password, $dealer['password'])) {`

**Does:** Verifies the password.

**Why `$dealer['password']`?**
- That's the column from the DB. It contains the **bcrypt hash**, not the plain password.
- `verifyPassword` uses `password_verify` internally to compare.

**Why `!` (not) in the condition?**
- `verifyPassword` returns `true` for a correct password.
- We want to enter this block if the password is **wrong**.
- `!true` = false → skip block. `!false` = true → enter block.
- So the block runs when verification **fails**.

**Keyword:** boolean logic, negation.

---

### Line: `        http_response_code(401);`

**Does:** Returns 401 Unauthorized.

**Why 401?**
- Standard code for "authentication required / credentials invalid."

---

### Line: `        echo json_encode([`

### Line: `            'success' => false,`

### Line: `            'status' => 'invalid_credentials',`

### Line: `            'message' => 'Invalid username or password.'`

### Line: `        ]);`

**Does:** Error response.

**Why "Invalid username or password" and not "Wrong password"?**
- **User enumeration prevention** — if we said "wrong password," an attacker could confirm which usernames exist. Saying "invalid username or password" gives no info.
- Same message as if the dealer was not found (except status code differs — that's a slight leak, but manageable).

**Keyword:** user enumeration, information disclosure.

---

### Line: `        exit;`

### Line: `    }`

**Does:** Stop, then close the if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Case 3: Pending`

### Line: `    if ($dealer['verification_status'] === 'pending') {`

**Does:** Blocks dealers whose accounts aren't approved yet.

**Why here and not earlier?**
- We verify the password **first**. This prevents an attacker from probing which statuses exist without a valid password.
- Only after identity is confirmed do we check the account state.

**Keyword:** order of checks, defense in depth.

---

### Line: `        http_response_code(403);`

**Does:** Returns 403 Forbidden.

**Why 403 and not 401?**
- `401` = "you haven't proven who you are."
- `403` = "you're authenticated but not allowed."
- The dealer proved their identity with the correct password, but the account is pending. That's 403.

**Keyword:** 401 vs 403, semantic HTTP.

---

### Line: `        echo json_encode([`

### Line: `            'success' => false,`

### Line: `            'status' => 'pending',`

### Line: `            'message' => 'Your account is pending admin approval. Please contact us to request approval.',`

### Line: `            'contact_number' => ADMIN_CONTACT_NUMBER`

### Line: `        ]);`

**Does:** Returns JSON with the contact number.

**Why include `contact_number`?**
- The frontend needs it to show a clickable "Call Now" button.
- The number lives in `config.php`, not in the frontend — so changing it updates everywhere.

**Keyword:** server-driven configuration, single source of truth.

---

### Line: `        exit;`

### Line: `    }`

**Does:** Stop, close if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Case 4: Rejected`

### Line: `    if ($dealer['verification_status'] === 'rejected') {`

**Does:** Blocks rejected dealers.

**Why similar to pending but with a different message?**
- The user experience should differ: pending = "wait for us," rejected = "we rejected you."
- The frontend shows different colors (amber vs red).

---

### Line: `        http_response_code(403);`

**Does:** 403.

---

### Line: `        echo json_encode([`

### Line: `            'success' => false,`

### Line: `            'status' => 'rejected',`

### Line: `            'message' => 'Your registration request was rejected by the admin. Please contact us for more information.',`

### Line: `            'contact_number' => ADMIN_CONTACT_NUMBER`

### Line: `        ]);`

**Does:** Rejected response with contact info.

---

### Line: `        exit;`

### Line: `    }`

**Does:** Stop, close if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Case 5: Approved → create session`

### Line: `    if ($dealer['verification_status'] === 'approved') {`

**Does:** Handles successful login.

**Why an `if` here?**
- We could just assume success at this point, but explicit is better. If the DB ever contained an unexpected status value, this block wouldn't run — a fail-safe.

---

### Line: `        session_start();`

**Does:** Starts a new session (or resumes an existing one).

**Why here and not at the top of the file?**
- If we called `session_start()` at the top, every request — even ones that fail validation — would create a session cookie.
- By calling it **only on successful login**, we avoid unnecessary session cookies for failed attempts.

**Keyword:** lazy session creation.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        // Prevent session fixation`

### Line: `        session_regenerate_id(true);`

**Does:** Generates a new session ID and destroys the old one.

**Why?** — **The most important security line in the file.**

**The attack (session fixation):**
1. Attacker visits the site and gets a session ID (say `ABC123`).
2. Attacker tricks the victim into using the same session ID (via a URL, XSS, or shared computer).
3. Victim logs in. Their account is now associated with session `ABC123`.
4. Attacker also has `ABC123`. They can now access the victim's account.

**The defense:**
- On successful login, generate a **new** session ID.
- The old one (`ABC123`) becomes invalid on the server.
- The attacker's copy is now useless.

**Why the `true` argument?**
- `session_regenerate_id(true)` → deletes the old session file.
- `session_regenerate_id(false)` → keeps the old session file (still exists on disk).
- `true` is safer — the old session can't be resumed even if someone has the ID.

**Keyword:** session fixation, session hijacking, session_regenerate_id.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        $_SESSION['dealer_id'] = (int) $dealer['dealer_id'];`

**Does:** Stores the dealer's ID in the session.

**Why store it?**
- Every subsequent authenticated request (add property, edit, etc.) needs to know which dealer is logged in.
- The middleware checks `$_SESSION['dealer_id']` and passes it to the endpoint.

**Why cast `(int)`?**
- Values from the DB sometimes come back as strings (especially from PDO with certain settings).
- Casting ensures `$_SESSION['dealer_id']` is always an integer, so downstream code doesn't need to think about it.

**Keyword:** type casting, session data.

---

### Line: `        $_SESSION['dealer_username'] = $dealer['username'];`

**Does:** Stores the username for display purposes.

**Why store both ID and username?**
- ID is used for DB lookups (fast, indexed).
- Username is used for UI (displayed in the navbar: "Welcome, oop_dealer").
- You could always look up the username from the ID, but caching it avoids an extra query.

---

### Line: `        $_SESSION['role'] = 'dealer';`

**Does:** Stores the role.

**Why the role?**
- The `require-dealer.php` middleware checks `$_SESSION['role'] === 'dealer'`.
- If an admin logs in next, we set `'admin'` — the same browser can't be both at once.
- The role drives **authorization** everywhere.

**Keyword:** role, authorization, RBAC.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `        http_response_code(200);`

**Does:** Returns 200 OK.

---

### Line: `        echo json_encode([`

### Line: `            'success' => true,`

### Line: `            'status' => 'approved',`

### Line: `            'message' => 'Login successful.',`

### Line: `            'dealer' => [`

### Line: `                'dealer_id' => (int) $dealer['dealer_id'],`

### Line: `                'username' => $dealer['username'],`

### Line: `                'business_name' => $dealer['business_name']`

### Line: `            ]`

### Line: `        ]);`

**Does:** Returns success with only the fields the frontend needs.

**Why not return the whole dealer row?**
- The dealer row contains the **password hash** — never send that to the frontend.
- Return only what the UI needs: `dealer_id`, `username`, `business_name`.

**Keyword:** output filtering, principle of least disclosure.

---

### Line: `        exit;`

**Does:** Stop.

---

### Line: `    }`

**Does:** Close if.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `    // Fallback: unexpected status value in DB`

### Line: `    http_response_code(500);`

**Does:** Returns 500 if none of the cases matched.

**Why a fallback?**
- What if the DB somehow has a status value not in the enum (`'banana'`)?
- None of the `if` blocks matched, so we'd fall through to here.
- Returning 500 signals "something is wrong" instead of silently returning nothing.

**Keyword:** defensive programming, fail-safe default.

---

### Line: `    echo json_encode([`

### Line: `        'success' => false,`

### Line: `        'message' => 'Unexpected account status.'`

### Line: `    ]);`

**Does:** Error response.

---

### Line: (blank line)

**Does:** Visual spacing.

---

### Line: `} catch (Exception $e) {`

**Does:** Catches any exception from the try block.

**Why `Exception` (the parent class) and not `PDOException`?**
- We want to catch **all** exceptions, not just DB ones. Something else might throw (e.g., a `TypeError` from a bad cast).
- Catching the parent class is broad but appropriate for a final safety net.

**Keyword:** catch block, exception hierarchy.

---

### Line: `    http_response_code(500);`

**Does:** Returns 500 Internal Server Error.

---

### Line: `    echo json_encode([`

### Line: `        'success' => false,`

### Line: `        'message' => 'Unable to process login.'`

### Line: `    ]);`

**Does:** Generic error message.

**Why generic?**
- Never expose the real error to the client. It might reveal DB structure, file paths, or stack traces.
- Log the real error on the server (we don't have logging yet — a future addition).

**Keyword:** information disclosure prevention.

---

### Line: `}`

**Does:** Closes catch.

---

## Full `login-dealer.php` summary — what to say in an interview

> "This is the dealer login endpoint. It follows the standard pattern: check the HTTP method, parse JSON, validate required fields. Then it uses the Auth class to look up the dealer and verify the password. It handles four outcomes with distinct status codes: 404 for unregistered, 401 for wrong password, 403 for pending/rejected, and 200 with a session cookie for approved. On success, it calls `session_regenerate_id(true)` to prevent session fixation, stores the dealer ID, username, and role in the session, and returns only the safe fields. Everything is wrapped in a try/catch that hides internal errors."

---


Answer these 3 questions :

**Q1.** Why does `verifyPassword()` inside `Auth.php` use PHP's built-in `password_verify()` instead of comparing hashes directly with `===`?

**Q2.** In `login-dealer.php`, why is `session_start()` called **inside** the success block instead of at the top of the file?

**Q3.** If the dealer's status in the DB is somehow `'unknown'` (not pending/approved/rejected), what happens, and why is that the correct behavior?

Just tell me your answers. Then we move to the next pair of files: `login-admin.php` + `require-dealer.php` + `require-admin.php`.**A1.** `verifyPassword()` uses `password_verify()` because it correctly handles the bcrypt hashing algorithm, including the salt embedded in the hash. Directly comparing hashes with `===` would fail since bcrypt generates a different hash each time, even for the same password. `password_verify()` re-hashes the input password using the stored hash's salt and compares them in constant time to prevent timing attacks.
**A2.** `session_start()` is called inside the success block to avoid creating unnecessary session cookies for failed login attempts. This ensures that a session is only created when the dealer successfully logs in, preventing potential security issues and reducing server load from unused sessions.
**A3.** If the dealer's status is `'unknown'`, the code falls through all the specific status checks and reaches the fallback case, which returns a 500 Internal Server Error with a message indicating an unexpected account status. This is correct behavior because it signals that there is an inconsistency or error in the database, prompting further investigation rather than allowing the system to proceed with an undefined state.