-- Database Migration for Employee Assignment Feature
-- This script adds the necessary column to support employee assignment to bookings

-- Add assigned_employee_id column to bookings table
ALTER TABLE bookings ADD COLUMN assigned_employee_id INT NULL;

-- Add foreign key constraint to ensure data integrity
ALTER TABLE bookings 
ADD CONSTRAINT fk_bookings_employee 
FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) 
ON DELETE SET NULL;

-- Add index for better query performance
CREATE INDEX idx_bookings_assigned_employee ON bookings(assigned_employee_id);

-- Add updated_at column if it doesn't exist (for tracking when assignments change)
ALTER TABLE bookings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing approved bookings to have a default assigned employee (optional)
-- This is only needed if you want to migrate existing data
-- UPDATE bookings SET assigned_employee_id = (SELECT id FROM employees LIMIT 1) WHERE status = 'approved' AND assigned_employee_id IS NULL;

-- Verify the changes
DESCRIBE bookings;
