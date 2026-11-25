# Assumptions and Limitations

## 1. Purpose

This document describes the assumptions, business rules, and limitations considered during the development of the Customer Loyalty Rewards System.

## 2. Business Assumptions

1. The company manufactures products and sells them in boxes.
2. Each eligible product box contains one unique reward code printed beneath a scratch-off label.
3. Each reward code is associated with a product and a predefined points value.
4. Customers obtain reward codes by purchasing product boxes through normal sales channels.
5. Entering a valid, unused code allows a customer to earn its associated points.
6. A reward code can be redeemed only once.
7. A customer must have an account to collect and redeem points.
8. Customers can redeem accumulated points for rewards made available by the administrator.

## 3. Customer Assumptions

1. Customers have access to a web browser and an internet connection or a local demonstration environment.
2. Customers provide valid registration information.
3. Each customer account uses a unique email address and mobile number.
4. Customers are responsible for keeping their login credentials private.
5. A customer can view only their own points balance, transactions, and redemption history.

## 4. Administrator Assumptions

1. The administrator is responsible for managing products and rewards.
2. The administrator can generate reward codes in bulk for selected products.
3. Generated codes are associated with a product and its configured points value.
4. The administrator can activate or deactivate products and rewards.
5. The administrator is responsible for securely distributing generated codes for printing and packaging.
6. The initial administrator account is created through the application's first-admin setup process.

## 5. Points and Redemption Rules

1. Points are awarded only after successful validation of a reward code.
2. The points value is stored with the generated code so that later product-point changes do not alter previously generated codes.
3. A customer cannot redeem a reward if their available points balance is insufficient.
4. Successful reward redemption deducts the required points from the customer's balance.
5. Each successful reward redemption generates a unique voucher code.
6. Points history is maintained through transaction records.

## 6. Security Assumptions

1. Reward codes must be difficult to guess.
2. Reward codes are stored as hashes in the database rather than as plain-text values.
3. Passwords are stored using secure password hashing.
4. Database operations use PDO prepared statements.
5. Protected pages require appropriate authentication and role authorization.
6. Forms that modify data use CSRF protection.
7. Database constraints help prevent duplicate reward codes and duplicate redemption references.

## 7. Project Limitations

1. A valid reward code confirms possession of a genuine code issued by the system; it does not independently verify the customer's purchase receipt.
2. The system does not independently integrate with retail stores, distributors, or payment gateways.
3. The system depends on the availability of its configured database and web server.
4. The project is designed as a web-based loyalty application and does not include a dedicated Android or iOS application.
5. Features such as SMS OTP, email OTP, and automated notifications are outside the current project scope unless separately implemented.

## 8. Scope Note

This document records the assumptions and limitations used to define the project scope. It does not claim that every possible future enhancement is currently implemented.
