#!/bin/bash
cd /Applications/XAMPP/xamppfiles/htdocs/freelance/backend-assessment-test
php artisan test --filter="testServiceCanCreateLoanOfForACustomer"
