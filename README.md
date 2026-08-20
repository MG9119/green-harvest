# Green Harvest

Green Harvest is a cloud-based organic food e-commerce application developed for the **CSBC 252: Introduction to Cloud Computing** semester capstone project.

The application allows customers to browse organic food products, manage a shopping basket, place orders, view order history, and contact the business. Administrators can manage products, categories, customers, stock, orders, and customer feedback.

## Project Objective

The project demonstrates the design, development, deployment, and monitoring of a complete cloud-based application using Amazon Web Services (AWS).

## Main Features

### Customer
- User registration and secure login
- Product browsing and search
- Product details
- Shopping basket management
- Checkout and order placement
- Order history
- Contact/feedback form
- Password reset

### Administrator
- Secure admin login
- Dashboard overview
- Product CRUD operations
- Category management
- Customer management
- Inventory monitoring
- Order management and status updates
- Customer feedback inbox
- Read/unread message management

## Technology Stack

### Frontend
- HTML5
- CSS3
- Bootstrap
- JavaScript

### Backend
- PHP
- PDO

### Database
- MySQL
- Amazon RDS for MySQL

### Cloud Services
- AWS IAM
- Amazon EC2
- Amazon RDS
- Amazon S3
- Security Groups
- Amazon CloudWatch

### Development Tools
- XAMPP
- phpMyAdmin
- Git
- GitHub

## Proposed AWS Architecture

```text
User Browser
     |
     v
Amazon EC2
(PHP Application)
   /       \
  v         v
Amazon RDS  Amazon S3
(MySQL)     (Uploaded Images)

Supporting Services:
- AWS IAM
- Security Groups
- Amazon CloudWatch
```

## Project Structure

```text
Green_harvest/
├── admin/
├── assets/
│   ├── css/
│   ├── images/
│   └── js/
├── config/
│   └── database.php
├── includes/
├── uploads/
│   ├── categories/
│   └── products/
├── .env.example
├── .gitignore
├── index.php
└── README.md
```

> In the AWS deployment, dynamically uploaded product and category images are intended to be stored in Amazon S3 instead of EC2 local storage.

## Local Setup

### Requirements

- XAMPP
- PHP 8+
- MySQL
- Web browser

### Steps

1. Clone or copy the project into:

```text
C:\xampp\htdocs\Green_harvest
```

2. Start **Apache** and **MySQL** from XAMPP.

3. Create a MySQL database named:

```text
green_harvest
```

4. Import the Green Harvest SQL schema using phpMyAdmin.

5. Configure the required environment variables.

Example values are provided in:

```text
.env.example
```

6. Open the application:

```text
http://localhost/Green_harvest
```

## Environment Variables

The project uses environment variables for configuration.

Important variables include:

```text
APP_ENV
APP_URL

DB_HOST
DB_PORT
DB_NAME
DB_USER
DB_PASS

GOOGLE_CLIENT_ID
GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI

AWS_REGION
AWS_S3_BUCKET
AWS_S3_BASE_URL
```

Do not commit real passwords, database credentials, AWS access keys, or other secrets to GitHub.

## Security Measures

Green Harvest includes or is designed to use:

- Password hashing
- PDO prepared statements
- CSRF protection
- Server-side input validation
- Role-based access control
- Least-privilege AWS IAM permissions
- Restricted Security Group rules
- Protected environment variables
- S3 storage for uploaded assets

## AWS Deployment Plan

The production deployment will use:

1. **AWS IAM** for users, roles, and permissions
2. **Amazon EC2** for hosting the PHP application
3. **Security Groups** for network access control
4. **Amazon RDS for MySQL** for relational data
5. **Amazon S3** for uploaded product/category images
6. **Amazon CloudWatch** for monitoring and metrics

The **Application Load Balancer** may be implemented as an optional enhancement.

## Group Roles

| Role | Main Responsibility |
|---|---|
| Frontend Developer | UI, responsive design, navigation, product pages |
| Backend Developer | PHP logic, authentication, basket, checkout, CRUD |
| Database Administrator | Database design, SQL, relationships, Amazon RDS |
| Cloud Architect / Deployment Engineer | IAM, EC2, Security Groups, S3, deployment |
| QA / Security / Monitoring Engineer | Testing, security validation, CloudWatch |

## Course

**CSBC 252: Introduction to Cloud Computing**

Semester Group Project / Capstone

## Status

Development completed locally. AWS deployment and cloud-service integration are being prepared for the final capstone submission.
