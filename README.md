## Backend Assessment Test
### Setup procedure
1. Checkout a new feature branch from `master`
2. Do commit for every function updates
3. Push the code and prepare the Pull Request from feature branch to master branch

### Test #01
#### Objective
Create feature tests to test *DebitCard* and *DebitCardTransaction* endpoints and relatives policies, validations and resources.

#### Business Logic
Each customer can have multiple *Debit Cards* and each debit card can have many *Debit Card Transactions*.

- The customer should be able to create, update, read and delete his debit cards. 
- For each debit card the customer should be able to read and create debit card transactions.

##### Debit cards endpoints:
- **get** `/debit-cards`
- **post** `/debit-cards`
- **get** `/debit-cards/{debitCard}`
- **put** `/debit-cards/{debitCard}`
- **delete** `/debit-cards/{debitCard}`

##### Debit card transactions endpoints *(optional/bonus point)*:
- **get** `/debit-card-transactions`
- **post** `/debit-card-transactions`
- **get** `/debit-card-transactions/{debitCardTransaction}`

For each endpoint there are specific condition and validation to asserts

#### Challenge
Read through the *DebitCard* and *DebitCardTransaction* routes, controllers, requests, resources and policies. 
Understand the logic and write as much tests as possible to validate the endpoints. The `DebitCardControllerTest` and `DebitCardTransactionTest` are already created you just need to complete them.

Tips:

- verify positive and negative scenarios
- assert response and database values
- customer can handle only his own debit cards

**IMPORTANT:** For this challenge you SHOULD ONLY update the feature tests

---

### Test #02

#### Objective
Create a Loan service to handle repayments based on complete unit tests already created.

#### Business Logic
Each customer can have a credit *loan* (due in 3 or 6 months). So a Loan has 3 or 6 *scheduled repayments* (once each month),
and it can be repaid with *received repayments*.
Example:

Loan of 3 months, amount 3000$, created on 2021-01-01

- Scheduled Repayment of 1000$ due to 2021-02-01
- Scheduled Repayment of 1000$ due to 2021-03-01
- Scheduled Repayment of 1000$ due to 2021-04-01

A customer can repay the full amount of each single scheduled repayment, but also he can repay partially or in full

#### Challenge
Read through the tests of LoanService to understand what is the logic to be implemented. All classes and files are already created, you just need to complete them.
In order to make the unit tests passed, you need to fulfil:

- the migrations/factories for scheduled_repayments and received_repayment tables (migration for loans table already done);
- the Loan, ScheduledRepayment, and ReceivedRepayment Models;
- the LoanService class;

**IMPORTANT:** For this challenge you SHOULD NOT update the unit test

## Installation & Setup Guide

### Prerequisites
- **PHP** 7.4 or higher
- **Composer** (PHP dependency manager)
- **MySQL** 5.7 or higher
- **Git**

### Step 1: Clone Repository
```bash
git clone https://github.com/Dimdevs/akasia-debit-loan-system.git
cd akasia-debit-loan-system
```

### Step 2: Install PHP Dependencies
```bash
composer install
```

### Step 3: Environment Configuration
```bash
cp .env.example .env

php artisan key:generate
```

Edit `.env` file and update database configuration:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=akasia_debit_loan
DB_USERNAME=root
DB_PASSWORD=
```

### Step 4: Create MySQL Database
```bash
mysql -u root -p -e "CREATE DATABASE akasia_debit_loan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

```

### Step 5: Run Database Migrations
```bash
php artisan migrate

php artisan migrate:reset
php artisan migrate
```

### Step 6: Generate Laravel Passport Encryption Keys
```bash
php artisan passport:install
```

This creates OAuth tokens for API authentication testing.

### Step 7: Run Tests
```bash
php artisan test

php artisan test --testdox

php artisan test tests/Feature/DebitCardControllerTest.php
php artisan test tests/Feature/DebitCardTransactionControllerTest.php
php artisan test tests/Unit/LoanService.php

php artisan test --coverage
```

### Expected Test Output
```
Tests: 43 total
✅ Loan Service: 4/4 passing
✅ DebitCard Controller: 19/19 passing
✅ DebitCard Transaction Controller: 20/20 passing
Assertions: 129 total
Time: ~0.945 seconds
```

### Optional: Run Development Server
```bash
php artisan serve

# Server will be available at: http://localhost:8000
```