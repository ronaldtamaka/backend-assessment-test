# Backend Assessment Test - Setup Instructions

## Prerequisites
- PHP >= 7.4
- Laravel 8.x
- MySQL/SQLite database
- Composer

## Setup Steps

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Configure your database in .env file
   # Then run migrations
   php artisan migrate:fresh
   ```

4. **Install Laravel Passport (for API authentication)**
   ```bash
   php artisan passport:install
   ```

## Running Tests

### Option 1: Run Individual Test Suites
```bash
# Test #01: Feature Tests
php artisan test tests/Feature/DebitCardControllerTest.php
php artisan test tests/Feature/DebitCardTransactionControllerTest.php

# Test #02: Unit Tests  
php artisan test tests/Unit/LoanServiceTest.php
```

### Option 2: Run All Tests
```bash
php artisan test
```

### Option 3: Use the Test Script
```bash
chmod +x run_tests.sh
./run_tests.sh
```

## Assessment Implementation Summary

### ✅ Test #01: Feature Tests
- **DebitCardControllerTest**: 10 main tests + 4 bonus tests
- **DebitCardTransactionControllerTest**: 6 main tests + 3 bonus tests
- All endpoints tested for positive/negative scenarios
- Authorization and validation properly tested

### ✅ Test #02: Loan Service
- **Migrations**: scheduled_repayments, received_repayments tables
- **Models**: ScheduledRepayment, ReceivedRepayment with proper relationships
- **Factories**: LoanFactory, ScheduledRepaymentFactory completed
- **LoanService**: createLoan() and repayLoan() methods implemented
- All 4 unit tests should pass

## Key Features Implemented

### Security & Authorization
- Policy-based authorization for all resources
- User can only access their own data
- Proper request validation with FormRequest classes

### Business Logic
- Debit card lifecycle management
- Transaction integrity (can't delete cards with transactions)
- Loan repayment system with partial payments
- Multi-currency support

### Database Design
- Proper foreign key relationships
- Soft deletes for data integrity
- Comprehensive migrations

All tests are designed to pass and validate the complete functionality as specified in the assessment requirements.
