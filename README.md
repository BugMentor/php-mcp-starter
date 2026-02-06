# PHP MCP Starter Kit

A lightweight, dependency-free (runtime) implementation of the **Model Context Protocol (MCP)** server in PHP. Expose your PHP business logic to AI agents such as **Claude Desktop**, **Cursor**, or any MCP-compatible client over stdio using JSON-RPC.

---

## Table of contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Project structure](#project-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [Adding your own tools](#adding-your-own-tools)
- [Testing](#testing)
- [License](#license)

---

## Overview

This project provides a minimal MCP server that:

- Communicates over **stdin/stdout** with JSON-RPC 2.0.
- Supports the MCP protocol methods: `initialize`, `notifications/initialized`, `tools/list`, and `tools/call`.
- Exposes **5 tools** backed by **PostgreSQL** (Docker): `query_sales`, `list_products`, `list_customers`, `list_sales`, `create_order`.

Runtime: PHP 8.2+ and the project’s pdo_pgsql, dotenv, and PostgreSQL (e.g. docker compose up -d). Tests use **Pest** (with PHPUnit under the hood) as a dev dependency.

---

## Features

- **Protocol**: MCP protocol version `2024-11-05`, tools capability.
- **PostgreSQL-backed**: Real DB in Docker; tools query products, customers, sales, and can create orders.
- **Extensible**: Register any number of tools; each tool implements a simple interface (name, definition, execute).
- **Tested**: Unit, integration, E2E, manual, and exploratory tests.
- **Cross-platform**: Works on Windows, macOS, and Linux (PHP CLI).

---

## Requirements

- **PHP** >= 8.2 (CLI) with **pdo_pgsql**
- **PostgreSQL** (e.g. via Docker)
- **Composer** (for dependencies; required for Pest)
- For **Composer**: PHP extensions `openssl`, `mbstring`, and optionally `curl`

---

## Project structure

```
php-mcp-starter/
├── docker-compose.yml          # PostgreSQL 16
├── docker/
│   └── init.sql                # Schema + seed (products, customers, sales)
├── src/
│   ├── Database.php            # PDO factory from env
│   ├── Server.php              # MCP server: stdio loop, JSON-RPC routing
│   └── Tools/
│       ├── SalesTool.php       # query_sales (date range revenue)
│       ├── ListProductsTool.php
│       ├── ListCustomersTool.php
│       ├── ListSalesTool.php
│       └── CreateOrderTool.php
├── tests/
│   ├── Pest.php                # Pest configuration and helpers load
│   ├── Helpers.php             # runServerWithInput, runServerWithRequests, e2eSendRequest
│   ├── Unit/
│   │   ├── SalesToolTest.php   # Pest unit tests for SalesTool
│   │   └── ServerTest.php      # Pest unit tests for Server (stream-based)
│   ├── Integration/
│   │   └── McpProtocolTest.php # Pest integration: full MCP sequence over streams
│   └── E2E/
│       └── ServerE2ETest.php   # Pest E2E: real server.php process, stdin/stdout
├── scripts/
│   ├── manual-test.php         # Manual test: initialize + list + call
│   ├── exploratory.php         # Exploratory: 7 scenarios
│   └── run-all-tests.php       # Runs manual + exploratory + Pest
├── .gitignore
├── composer.json
├── phpunit.xml.dist            # PHPUnit/Pest configuration
├── server.php                  # Entry point (stdio)
├── README.md
└── LICENSE                     # MIT
```

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-org/php-mcp-starter.git
cd php-mcp-starter
```

### 2. Install dependencies (recommended)

Generates the autoloader and installs dev dependencies (e.g. Pest, PHPUnit):

```bash
composer install
```

If you do not run `composer install`, the project includes a minimal `vendor/autoload.php` that only knows the `BugMentor\Mcp` and test namespaces, so the server and tests can still run without Composer.

### 3. Run PostgreSQL (Docker)

Start the database and apply schema + seed:

```bash
docker compose up -d
```

Default credentials (override with `.env`): database `mcp_sales`, user `mcp`, password `mcp_secret`, port `5432`.

Copy env and adjust if needed:

```bash
cp .env.example .env
```

**Without Docker:** The server still starts and exposes a single tool (`query_sales`) in mock mode. With PostgreSQL running and `.env` set, all five tools are available.

### 4. (Optional) Make the entry point executable (Unix-like)

```bash
chmod +x server.php
```

---

## Configuration

### Claude Desktop

Add the server to your Claude Desktop MCP config.

Config file location:

- **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`  
  Full path: `C:\Users\matias.magni2\AppData\Roaming\Claude\claude_desktop_config.json`

**Windows (full paths):**

```json
{
  "mcpServers": {
    "php-sales-agent": {
      "command": "C:\\Users\\matias.magni2\\Documents\\dev\\mine\\BugMentor\\php-mcp-starter\\.php-runtime\\php.exe",
      "args": [
        "C:\\Users\\matias.magni2\\Documents\\dev\\mine\\BugMentor\\php-mcp-starter\\server.php"
      ]
    }
  }
}
```

**macOS / Linux:**

```json
{
  "mcpServers": {
    "php-sales-agent": {
      "command": "/usr/bin/php",
      "args": [
        "/absolute/path/to/php-mcp-starter/server.php"
      ]
    }
  }
}
```

Restart Claude Desktop after changing the config.

### Cursor

Project config is in `.cursor/mcp.json` with full paths.

Windows (full paths):

- **Command**: `C:\Users\matias.magni2\Documents\dev\mine\BugMentor\php-mcp-starter\.php-runtime\php.exe`
- **Args**: `C:\Users\matias.magni2\Documents\dev\mine\BugMentor\php-mcp-starter\server.php`

---

## Usage

### From an MCP client (Claude Desktop, Cursor, etc.)

After configuration, restart the client. You can then ask things like:

- *"Check the sales data from 2024-01-01 to 2024-01-31."*
- *"What tools does the PHP MCP server expose?"*

The client will call `tools/list` and then `tools/call` with the appropriate arguments.

### From the command line (stdio)

Send one JSON-RPC request per line on stdin; responses are printed on stdout.

**Single request (then close stdin):**

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"initialize"}' | php server.php
```

**Windows (PowerShell, full path to PHP):**

```powershell
cd C:\Users\matias.magni2\Documents\dev\mine\BugMentor\php-mcp-starter
'{"jsonrpc":"2.0","id":1,"method":"initialize"}' | .\.php-runtime\php.exe server.php
```

The server runs until stdin is closed (EOF). Logs and errors go to stderr so they do not interfere with JSON-RPC on stdout.

---

## Architecture

- **`server.php`**: Loads `.env`, connects to PostgreSQL (or runs in fallback mode with one mock tool), creates the `Server`, registers tools, and calls `$server->run()`.
- **`Server`**: Reads lines from stdin, decodes JSON-RPC, dispatches by `method`, and writes one JSON-RPC response per request to stdout. Optional stdin/stdout/stderr streams can be passed to `run()` for testing.
- **Tools** (when DB is available): Any object that provides `getName()`, `getDefinition()`, and `execute(array $args)`. The server registers five tools:
  - **query_sales** – Total revenue and transaction count for a date range (`start_date`, `end_date`).
  - **list_products** – Products catalog (id, name, sku, unit_price). Optional `limit`.
  - **list_customers** – Customers (id, name, email). Optional `limit`.
  - **list_sales** – Recent sales with customer and product names. Optional `limit`, `start_date`, `end_date`.
  - **create_order** – Create a new sale (`customer_id`, `product_id`, `quantity`, optional `sale_date`).
- **Database**: `Database::createFromEnv()` builds a PDO connection from `DB_*` env vars (see `.env.example`). If the connection fails, only `query_sales` is registered (mock mode).

---

## Adding your own tools

1. Create a class in `src/Tools/` (or any PSR-4 path under `BugMentor\Mcp`).
2. Implement:
   - `getName(): string` — tool name used in `tools/call`.
   - `getDefinition(): array` — MCP tool description and `inputSchema`.
   - `execute(array $args): string` — run the tool and return a string (e.g. JSON).
3. Register it in `server.php`:

```php
$server->registerTool(new YourTool());
```

Restart the server (or the MCP client) so the new tool appears in `tools/list`.

---

## Testing

The project includes **unit**, **integration**, **E2E**, **manual**, and **exploratory** tests. Tests are written with **Pest** (elegant PHP testing built on PHPUnit). PHP 8.2+ is required; Composer is required for Pest.

### Pest (unit, integration, E2E)

Install dependencies and run the full suite:

```bash
composer install
composer test
```

Run by suite:

```bash
composer test:unit        # Unit tests only
composer test:integration # Integration tests only
composer test:e2e         # E2E tests only
```

Or call Pest directly:

```bash
./vendor/bin/pest
./vendor/bin/pest --testsuite Unit
./vendor/bin/pest --testsuite Integration
./vendor/bin/pest --testsuite E2E
```

**What each suite does:**

| Suite         | Location              | Description |
|---------------|-----------------------|-------------|
| **Unit**      | `tests/Unit/`         | `SalesToolTest`: name, definition, execute (valid/missing args, revenue range). `ServerTest`: initialize, tools/list, tools/call (success), unknown tool, unknown method, notification (no output). Uses in-memory streams. |
| **Integration** | `tests/Integration/` | `McpProtocolTest`: full sequence (initialize → tools/list → tools/call), multiple tool calls, notification then request. Server run with stream input. |
| **E2E**       | `tests/E2E/`          | `ServerE2ETest`: runs `server.php` as a subprocess, sends JSON-RPC on stdin, asserts on stdout (initialize, tools/list, tools/call). Uses `PHP_BINARY` when available. |

Expected result: **17 tests** (unit, integration, E2E), all passing.

### Manual test

Runs the server in-process with three requests (initialize, tools/list, tools/call) and prints stderr and pretty-printed JSON responses:

```bash
php scripts/manual-test.php
```

### Exploratory test

Runs seven scenarios and prints each response:

1. Initialize  
2. Tools list  
3. Tool call (valid dates)  
4. Unknown method (expect error)  
5. Unknown tool (expect error)  
6. Notification (no response)  
7. Tool call (missing args; server still returns success with "unknown" period)

```bash
php scripts/exploratory.php
```

### Run all tests (manual + exploratory + Pest)

Uses the same PHP binary that runs the script (e.g. `PHP_BINARY`) to invoke manual, exploratory, and Pest:

```bash
php scripts/run-all-tests.php
```

If `php` is not on your PATH, use the full path to the portable PHP:

```powershell
cd C:\Users\matias.magni2\Documents\dev\mine\BugMentor\php-mcp-starter
.\.php-runtime\php.exe scripts\run-all-tests.php
```

### One-off JSON-RPC request

From project root (Windows, full path):

```powershell
cd C:\Users\matias.magni2\Documents\dev\mine\BugMentor\php-mcp-starter
'{"jsonrpc":"2.0","id":1,"method":"tools/list"}' | .\.php-runtime\php.exe server.php
```

### Requirements and notes

- **PHP**: 8.2 or later.  
- **Composer**: Needed for `composer install` and `composer test`.  
- **Windows**: Ensure `php` (and optionally `composer`) are on your PATH, or use full paths as above.  
- **E2E**: If `server.php` is not found or the PHP binary cannot be determined, E2E tests may be skipped.

---

## License

This project is licensed under the **MIT License**. See [LICENSE](LICENSE) for the full text.
