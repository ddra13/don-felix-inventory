# Don Felix Sizzling House - Smart Inventory System

A centralized web application developed for **Web Development 2** designed to automate raw ingredient tracking, recipe-level point-of-sale depletion, and shift audits for Don Felix Sizzling House.

## Features
- **Role-Based Access Control**: Separate portals for Admin (Owner), Staff (Kitchen), and Client (POS Cashier).
- **Recipe Bill of Materials (BOM)**: Automatically deducts fractional ingredients (pork, rice, onions, eggs) when meal plates are charged.
- **Live Monitoring & Critical Alerts**: Instant visual badges for ingredients falling below reorder thresholds.
- **Printable Shift Audits**: Built-in printer stylesheets for closing inventory reconciliation.

## Tech Stack
- **Frontend**: HTML5, Modern CSS3, JavaScript
- **Backend**: PHP (Prepared Statements, Sessions)
- **Database**: MySQL (MariaDB via XAMPP)

## Setup & Installation
1. Clone this repository into your XAMPP `htdocs` directory:
   ```bash
   git clone https://github.com/ddra_13/don-felix-inventory.git
