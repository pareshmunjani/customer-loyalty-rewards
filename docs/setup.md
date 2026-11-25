
# Customer Loyalty Rewards System - Setup Guide

## 1. Requirements

Install the following software:

- XAMPP
- PHP 8.1 or later
- MySQL / MariaDB
- Web browser
- Git

## 2. Clone the GitHub Repository

Open a terminal and run:

```bash
git clone https://github.com/pareshmunjani/customer-loyalty-rewards
```

Move the project folder into:

```text
C:\xampp\htdocs\
```

The project folder should be named:

```text
customer-loyalty-rewards
```

## 3. Start XAMPP

Open XAMPP Control Panel.

Start:
- Apache
- MySQL

## 4. Create the Database

1. Open http://localhost/phpmyadmin/
2. Create a database named:

```text
customer_loyalty_db
```

3. Select the database.
4. Open the Import tab.
5. Import the project's `database/schema.sql` file.

## 5. Configure Database Connection

Open:

```text
app/config/database.php
```

Configure the database credentials according to your local MySQL installation.

Default XAMPP configuration:

- Host: 127.0.0.1
- Port: 3306
- Database: customer_loyalty_db
- Username: root
- Password: blank

## 6. Create Administrator Account

After importing the database, open:

```text
http://localhost/customer-loyalty-rewards/public/admin/create-admin.php
```

Enter the administrator's:

- Full name
- Mobile number
- Email address
- Password
- Confirm password

Submit the form to create the first administrator account.

Only the first administrator can be created through this setup page. Further setup attempts are blocked once an administrator exists.

## 7. Administrator Login

Open:

```text
http://localhost/customer-loyalty-rewards/public/admin/login.php
```

Log in using the administrator credentials created in Step 6.

## 8. Customer Registration

Open:

```text
http://localhost/customer-loyalty-rewards/public/customer/register.php
```

Register a customer account to test the customer features.

## 9. Access the Application

Customer application:

```text
http://localhost/customer-loyalty-rewards/public/
```

Administrator login:

```text
http://localhost/customer-loyalty-rewards/public/admin/login.php
```

## 10. Important Notes

- Every evaluator should create their own administrator account.
- Do not upload real passwords or generated reward-code CSV files to GitHub.
- The database must be imported before using the application.
- The administrator setup page becomes unavailable for account creation after the first administrator is registered.
