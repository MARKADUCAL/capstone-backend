# AutoWash Hub API Documentation

## Overview

This API provides endpoints for managing a car wash booking system, including customers, employees, services, bookings, and various supporting features.

## Base URL

```
http://localhost/autowash-hub-api/api/
```

## Authentication

Most endpoints require authentication. Include the appropriate user credentials in the request body.

## Endpoints

### Customer Management

#### GET Endpoints

##### Get Customer Count

```
GET /get_customer_count
```

Returns the total number of customers in the system.

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Customer count retrieved successfully"
  },
  "payload": {
    "total_customers": 25
  },
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

##### Get All Customers

```
GET /get_all_customers
```

Returns a list of all customers with basic information.

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Customers retrieved successfully"
  },
  "payload": {
    "customers": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "phone": "09123456789",
        "created_at": "2025-01-15T10:00:00+00:00"
      }
    ]
  },
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

##### Get Customer ID Sequence

```
GET /get_customer_id_sequence
```

Returns the current sequence of customer IDs for debugging and verification purposes.

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Customer ID sequence retrieved successfully"
  },
  "payload": {
    "customer_ids": [1, 2, 3, 5, 6]
  },
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

#### POST Endpoints

##### Register Customer

```
POST /register_customer
```

Creates a new customer account. The system automatically assigns the next available ID starting from 1, filling any gaps in the ID sequence.

**Request Body:**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "09123456789",
  "password": "securepassword123"
}
```

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Successfully registered"
  },
  "payload": null,
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

**ID Management Behavior:**

- First customer always gets ID = 1
- Subsequent customers get the next available ID
- If a customer is deleted, the next registration will fill the gap
- IDs are always sequential starting from 1 with no gaps

##### Login Customer

```
POST /login_customer
```

Authenticates a customer and returns a JWT token.

**Request Body:**

```json
{
  "email": "john@example.com",
  "password": "securepassword123"
}
```

#### DELETE Endpoints

##### Delete Customer

```
DELETE /customers/{id}
```

Deletes a customer account. The system will automatically reset the ID sequence to maintain proper ordering.

**Parameters:**

- `id` (path parameter): The customer ID to delete

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Customer deleted successfully"
  },
  "payload": null,
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

**Notes:**

- Customer cannot be deleted if they have existing bookings or feedback
- After deletion, the next customer registration will fill the gap in the ID sequence
- The auto-increment counter is automatically reset to maintain proper ID ordering

### Employee Management

#### GET Endpoints

##### Get Employee Count

```
GET /get_employee_count
```

Returns the total number of employees in the system.

##### Get All Employees

```
GET /get_all_employees
```

Returns a list of all employees with basic information.

#### POST Endpoints

##### Register Employee

```
POST /register_employee
```

Creates a new employee account.

**Request Body:**

```json
{
  "employee_id": "EMP-001",
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@autowashhub.com",
  "phone": "09876543210",
  "password": "securepassword123",
  "position": "Car Washer"
}
```

##### Login Employee

```
POST /login_employee
```

Authenticates an employee and returns a JWT token.

### Service Management

#### GET Endpoints

##### Get All Services

```
GET /services
```

Returns a list of all available services.

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Services retrieved successfully"
  },
  "payload": {
    "services": [
      {
        "id": 1,
        "name": "Basic Wash",
        "description": "Exterior wash with hand drying",
        "price": "25.00",
        "duration_minutes": 30,
        "category": "Basic Wash",
        "is_active": 1
      }
    ]
  },
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

##### Get Service Categories

```
GET /get_service_categories
```

Returns all service categories.

#### POST Endpoints

##### Add Service

```
POST /services
```

Creates a new service.

**Request Body:**

```json
{
  "name": "Premium Wash",
  "description": "Exterior wash, interior cleaning, and tire shine",
  "price": 45.0,
  "duration_minutes": 60,
  "category": "Premium Wash",
  "is_active": true
}
```

#### PUT Endpoints

##### Update Service

```
PUT /services
```

Updates an existing service.

**Request Body:**

```json
{
  "id": 1,
  "name": "Basic Wash Plus",
  "description": "Enhanced basic wash service",
  "price": 30.0,
  "duration_minutes": 35,
  "category": "Basic Wash"
}
```

### Booking Management

#### GET Endpoints

##### Get All Bookings

```
GET /get_all_bookings
```

Returns all bookings with customer and service information.

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Bookings retrieved successfully"
  },
  "payload": {
    "bookings": [
      {
        "id": 1,
        "washDate": "2025-01-20",
        "washTime": "10:00:00",
        "status": "Pending",
        "price": "25.00",
        "vehicleType": "Sedan",
        "paymentType": "Cash",
        "nickname": "John's Car",
        "customerName": "John Doe",
        "serviceName": "Basic Wash",
        "serviceDescription": "Exterior wash with hand drying",
        "serviceDuration": 30
      }
    ]
  },
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

##### Get Bookings by Customer

```
GET /get_bookings_by_customer?customer_id=1
```

Returns all bookings for a specific customer.

##### Get Booking Details

```
GET /get_booking_details?booking_id=1
```

Returns detailed information about a specific booking.

##### Get Booking History

```
GET /get_booking_history?booking_id=1
```

Returns the status change history for a specific booking.

##### Get Booking Counts

```
GET /get_booking_count
GET /get_completed_booking_count
GET /get_pending_booking_count
```

Returns various booking statistics.

#### POST Endpoints

##### Create Booking

```
POST /create_booking
```

Creates a new booking.

**Request Body:**

```json
{
  "customer_id": 1,
  "service_id": 1,
  "vehicle_type": "Sedan",
  "nickname": "John's Car",
  "phone": "09123456789",
  "wash_date": "2025-01-25",
  "wash_time": "10:00:00",
  "payment_type": "Cash",
  "price": 25.0,
  "notes": "Please be gentle with the paint"
}
```

#### PUT Endpoints

##### Update Booking Status

```
PUT /update_booking_status
```

Updates the status of a booking and records the change in history.

**Request Body:**

```json
{
  "booking_id": 1,
  "status": "Confirmed",
  "changed_by": "admin@autowashhub.com",
  "notes": "Booking confirmed by admin"
}
```

#### DELETE Endpoints

##### Delete Booking

```
DELETE /bookings/{id}
```

Permanently deletes a booking by ID. The system automatically manages booking IDs to ensure they always start from 1 and fill gaps when available.

**URL Parameters:**

- `id`: The ID of the booking to delete

**Response:**

```json
{
  "status": {
    "remarks": "success",
    "message": "Booking deleted successfully"
  },
  "payload": null,
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

**Notes:**

- When a booking is deleted, the next new booking will use the available gap in the ID sequence
- If all bookings are deleted, the next new booking will get ID = 1
- This ensures booking IDs always start from 1 and don't continue auto-incrementing indefinitely

### Vehicle Types

#### GET Endpoints

##### Get Vehicle Types

```
GET /get_vehicle_types
```

Returns all available vehicle types with price multipliers.

#### POST Endpoints

##### Add Vehicle Type

```
POST /add_vehicle_type
```

Creates a new vehicle type.

**Request Body:**

```json
{
  "name": "SUV",
  "description": "Sport Utility Vehicles",
  "base_price_multiplier": 1.25,
  "is_active": true
}
```

#### PUT Endpoints

##### Update Vehicle Type

```
PUT /update_vehicle_type
```

Updates an existing vehicle type.

### Payment Methods

#### GET Endpoints

##### Get Payment Methods

```
GET /get_payment_methods
```

Returns all available payment methods.

#### POST Endpoints

##### Add Payment Method

```
POST /add_payment_method
```

Creates a new payment method.

**Request Body:**

```json
{
  "name": "Digital Wallet",
  "description": "Mobile payment apps",
  "is_active": true
}
```

#### PUT Endpoints

##### Update Payment Method

```
PUT /update_payment_method
```

Updates an existing payment method.

### Time Slots

#### GET Endpoints

##### Get Time Slots

```
GET /get_time_slots
```

Returns all available time slots.

##### Get Available Time Slots

```
GET /get_available_time_slots?date=2025-01-25
```

Returns available time slots for a specific date.

#### POST Endpoints

##### Add Time Slot

```
POST /add_time_slot
```

Creates a new time slot.

**Request Body:**

```json
{
  "start_time": "09:00:00",
  "end_time": "10:00:00",
  "max_bookings": 3,
  "is_active": true
}
```

#### PUT Endpoints

##### Update Time Slot

```
PUT /update_time_slot
```

Updates an existing time slot.

### Promotions

#### GET Endpoints

##### Get Promotions

```
GET /get_promotions
```

Returns all active promotions.

#### POST Endpoints

##### Add Promotion

```
POST /add_promotion
```

Creates a new promotion.

**Request Body:**

```json
{
  "name": "New Customer Discount",
  "description": "20% off for first-time customers",
  "discount_percentage": 20.0,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "is_active": true
}
```

#### PUT Endpoints

##### Update Promotion

```
PUT /update_promotion
```

Updates an existing promotion.

### Customer Feedback

#### GET Endpoints

##### Get Customer Feedback

```
GET /get_customer_feedback?limit=10
```

Returns public customer feedback with ratings and comments.

#### POST Endpoints

##### Add Customer Feedback

```
POST /add_customer_feedback
```

Creates new customer feedback.

**Request Body:**

```json
{
  "booking_id": 1,
  "customer_id": 1,
  "rating": 5,
  "comment": "Excellent service! Very professional and thorough.",
  "is_public": true
}
```

### Employee Schedules

#### GET Endpoints

##### Get Employee Schedules

```
GET /get_employee_schedules?employee_id=1&date=2025-01-20
```

Returns employee work schedules.

#### POST Endpoints

##### Add Employee Schedule

```
POST /add_employee_schedule
```

Creates a new employee schedule.

**Request Body:**

```json
{
  "employee_id": 1,
  "work_date": "2025-01-20",
  "start_time": "08:00:00",
  "end_time": "17:00:00",
  "is_available": true
}
```

#### PUT Endpoints

##### Update Employee Schedule

```
PUT /update_employee_schedule
```

Updates an existing employee schedule.

### Notifications

#### GET Endpoints

##### Get Notifications

```
GET /get_notifications?user_id=1&user_type=customer&limit=20
```

Returns notifications for a specific user.

#### POST Endpoints

##### Add Notification

```
POST /add_notification
```

Creates a new notification.

**Request Body:**

```json
{
  "user_id": 1,
  "user_type": "customer",
  "title": "Booking Confirmed",
  "message": "Your booking for January 25th has been confirmed.",
  "type": "success"
}
```

#### PUT Endpoints

##### Update Notification Status

```
PUT /update_notification_status
```

Marks a notification as read.

### System Settings

#### GET Endpoints

##### Get System Settings

```
GET /get_system_settings
```

Returns all system configuration settings.

#### PUT Endpoints

##### Update System Setting

```
PUT /update_system_setting
```

Updates a system configuration setting.

**Request Body:**

```json
{
  "setting_key": "business_hours_start",
  "setting_value": "08:00:00"
}
```

### Analytics & Reports

#### GET Endpoints

##### Get Revenue Analytics

```
GET /get_revenue_analytics
```

Returns monthly revenue data for the last 6 months.

##### Get Service Distribution

```
GET /get_service_distribution
```

Returns booking distribution across different services.

##### Get Dashboard Summary

```
GET /get_dashboard_summary
```

Returns comprehensive dashboard statistics.

## Error Responses

All endpoints return consistent error responses:

```json
{
  "status": {
    "remarks": "failed",
    "message": "Error description"
  },
  "payload": null,
  "timestamp": "2025-01-20T10:00:00+00:00"
}
```

## HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Internal Server Error

## Rate Limiting

Currently, no rate limiting is implemented. Consider implementing rate limiting for production use.

## CORS

The API includes CORS headers for cross-origin requests from `http://localhost:4200`.

## Database Schema

The API works with the following main tables:

- `customers` - Customer information
- `employees` - Employee information
- `services` - Available services
- `bookings` - Booking records
- `vehicle_types` - Vehicle categories
- `payment_methods` - Payment options
- `time_slots` - Available appointment times
- `promotions` - Discount offers
- `customer_feedback` - Customer reviews
- `employee_schedules` - Work schedules
- `notifications` - System notifications
- `system_settings` - Configuration settings

## Testing

Test the API endpoints using tools like:

- Postman
- Insomnia
- cURL
- Browser developer tools

## Support

For technical support or questions about the API, please refer to the system documentation or contact the development team.
