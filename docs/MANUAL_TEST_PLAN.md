# Manual Test Plan

## 1. Purpose

This document defines the manual test cases executed to verify the functionality of the Customer Loyalty Rewards System.

## 2. Testing Environment

| Item | Configuration |
|---|---|
| Operating System | Windows |
| Local Server | XAMPP |
| Backend | Core PHP 8.1+ |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript, Bootstrap 5 |
| Browser | Google Chrome |
| Database Access | PDO |

## 3. Test Status Definitions

- **Not Run:** Test has not yet been executed.
- **Pass:** Actual result matches the expected result.
- **Fail:** Actual result does not match the expected result.
- **Blocked:** Test cannot be executed because of an issue or dependency.

## 4. Customer Module Test Cases

| ID | Test Scenario | Test Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| CUST-01 | Customer registration | Open registration page, enter valid details, submit. | Account is created successfully. | Customer account created successfully. | Pass |
| CUST-02 | Duplicate email | Register using an already registered email. | Registration is rejected with an appropriate message. | Duplicate email was rejected. | Pass |
| CUST-03 | Duplicate mobile | Register using an already registered mobile number. | Registration is rejected. | Duplicate mobile number was rejected. | Pass |
| CUST-04 | Customer login | Enter valid customer credentials. | Customer is logged in and redirected to dashboard. | Customer logged in successfully. | Pass |
| CUST-05 | Invalid login | Enter incorrect credentials. | Login is rejected. | Invalid login was rejected. | Pass |
| CUST-06 | Customer logout | Click logout. | Customer session ends and protected pages require login. | Customer logged out successfully. | Pass |
| CUST-07 | Customer dashboard | Log in and open dashboard. | Correct points balance and account information are displayed. | Dashboard displayed the customer's information and points. | Pass |
| CUST-08 | Valid reward code | Submit a valid unused code. | Associated points are credited to the customer. | Valid code was accepted and points were credited. | Pass |
| CUST-09 | Invalid reward code | Submit a code that does not exist. | Code is rejected and no points are credited. | Invalid code was rejected. | Pass |
| CUST-10 | Reuse reward code | Submit a code that has already been redeemed. | Code is rejected and points are not credited again. | Previously redeemed code was rejected. | Pass |
| CUST-11 | Points history | Open points history. | Correct earning and redemption transactions are displayed. | Points transaction history displayed successfully. | Pass |
| CUST-12 | Browse rewards | Open rewards page. | Active rewards and their required points are displayed. | Available rewards were displayed successfully. | Pass |
| CUST-13 | Insufficient points | Attempt to redeem a reward without enough points. | Redemption is rejected and balance remains unchanged. | Redemption was rejected due to insufficient points. | Pass |
| CUST-14 | Successful reward redemption | Redeem an available reward with sufficient points. | Points are deducted and a unique voucher is generated. | Points were deducted and a voucher was generated. | Pass |
| CUST-15 | Redemption history | Open redemption history. | Customer's previous redemptions are displayed. | Redemption history displayed successfully. | Pass |

## 5. Admin Module Test Cases

| ID | Test Scenario | Test Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| ADM-01 | Admin login | Enter valid admin credentials. | Admin is logged in successfully. | Admin logged in successfully. | Pass |
| ADM-02 | Invalid admin login | Enter incorrect admin credentials. | Login is rejected. | Invalid admin login was rejected. | Pass |
| ADM-03 | Admin logout | Click admin logout. | Admin session ends. | Admin logged out successfully. | Pass |
| ADM-04 | Admin dashboard | Open admin dashboard after login. | Correct dashboard information is displayed. | Admin dashboard displayed successfully. | Pass |
| ADM-05 | Add product | Enter valid product details and save. | Product is created successfully. | Product was added successfully. | Pass |
| ADM-06 | Edit product | Modify an existing product. | Updated product information is saved. | Product information was updated successfully. | Pass |
| ADM-07 | Product status | Activate or deactivate a product. | Product status is updated correctly. | Product activation and deactivation worked successfully. | Pass |
| ADM-08 | Add reward | Enter valid reward details and save. | Reward is created successfully. | Reward was added successfully. | Pass |
| ADM-09 | Edit reward | Modify an existing reward. | Updated reward information is saved. | Reward information was updated successfully. | Pass |
| ADM-10 | Reward status | Activate or deactivate a reward. | Reward status is updated correctly. | Reward activation and deactivation worked successfully. | Pass |
| ADM-11 | Generate reward codes | Select a product, enter a valid quantity, and generate codes. | Requested codes are generated successfully. | Reward codes were generated successfully. | Pass |
| ADM-12 | Code generation validation | Enter an invalid quantity or product. | Invalid request is rejected. | Invalid code generation request was rejected. | Pass |
| ADM-13 | Export generated codes | Use the available CSV export option. | Generated codes are exported correctly. | CSV export worked successfully. | Pass |
| ADM-14 | Customer listing | Open customer management. | Customer records are displayed. | Customer records were displayed successfully. | Pass |
| ADM-15 | Customer details | Open a customer's details. | Correct customer information and activity are displayed. | Customer details and activity were displayed successfully. | Pass |
| ADM-16 | Activity monitoring | Open activity monitoring. | Available reward-code attempts and transaction activity are displayed. | Activity monitoring displayed successfully. | Pass |

## 6. Security and Access Control Test Cases

| ID | Test Scenario | Test Steps | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| SEC-01 | Customer opens admin page | Log in as customer and open an admin URL. | Access is denied or redirected. | Customer access to admin pages was denied. | Pass |
| SEC-02 | Guest opens protected page | Log out and open a protected customer or admin page. | User is redirected to the appropriate login page. | Unauthenticated access was redirected to login. | Pass |
| SEC-03 | Cross-customer data access | Attempt to access another customer's records. | Customer cannot access another customer's private data. | Access to another customer's private data was prevented. | Pass |
| SEC-04 | CSRF validation | Submit a protected form with a missing or invalid CSRF token. | Request is rejected. | Invalid or missing CSRF token request was rejected. | Pass |
| SEC-05 | SQL injection input | Enter SQL-like input in applicable form fields. | Input is handled safely without changing database query behaviour. | SQL-like input was handled safely. | Pass |
| SEC-06 | Password storage | Inspect stored user password values in the database. | Passwords are stored as hashes, not plain text. | Passwords were stored as hashes. | Pass |
| SEC-07 | Duplicate code prevention | Attempt to generate or insert a duplicate reward code. | Duplicate code is prevented. | Duplicate reward code was prevented. | Pass |
| SEC-08 | Repeated reward redemption | Attempt to redeem the same reward code more than once. | Points are credited only once. | Repeated redemption was prevented. | Pass |

## 7. Test Execution Summary

| Category | Total Test Cases | Passed | Failed | Blocked | Not Run |
|---|---:|---:|---:|---:|---:|
| Customer | 15 | 15 | 0 | 0 | 0 |
| Admin | 16 | 16 | 0 | 0 | 0 |
| Security | 8 | 8 | 0 | 0 | 0 |
| **Total** | **39** | **39** | **0** | **0** | **0** |

<text>**Overall Test Result: 39/39 test cases passed (100%).**</text>

## 8. Test Completion Criteria

Testing is considered complete because:

1. All 39 listed test cases were executed.
2. Actual results were recorded.
3. Customer registration, login, points, and reward redemption workflows were tested.
4. Admin product, reward, code generation, and customer management workflows were tested.
5. Security and access-control checks were performed.
6. All recorded test cases passed.

## 9. Conclusion

The manual testing of the Customer Loyalty Rewards System was completed successfully. All 39 test cases across the Customer, Admin, and Security modules passed during testing in the local development environment.

