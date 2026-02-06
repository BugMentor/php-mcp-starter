-- Schema for MCP sales agent (realistic demo)
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(64) UNIQUE NOT NULL,
    unit_price DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales (
    id SERIAL PRIMARY KEY,
    customer_id INTEGER NOT NULL REFERENCES customers(id),
    product_id INTEGER NOT NULL REFERENCES products(id),
    quantity INTEGER NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(12, 2) NOT NULL,
    total DECIMAL(12, 2) NOT NULL,
    sale_date DATE NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_sales_sale_date ON sales(sale_date);
CREATE INDEX idx_sales_customer_id ON sales(customer_id);
CREATE INDEX idx_sales_product_id ON sales(product_id);

-- Seed products
INSERT INTO products (name, sku, unit_price) VALUES
    ('Widget Pro', 'WDG-001', 29.99),
    ('Gadget Plus', 'GDG-002', 49.50),
    ('Super Cable', 'CBL-003', 12.99),
    ('Power Bank', 'PWR-004', 34.00),
    ('USB Hub', 'USB-005', 24.99);

-- Seed customers
INSERT INTO customers (name, email) VALUES
    ('Acme Corp', 'orders@acme.example.com'),
    ('Globex Inc', 'procurement@globex.example.com'),
    ('Initech', 'buy@initech.example.com'),
    ('Umbrella Co', 'supply@umbrella.example.com'),
    ('Wayne Enterprises', 'purchasing@wayne.example.com');

-- Seed sales (customer_id 1-5, product_id 1-5, various dates)
INSERT INTO sales (customer_id, product_id, quantity, unit_price, total, sale_date) VALUES
    (1, 1, 2, 29.99, 59.98, '2024-01-15'),
    (1, 2, 1, 49.50, 49.50, '2024-01-20'),
    (2, 3, 5, 12.99, 64.95, '2024-02-10'),
    (2, 4, 1, 34.00, 34.00, '2024-02-25'),
    (3, 1, 3, 29.99, 89.97, '2024-03-05'),
    (3, 5, 2, 24.99, 49.98, '2024-03-12'),
    (4, 2, 1, 49.50, 49.50, '2024-04-01'),
    (4, 4, 2, 34.00, 68.00, '2024-04-18'),
    (5, 5, 4, 24.99, 99.96, '2024-05-10'),
    (1, 3, 10, 12.99, 129.90, '2024-05-22'),
    (2, 1, 1, 29.99, 29.99, '2024-06-01'),
    (3, 4, 1, 34.00, 34.00, '2024-06-15'),
    (4, 5, 1, 24.99, 24.99, '2024-06-20'),
    (5, 2, 2, 49.50, 99.00, '2024-06-30');
