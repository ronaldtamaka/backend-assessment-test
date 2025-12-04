## Backend Assessment Test

### Setup procedure
1. Checkout a new feature branch from `main`
2. Commit every meaningful update
3. Push the feature branch and open a Pull Request to `main`

---

## Test #01 — Debit Card API Feature Tests

### Objective
Create feature tests for *DebitCard* and *DebitCardTransaction* endpoints, including policies, validations, and resources.

### Business Logic
Each customer can have multiple **Debit Cards**, and each debit card can have many **Debit Card Transactions**.

- The customer must be able to create, update, read, and delete *his own* debit cards.
- For each debit card, the customer must be able to read and create debit card transactions.

##### Debit card endpoints:
- **get** `/debit-cards`
- **post** `/debit-cards`
- **get** `/debit-cards/{debitCard}`
- **put** `/debit-cards/{debitCard}`
- **delete** `/debit-cards/{debitCard}`

##### Debit card transactions endpoints *(optional / bonus)*:
- **get** `/debit-card-transactions`
- **post** `/debit-card-transactions`
- **get** `/debit-card-transactions/{debitCardTransaction}`

Each endpoint has specific conditions and validations that must be asserted.

### Challenge
Read all *DebitCard* and *DebitCardTransaction* routes, controllers, requests, resources, and policies.
Complete the missing feature tests in:

- `DebitCardControllerTest`
- `DebitCardTransactionTest`

Tips:
- verify positive and negative scenarios
- assert response and database values
- ensure customers can only access their own debit cards

**IMPORTANT:** For this challenge, you SHOULD ONLY update the feature tests.

---

## Test #02 — Loan Service

### Objective
Create a Loan Service that correctly handles loan repayments so that all existing unit tests pass.

### Business Logic
Each customer may have a credit **Loan** with duration of 3 or 6 months.
A loan automatically generates **scheduled repayments** (monthly), and the customer can make **received repayments**.

Example:

Loan of 3 months, amount 3000$, created on 2021-01-01:

- Scheduled Repayment of 1000$ due on 2021-02-01
- Scheduled Repayment of 1000$ due on 2021-03-01
- Scheduled Repayment of 1000$ due on 2021-04-01

A customer may repay:
- the full amount of each scheduled repayment
- partial repayments
- multiple scheduled repayments in sequence

Payments must always be applied to the earliest pending scheduled repayment.

### Challenge
Read the provided `LoanService` unit tests to understand the exact required behavior. All core files are already created; you only need to complete them.

You must implement:
- Migrations & factories for `scheduled_repayments` and `received_repayments`
- Models: `Loan`, `ScheduledRepayment`, `ReceivedRepayment`
- The full `LoanService` class logic

**IMPORTANT:** For this challenge, you SHOULD NOT update the existing unit tests.

---

## Test #03 — Scheduled Task: Overdue Repayment Reminders *(New Requirement)*

### Objective
Implement a daily scheduled task and queue job to automatically generate repayment reminders for overdue scheduled repayments.

### Business Logic
A system process must run **every day at 07:00**, checking for overdue repayments.

A reminder must be generated when:
- `ScheduledRepayment.status = 'pending'`
- `due_date < today`
- the loan is still active

For each overdue repayment:
1. Insert a record into `repayment_reminders`
2. Dispatch the queued job `SendRepaymentReminderJob`

### SendRepaymentReminderJob
This job does **not** send a real notification.

Instead, it must simulate notification sending by writing a structured log entry to:

```
storage/logs/laravel.log
```

Log entry example:

```
Log::info('Repayment reminder processed.', [
    'scheduled_repayment_id' => $this->scheduledRepayment->id,
    'customer_id' => $this->scheduledRepayment->loan->customer_id,
    'due_date' => $this->scheduledRepayment->due_date,
]);
```

### Challenge
You must implement:
- Migration + Model for `repayment_reminders`
- A scheduled command or scheduled service
- `SendRepaymentReminderJob`
- Queue dispatch logic in the scheduler

You may write new tests for this feature.

---

## Test #04 — Queue Job: Auto-Close Fully Paid Loans *(New Requirement)*

### Objective
Automatically close a loan when all its scheduled repayments are fully paid.

### Business Logic
Whenever a new `ReceivedRepayment` is created:

- Dispatch the queued job `EvaluateLoanStatusJob`

This job must:
- Check if all scheduled repayments for the loan are fully paid
- If yes → set the loan status to `closed`
- If not → do nothing

The job **must** run through the queue worker (not synchronously).

### Challenge
Implement:
- `EvaluateLoanStatusJob`
- Trigger logic (observer or LoanService)
- Minimal optional tests for this feature

---

## Allowed Changes
You **may**:
- Add new migrations, models, jobs, services, factories
- Add new tests for Test #03 and Test #04

You **may NOT**:
- Modify any existing test files
- Modify existing test logic
- Change the existing business rules defined by the provided tests

---

## Submission
Before submitting, run all tests:

```
php artisan test
```

### Your Pull Request must include:
- Summary of completed work
- Explanation of architectural decisions
- Questions or assumptions if any

---