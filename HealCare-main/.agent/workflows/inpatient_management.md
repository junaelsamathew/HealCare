---
description: Design and implement Inpatient Management Workflow
---

# Inpatient Management Implementation Plan

This workflow outlines the steps to implement a complete Inpatient Management system.

## 1. Database Schema Updates
Create/Modify tables to support admission requests, inpatient workflow, and discharge.

- **Table: admissions** (Modify)
  - Add `request_date` (datetime)
  - Add `reason` (text)
  - Add `ward_type_req` (varchar) - The type of ward requested (General, ICU, etc.)
  - Update `status` enum to include 'Pending', 'Admitted', 'Discharged'.

- **Table: inpatient_daily_records** (New)
  - `record_id` (PK)
  - `admission_id` (FK)
  - `doctor_id` (FK)
  - `visit_date` (datetime)
  - `daily_notes` (text)
  - `treatment_plan` (text)

- **Table: discharge_summaries** (New)
  - `summary_id` (PK)
  - `admission_id` (FK)
  - `discharge_date` (datetime)
  - `final_diagnosis` (text)
  - `summary` (text)
  - `advice` (text)
  - `follow_up_date` (date)

## 2. Doctor Module: Admission Request
- **File:** `doctor_dashboard.php` (or modal)
  - Add "Admit Patient" button in the consultation view.
  - Form to select Ward Type, Reason, and Submit.
- **File:** `request_admission.php` (New)
  - Handle POST request to create 'Pending' admission record.

## 3. Admin Module: Room Assignment
- **File:** `admin_dashboard.php` (?section=room-management)
  - Add "Pending Admissions" tab/section.
  - List requests with Patient Name, Requested Ward Type, Doctor.
  - Action: "Assign Room".
    - Modal to select available Ward (filter by type) -> Select Room.
    - On Save: Update `admissions` status to 'Admitted', link `room_id`. Update `rooms` status to 'Occupied'.
    - Auto-create initial Bill entry?

## 4. Inpatient Dashboard
- **File:** `inpatient_manager.php` (New - unified view for Doc/Nurse)
  - List Admitted Patients.
  - View Patient Details (Bed info).
  - Add Daily Notes.
  - View Bill Estimator.

## 5. Billing & Discharge
- **File:** `process_discharge.php` (New)
  - Doctor submits discharge form.
  - Calculate total days * room rate.
  - Add Lab Fees + Prescriptions linked to this admission (join by date range).
  - Generate Final Bill.
  - Update Admission Status -> 'Discharged'.
  - Update Room Status -> 'Available' (or 'Cleaning').

## 6. Patient View
- **File:** `patient_dashboard.php`
  - Show "Current Admission" card if status is 'Admitted'.
  - Show Room Number, Doctor, Running Bill.

// turbo-all
