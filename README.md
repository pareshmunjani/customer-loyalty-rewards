
# Customer Loyalty Rewards System

A web-based loyalty rewards platform that allows customers to earn points by redeeming unique product codes and exchange accumulated points for rewards.

## Project Overview

The Customer Loyalty Rewards System is designed for businesses that sell physical products in packages.

Each package contains a unique reward code protected by a scratch-off label. Customers can register, log in, enter the code printed inside the package, and earn loyalty points.

Customers can use their accumulated points to redeem available rewards and receive a unique voucher.

Administrators manage products, reward options, reward codes, customers, and system activities through a dedicated admin panel.

## Project Objectives

- Provide a digital loyalty rewards platform for customers.
- Encourage repeat purchases through a points-based reward system.
- Validate unique product codes securely.
- Prevent duplicate redemption of reward codes.
- Allow customers to exchange points for rewards.
- Provide administrators with tools to manage and monitor the loyalty program.

## Key Features

### Customer Features

- Customer registration and login.
- Customer dashboard with points balance.
- Redeem unique product reward codes.
- Earn points after successful code validation.
- View points transaction history.
- Browse available rewards.
- Redeem rewards using accumulated points.
- Receive unique reward voucher codes.
- View reward redemption history.

### Administrator Features

- Secure administrator login.
- Admin dashboard with system statistics.
- Product management.
- Reward management.
- Bulk reward code generation.
- CSV export of generated reward codes.
- Customer search and customer details.
- Customer reward and points history.
- Activity monitoring.
- Monitoring of successful code redemptions and failed code attempts.

## How the System Works

1. The administrator creates products and defines their points value.
2. The administrator generates unique reward codes for products.
3. Generated codes are printed and placed inside product packages under scratch-off labels.
4. A customer purchases a product and scratches the label to reveal the code.
5. The customer logs in and submits the code.
6. The system validates the code and checks its eligibility.
7. If the code is valid and unused, the customer receives the associated points.
8. The customer can redeem accumulated points for available rewards.
9. The system generates a unique voucher for a successful reward redemption.

**Important:** A valid reward code confirms possession of a genuine package code. It does not independently verify the retail purchase.

## Technology Stack

| Component | Technology |
|---|---|
| Backend | Core PHP 8.1+ |
| Frontend | HTML, CSS, JavaScript |
| UI Framework | Bootstrap 5 |
| Database | MySQL / MariaDB |
| Database Access | PHP PDO |
| Local Development | XAMPP |
| Version Control | Git |
| Repository Hosting | GitHub |
| Code Editor | Visual Studio Code |

The project uses Core PHP and does not require Laravel, React, Node.js, or other backend/frontend frameworks.

## Security Features

- Password hashing and password verification.
- PDO prepared statements for database operations.
- Unique reward code validation.
- Secure random reward code generation.
- Prevention of duplicate reward code redemption.
- Database transactions for points operations.
- Session-based authentication.
- Role-based access control for administrators and customers.
- CSRF protection for applicable forms.
- Server-side validation of customer and reward operations.
- Output escaping to reduce XSS risks.
- Protection against unauthorized access to customer-specific data.

## Installation and Setup

### Prerequisites

- XAMPP
- PHP 8.1 or later
- MySQL / MariaDB
- Git
- A modern web browser

### Quick Start

**1. Clone the repository**

```bash
git clone https://github.com/pareshmunjani/customer-loyalty-rewards
```

**2. Place the project inside XAMPP**

Move the project folder into:

```text
C:\xampp\htdocs\
```

Keep the folder name:

```text
customer-loyalty-rewards
```

**3. Start XAMPP**

Start Apache and MySQL from the XAMPP Control Panel.

**4. Configure the database**

Create a database named:

```text
customer_loyalty_db
```

Import:

```text
database/schema.sql
```

**5. Configure database credentials**

Check:

```text
app/config/database.php
```

Update the database connection details according to your local environment.

**6. Create the first administrator**

Open:

```text
http://localhost/customer-loyalty-rewards/public/admin/create-admin.php
```

Create the first administrator account.

**7. Open the application**

Customer application:

```text
http://localhost/customer-loyalty-rewards/public/
```

Administrator login:

```text
http://localhost/customer-loyalty-rewards/public/admin/login.php
```

For complete installation instructions, refer to:

**[Setup Guide](docs/setup.md)**

## Application URLs

| Area | URL |
|---|---|
| Customer Homepage | `/customer-loyalty-rewards/public/` |
| Customer Registration | `/customer-loyalty-rewards/public/customer/register.php` |
| Customer Login | `/customer-loyalty-rewards/public/customer/login.php` |
| Admin Login | `/customer-loyalty-rewards/public/admin/login.php` |
| Admin Setup | `/customer-loyalty-rewards/public/admin/create-admin.php` |

## Important Notes

- Import the database schema before using the application.
- The first administrator must be created through the administrator setup page.
- Further administrator setup attempts are blocked once an administrator exists.
- Each reward code can be redeemed only once.
- Never commit real passwords or sensitive generated reward code files to the repository.
- Use the setup guide for complete installation instructions.

## Version Control

This project uses Git and GitHub for version control.

To check project changes:

```bash
git status
```

To commit and push changes:

```bash
git add .
git commit -m "Describe your changes"
git push
```

## Project Repository

GitHub:

https://github.com/pareshmunjani/customer-loyalty-rewards

---

**Customer Loyalty Rewards System**

*Every Purchase Matters.*
