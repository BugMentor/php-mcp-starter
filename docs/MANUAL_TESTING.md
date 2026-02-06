# Manual Testing – 15 Prompts

This document describes 15 manual test prompts for the MCP Sales Agent. Use them to verify server behavior with a real client or by piping JSON-RPC requests into `server.php`.

## Prerequisites

- **With database**: Start Postgres (`docker compose up -d`), ensure `.env` is set. The server exposes 5 tools: `query_sales`, `list_products`, `list_customers`, `list_sales`, `create_order`.
- **Without database**: The server runs with a single tool, `query_sales` (mock data). Prompts 1–2, 6–7, 13–15 still apply; 3–5 and 8–12 require the DB for full behavior.

## How to run

1. **Start the server** (stdio):  
   `php server.php`
2. **Send one request per line** (JSON-RPC 2.0) on stdin. Each line is a JSON object; the server responds with one JSON object per request on stdout (except for notifications).
3. **Optional script**: Use or extend `scripts/manual-15-prompts.php` to send all 15 prompts and print responses.

---

## Test prompts

| # | Prompt | Request | Expected |
|---|--------|---------|----------|
| **1** | **Initialize** | `{"jsonrpc":"2.0","id":1,"method":"initialize"}` | `result` with `serverInfo.name` = `php-sales-agent`, `serverInfo.version` = `1.0.0`, `protocolVersion`, `capabilities.tools`. |
| **2** | **List tools** | `{"jsonrpc":"2.0","id":2,"method":"tools/list"}` | `result.tools` array. With DB: 5 tools (`query_sales`, `list_products`, `list_customers`, `list_sales`, `create_order`). Without DB: 1 tool (`query_sales`). |
| **3** | **Query sales – January 2024** | `{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"query_sales","arguments":{"start_date":"2024-01-01","end_date":"2024-01-31"}}}` | `result.content[0].text` JSON: `status` = `success`, `period`, `total_revenue`, `transaction_count`, `currency` = `USD`. |
| **4** | **Query sales – Q2 2024** | `{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"query_sales","arguments":{"start_date":"2024-04-01","end_date":"2024-06-30"}}}` | Same shape as #3; revenue/count for Apr–Jun 2024. |
| **5** | **Query sales – single day** | `{"jsonrpc":"2.0","id":5,"method":"tools/call","params":{"name":"query_sales","arguments":{"start_date":"2024-12-25","end_date":"2024-12-25"}}}` | Same shape; may be zero transactions for that day. |
| **6** | **List products (default)** | `{"jsonrpc":"2.0","id":6,"method":"tools/call","params":{"name":"list_products","arguments":{}}}` | With DB: `result.content[0].text` JSON with `status`, `products` array, `count`. Without DB: Tool not found. |
| **7** | **List products (limit 3)** | `{"jsonrpc":"2.0","id":7,"method":"tools/call","params":{"name":"list_products","arguments":{"limit":3}}}` | With DB: up to 3 products. Same JSON shape as #6. |
| **8** | **List customers (default)** | `{"jsonrpc":"2.0","id":8,"method":"tools/call","params":{"name":"list_customers","arguments":{}}}` | With DB: `customers` array, `count`. Without DB: Tool not found. |
| **9** | **List customers (limit 5)** | `{"jsonrpc":"2.0","id":9,"method":"tools/call","params":{"name":"list_customers","arguments":{"limit":5}}}` | With DB: up to 5 customers. |
| **10** | **List sales (default)** | `{"jsonrpc":"2.0","id":10,"method":"tools/call","params":{"name":"list_sales","arguments":{}}}` | With DB: `sales` array (recent first), `count`. Each sale has `id`, `customer_id`, `customer_name`, `product_id`, `product_name`, `quantity`, `unit_price`, `total`, `sale_date`. |
| **11** | **List sales (date filter)** | `{"jsonrpc":"2.0","id":11,"method":"tools/call","params":{"name":"list_sales","arguments":{"start_date":"2024-06-01","end_date":"2024-06-30","limit":10}}}` | With DB: sales in June 2024 only, max 10. |
| **12** | **Create order** | `{"jsonrpc":"2.0","id":12,"method":"tools/call","params":{"name":"create_order","arguments":{"customer_id":1,"product_id":1,"quantity":2}}}` | With DB: `result.content[0].text` JSON with `status` = `success`, `message` = `Order created`, `order` (id, customer_id, product_id, quantity, unit_price, total, sale_date). Without DB: Tool not found. |
| **13** | **Unknown tool** | `{"jsonrpc":"2.0","id":13,"method":"tools/call","params":{"name":"weather_forecast","arguments":{}}}` | `error`: `code` = -32601, `message` = `Tool not found`. |
| **14** | **Unknown method** | `{"jsonrpc":"2.0","id":14,"method":"resources/list"}` | `error`: `code` = -32601, `message` = `Method not found`. |
| **15** | **Notification (no response)** | `{"jsonrpc":"2.0","method":"notifications/initialized"}` | No JSON line on stdout (notification; server may log to stderr). |

---

## Raw request bodies (one per line, for piping)

Copy these lines into a file or pipe to `php server.php`:

```
{"jsonrpc":"2.0","id":1,"method":"initialize"}
{"jsonrpc":"2.0","id":2,"method":"tools/list"}
{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"query_sales","arguments":{"start_date":"2024-01-01","end_date":"2024-01-31"}}}
{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"query_sales","arguments":{"start_date":"2024-04-01","end_date":"2024-06-30"}}}
{"jsonrpc":"2.0","id":5,"method":"tools/call","params":{"name":"query_sales","arguments":{"start_date":"2024-12-25","end_date":"2024-12-25"}}}
{"jsonrpc":"2.0","id":6,"method":"tools/call","params":{"name":"list_products","arguments":{}}}
{"jsonrpc":"2.0","id":7,"method":"tools/call","params":{"name":"list_products","arguments":{"limit":3}}}
{"jsonrpc":"2.0","id":8,"method":"tools/call","params":{"name":"list_customers","arguments":{}}}
{"jsonrpc":"2.0","id":9,"method":"tools/call","params":{"name":"list_customers","arguments":{"limit":5}}}
{"jsonrpc":"2.0","id":10,"method":"tools/call","params":{"name":"list_sales","arguments":{}}}
{"jsonrpc":"2.0","id":11,"method":"tools/call","params":{"name":"list_sales","arguments":{"start_date":"2024-06-01","end_date":"2024-06-30","limit":10}}}
{"jsonrpc":"2.0","id":12,"method":"tools/call","params":{"name":"create_order","arguments":{"customer_id":1,"product_id":1,"quantity":2}}}
{"jsonrpc":"2.0","id":13,"method":"tools/call","params":{"name":"weather_forecast","arguments":{}}}
{"jsonrpc":"2.0","id":14,"method":"resources/list"}
{"jsonrpc":"2.0","method":"notifications/initialized"}
```

Example (PowerShell):

```powershell
Get-Content docs\manual-15-requests.txt | php server.php
```

Or with the script (when available):

```bash
php scripts/manual-15-prompts.php
```

---

## Pass criteria

- **1–2**: Server identifies itself and lists the expected tools.
- **3–5**: `query_sales` returns valid JSON with `status`, `period`, `total_revenue`, `transaction_count`, `currency`.
- **6–7**: `list_products` returns `products` and `count` (when DB is available).
- **8–9**: `list_customers` returns `customers` and `count` (when DB is available).
- **10–11**: `list_sales` returns `sales` and `count`; date filter narrows results (when DB is available).
- **12**: `create_order` returns success and an `order` object (when DB is available).
- **13**: Error with code -32601, message "Tool not found".
- **14**: Error with code -32601, message "Method not found".
- **15**: No response line; no crash.
