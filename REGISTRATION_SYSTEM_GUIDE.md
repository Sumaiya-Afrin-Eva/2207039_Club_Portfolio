# Event Registration System - Complete Guide

## Overview
The Photography Club website now features a complete, fully-functional event registration system that allows users to register for events with proper validation, seat management, and confirmation messages.

---

## Features

### 1. **Registration Form**
The registration form includes the following fields:

- **Full Name** (required) - Text input
- **Email** (required) - Email validation
- **Phone Number** (required) - Tel input
- **Address** (required) - Text input
- **Institute/Organization** (required) - Text input
- **Gender** (required) - Select dropdown
  - Male
  - Female
  - Other
  - Prefer not to say
- **Academic Year** (required) - Select dropdown
  - 1st Year
  - 2nd Year
  - 3rd Year
  - 4th Year
  - Other
- **Photography Experience** (required) - Select dropdown
  - Beginner
  - Intermediate
  - Advanced
  - Professional

### 2. **Seat Management**
- Real-time capacity display showing "X seats left"
- Automatic seat count update when users register
- "Event Full" message displays when capacity is reached
- Register button is disabled when event is at full capacity

### 3. **User Interface Components**

#### Event Card Display
```
- Event title with description
- Status badge (Upcoming/Ongoing/Completed)
- Category badge
- Price display
- "View Details" button
- "Remind Me" button
```

#### Event Detail Modal
```
- Event banner image
- Complete event information
- Countdown timer (for upcoming events)
- Organizer details
- Event agenda
- Required equipment list
- Seat availability: "X seats left"
- "Register Now" button
- Comments section
- "Remind Me" button
```

#### Registration Modal
```
- Event title in header
- Multi-row form layout with 2 columns
- All required fields
- Form validation
- "Complete Registration" button
```

#### Confirmation Modal
```
- Success checkmark icon with animation
- "Registration Successful!" heading
- Confirmation message with email
- Event details summary
- Location, date, and time
- "Done" button to close
```

### 4. **Registration Flow**

**Step 1:** User views events and clicks "View Details"
```
- Event detail modal opens
- Shows all event information
- Displays available seats
- "Register Now" button is visible (if seats available)
```

**Step 2:** User clicks "Register Now"
```
- Registration form modal opens
- Pre-filled event title shown
- Form has 7 required fields
```

**Step 3:** User fills form and submits
```
- Client-side validation checks all fields
- Data sent to API: /api/events.php?action=register
- Server validates fields
- Checks event capacity
- Checks for duplicate registration (same email)
- Creates registration record
- Updates event's registered_count
- Saves to registrations.json
```

**Step 4:** Success or Error
```
If Success:
- Confirmation modal displays
- Shows registration details
- Email address displayed
- Event date/time/location shown
- User clicks "Done" to close

If Error:
- Alert message shows
- Reasons could be:
  - Event is full
  - Already registered with same email
  - Missing required field
  - Server error
```

---

## Data Storage

### Registration Data Structure
```json
{
  "id": "id_1714003620_5234",
  "event_id": "event_001",
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "address": "123 Main St, City",
  "institute": "University XYZ",
  "gender": "Male",
  "academic_year": "2nd Year",
  "experience": "Intermediate",
  "registered_at": "2024-05-05 10:30:45"
}
```

### Files
- **Registrations:** `/data/registrations.json`
- **Events:** `/data/events.json` (includes registered_count)

---

## API Endpoints

### 1. Register for Event
**Endpoint:** `POST /api/events.php?action=register`

**Request Body:**
```json
{
  "event_id": "event_001",
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "address": "123 Main St",
  "institute": "University XYZ",
  "gender": "Male",
  "academic_year": "2nd Year",
  "experience": "Intermediate"
}
```

**Response (Success):**
```json
{
  "status": "success",
  "message": "Successfully registered!",
  "data": {
    "id": "id_1714003620_5234",
    "event_id": "event_001",
    "name": "John Doe",
    "email": "john@example.com",
    ...
    "registered_at": "2024-05-05 10:30:45"
  }
}
```

**Response (Error):**
```json
{
  "status": "error",
  "message": "Event is full"
}
```

**Validation Rules:**
- All fields are required
- Event must exist
- Event must have available seats
- User cannot register twice with same email for same event

---

## Frontend Implementation

### HTML (home.html)
- Registration Modal with form
- Confirmation Modal with success message
- Event card with "Register Now" button
- Seat availability display

### JavaScript (home-events.js)
- `openRegistrationForm()` - Opens registration modal
- `closeRegistrationForm()` - Closes registration modal
- `submitRegistration(e)` - Handles form submission
- `closeConfirmation()` - Closes confirmation modal
- Form validation and error handling

### CSS (home.css)
- `.registration-modal` - Form container styling
- `.form-row` - 2-column grid layout
- `.form-group` - Individual form field styling
- `.btn-primary` - Primary button styling
- `.confirmation-modal` - Success message styling
- `.confirmation-details` - Event details display
- Responsive design for mobile devices

---

## Backend Implementation

### PHP (api/events.php)
- `POST /api/events.php?action=register`
- Validates all required fields
- Checks event exists and has capacity
- Prevents duplicate registrations
- Creates registration record
- Updates event registered_count
- Returns JSON response

### PHP (api/config.php)
- `respond()` function for JSON responses
- `load_json()` and `save_json()` for file operations
- `generate_id()` for unique registration IDs
- `get_timestamp()` for registration timestamps

---

## Styling Features

### Modern Design Elements
- Gradient backgrounds for buttons
- Smooth transitions and hover effects
- Color-coded status badges
- Icon indicators for fields
- Responsive grid layout
- Professional form design

### Color Scheme
- Primary: `#38bdf8` (Sky Blue)
- Success: `#10b981` (Emerald)
- Warning: `#fbbf24` (Amber)
- Background: `#0f172a` (Dark Blue)
- Text: `#f1f5f9` (Off White)

### Animations
- Success icon scale and rotation animation
- Button hover effects with transform
- Smooth focus states on form inputs

---

## Testing the Registration System

### Test Case 1: Successful Registration
1. Navigate to "Upcoming Events"
2. Click "View Details" on an event
3. Click "Register Now" button
4. Fill all form fields
5. Click "Complete Registration"
6. Verify confirmation modal appears

### Test Case 2: Event Full
1. Register for an event with limited seats
2. Once full, try to register again
3. Verify error message or disabled button

### Test Case 3: Duplicate Registration
1. Register with same email for same event twice
2. Verify error: "Already registered for this event"

### Test Case 4: Missing Fields
1. Try to submit form with empty fields
2. Verify validation messages

---

## Files Modified/Created

### Updated Files
- `home.html` - Added Gender field and improved form layout
- `home-events.js` - Updated submitRegistration() with gender field
- `api/events.php` - Updated API handler for gender field
- `home.css` - Added comprehensive form and modal styling

### Key Components
- Registration Form Modal
- Confirmation Modal
- Event Detail Modal with Registration Button
- Styled Form Fields
- Success Message Display

---

## Future Enhancements

Potential improvements for the registration system:

1. **Email Confirmation**
   - Send verification email to registrant
   - Email receipt with event details

2. **Payment Integration**
   - Stripe/PayPal for paid events
   - Invoice generation

3. **Attendance Tracking**
   - Check-in system
   - Attendance marks

4. **Notifications**
   - Email reminders before event
   - SMS notifications
   - In-app notifications

5. **Certificate Generation**
   - Auto-generate certificates
   - Personalized PDF

6. **Analytics**
   - Registration statistics
   - Demographic reports
   - Event reports

---

## Support

For issues or questions about the registration system:
1. Check the console for JavaScript errors
2. Check the network tab for API calls
3. Verify JSON files are readable/writable
4. Check PHP error logs
