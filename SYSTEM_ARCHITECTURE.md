# Event Registration System - Architecture & Flow

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Photography Club Website                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  FRONTEND (Client-Side)          BACKEND (Server-Side)          │
│  ─────────────────────           ──────────────────             │
│                                                                   │
│  home.html                        api/events.php                │
│  ├─ Event Cards                   ├─ Register Handler           │
│  ├─ Event Detail Modal            ├─ Validation                 │
│  ├─ Registration Form             ├─ Capacity Check             │
│  └─ Confirmation Modal            └─ Data Persistence           │
│                                                                   │
│  home-events.js                   api/config.php                │
│  ├─ Load Events                   ├─ Helper Functions           │
│  ├─ Display Events                ├─ JSON Operations            │
│  ├─ Handle Registration           └─ CORS Headers               │
│  └─ Show Confirmation                                            │
│                                   home.php                       │
│  home.css                         └─ Initialization             │
│  ├─ Form Styling                                                 │
│  ├─ Modal Styling                 DATA (JSON Files)              │
│  └─ Button Styling                ──────────────────            │
│                                   /data/                        │
│                                   ├─ events.json                │
│                                   ├─ registrations.json          │
│                                   ├─ comments.json              │
│                                   └─ reminders.json             │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## User Registration Flow

```
START
  │
  ├─→ Browse Homepage
  │     └─→ See Upcoming Events (Event Cards)
  │
  ├─→ Click "View Details" on Event Card
  │     └─→ Event Detail Modal Opens
  │         • Banner Image
  │         • Title, Date, Time
  │         • Organizer Info
  │         • Agenda & Equipment
  │         • "X seats left" Display
  │         • Comments Section
  │         • Register Now Button
  │
  ├─→ Check Seats Available?
  │     │
  │     ├─ NO (Event Full)
  │     │   └─→ Register Button Disabled
  │     │       "Event Full" Message
  │     │
  │     └─ YES (Seats Available)
  │         └─→ Continue
  │
  ├─→ Click "Register Now"
  │     └─→ Registration Form Modal Opens
  │         • Full Name (required)
  │         • Email (required)
  │         • Phone (required)
  │         • Address (required)
  │         • Institute (required)
  │         • Gender (required)
  │         • Academic Year (required)
  │         • Experience (required)
  │
  ├─→ Fill All Form Fields
  │     └─→ Client-side validation runs
  │
  ├─→ Click "Complete Registration"
  │     └─→ Form Data to API
  │         POST /api/events.php?action=register
  │
  ├─→ Server Validation
  │     └─→ Check:
  │         • All fields exist
  │         • Event exists
  │         • Seats available
  │         • Not duplicate (same email)
  │
  ├─→ Registration Valid?
  │     │
  │     ├─ NO (Error)
  │     │   └─→ Alert User with Error Message
  │     │       "Already registered"
  │     │       "Event full"
  │     │       "Missing field"
  │     │
  │     └─ YES (Success)
  │         └─→ Create Registration Record
  │             • Generate unique ID
  │             • Store all user data
  │             • Get timestamp
  │             • Increment event.registered_count
  │             • Save to registrations.json
  │             • Save updated events.json
  │
  ├─→ Show Confirmation Modal
  │     └─→ Display:
  │         ✓ Success icon (animated)
  │         • "Registration Successful!"
  │         • Event name
  │         • Email address
  │         • Date, time, location
  │         • "Done" button
  │
  ├─→ Click "Done" in Confirmation
  │     └─→ Close modals
  │         • Reload events
  │         • Update seat counts
  │
  └─→ END - Successfully Registered!
```

---

## Data Flow Diagram

```
┌──────────────────────┐
│   User Fills Form    │ (Frontend)
│ - Name, Email, etc   │
└──────────┬───────────┘
           │ Form Submission
           ↓
┌──────────────────────────────────────┐
│   Form Validation (JavaScript)       │ (Frontend)
│ - Check required fields             │
│ - Validate email format             │
│ - Check all fields filled           │
└──────────┬───────────────────────────┘
           │ Valid?
           ├─ NO → Show Error Alert
           │
           └─ YES
              │
              ↓
┌──────────────────────────────────────┐
│  JSON.stringify(registrationData)    │ (Frontend)
│  POST to /api/events.php             │
└──────────┬───────────────────────────┘
           │ HTTP POST Request
           ↓
┌────────────────────────────────────────────────┐
│   API Process Registration (PHP)               │ (Backend)
├────────────────────────────────────────────────┤
│ 1. Parse JSON input                           │
│ 2. Validate all required fields               │
│ 3. Load events.json                           │
│ 4. Check event exists                         │
│ 5. Check registered_count < capacity          │
│ 6. Load registrations.json                    │
│ 7. Check no duplicate (same email + event)    │
│ 8. Generate unique registration ID            │
│ 9. Get current timestamp                      │
│ 10. Create registration record                │
│ 11. Add to registrations array                │
│ 12. Increment event.registered_count          │
│ 13. Save registrations.json                   │
│ 14. Save events.json                          │
│ 15. Return success response + registration    │
└──────────┬───────────────────────────────────┘
           │ JSON Response
           │ {status: "success", data: {...}}
           ↓
┌──────────────────────────────────────┐
│  Handle Response (JavaScript)        │ (Frontend)
│ - Parse JSON response                │
│ - Check status field                 │
└──────────┬───────────────────────────┘
           │
           ├─ Status: "error"
           │  └─→ Show error alert
           │      "Event is full"
           │      "Already registered"
           │
           └─ Status: "success"
              └─→ Update event registered_count
                  Show confirmation modal
                  Display registration details
                  Reload events
```

---

## Database Schema (JSON Structure)

### events.json
```
[
  {
    id: unique,
    title: string,
    description: string,
    category: "Outdoor" | "Workshop" | "Competition",
    status: "upcoming" | "ongoing" | "completed",
    date: YYYY-MM-DD,
    time: HH:MM AM/PM,
    end_time: HH:MM AM/PM,
    location: string,
    organizer: string,
    organizer_bio: string,
    organizer_image: url,
    price: number,
    capacity: number,           ← Max seats
    registered_count: number,   ← Current registrations
    image_url: url,
    required_equipment: [string],
    agenda: [{time, activity}],
    highlights: [string],
    created_at: YYYY-MM-DD
  }
]
```

### registrations.json
```
[
  {
    id: unique,                          ← Generated by API
    event_id: references events.id,
    name: string,                        ← User's full name
    email: string,                       ← User's email
    phone: string,                       ← Phone number
    address: string,                     ← Address
    institute: string,                   ← School/University
    gender: "Male" | "Female" | "Other" | "Prefer not to say",
    academic_year: "1st" | "2nd" | "3rd" | "4th" | "Other",
    experience: "Beginner" | "Intermediate" | "Advanced" | "Professional",
    registered_at: YYYY-MM-DD HH:MM:SS  ← Timestamp
  }
]
```

---

## API Endpoint Details

### POST /api/events.php?action=register

#### REQUEST
```
Method: POST
Content-Type: application/json

Body: {
  event_id: string (required),
  name: string (required),
  email: string (required),
  phone: string (required),
  address: string (required),
  institute: string (required),
  gender: string (required),
  academic_year: string (required),
  experience: string (required)
}
```

#### RESPONSE (Success: 200)
```json
{
  "status": "success",
  "message": "Successfully registered!",
  "data": {
    "id": "id_1714003620_5234",
    "event_id": "event_001",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1-555-0001",
    "address": "123 Main St",
    "institute": "University",
    "gender": "Male",
    "academic_year": "2nd Year",
    "experience": "Intermediate",
    "registered_at": "2026-05-05 10:30:45"
  }
}
```

#### RESPONSE (Error: 400)
```json
{
  "status": "error",
  "message": "Event is full"
}
```

---

## UI Component Hierarchy

```
App
├─ Header
│  ├─ Logo
│  └─ Navigation
│
├─ Events Section
│  ├─ Search Bar
│  ├─ Filter Buttons
│  ├─ View Toggle (Calendar/Cards)
│  │
│  └─ Events Cards Container
│     └─ Event Card (Multiple)
│        ├─ Image
│        ├─ Badges (Status, Category)
│        ├─ Title & Description
│        └─ "View Details" Button
│
├─ Modals
│  ├─ Event Detail Modal
│  │  ├─ Banner
│  │  ├─ Event Info
│  │  ├─ Agenda
│  │  ├─ Equipment
│  │  ├─ Comments Section
│  │  ├─ Seats Info
│  │  └─ "Register Now" Button
│  │
│  ├─ Registration Modal (Opens on "Register Now")
│  │  ├─ Form Header
│  │  ├─ Form Fields
│  │  │  ├─ Name (text)
│  │  │  ├─ Email (email)
│  │  │  ├─ Phone (tel)
│  │  │  ├─ Address (text)
│  │  │  ├─ Institute (text)
│  │  │  ├─ Gender (select)
│  │  │  ├─ Academic Year (select)
│  │  │  └─ Experience (select)
│  │  ├─ Submit Button
│  │  └─ Close Button
│  │
│  └─ Confirmation Modal (Shows after success)
│     ├─ Success Icon
│     ├─ Success Message
│     ├─ Event Details
│     └─ "Done" Button
│
└─ Footer
   └─ Copyright
```

---

## Validation Logic

### Client-Side Validation
```javascript
// Required field check
if (!name || !email || !phone || !address || 
    !institute || !gender || !academic_year || !experience) {
  return false; // Form invalid
}

// Email format check
if (!email.includes('@')) {
  return false; // Invalid email
}

// Phone format check
if (phone.length < 10) {
  return false; // Invalid phone
}
```

### Server-Side Validation
```php
// Required fields check
$required = ['event_id', 'name', 'email', 'phone', 
             'address', 'institute', 'gender', 
             'academic_year', 'experience'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        respond('error', "Field '$field' is required");
    }
}

// Event exists check
$event = find_event_by_id($input['event_id']);
if (!$event) {
    respond('error', 'Event not found');
}

// Capacity check
if ($event['registered_count'] >= $event['capacity']) {
    respond('error', 'Event is full');
}

// Duplicate check
if (is_already_registered($input['event_id'], $input['email'])) {
    respond('error', 'Already registered for this event');
}
```

---

## Error Handling Map

```
User Action → Possible Errors → Error Message
───────────────────────────────────────────────

Register    → Missing Name     → "Field 'name' is required"
            → Missing Email    → "Field 'email' is required"
            → Invalid Email    → HTML5 validation
            → Missing Phone    → "Field 'phone' is required"
            → Missing Address  → "Field 'address' is required"
            → Missing Institute → "Field 'institute' is required"
            → Missing Gender   → "Field 'gender' is required"
            → Missing Year     → "Field 'academic_year' is required"
            → Missing Exp.     → "Field 'experience' is required"
            
            → Event Not Found  → "Event not found"
            → Event Full       → "Event is full"
            → Duplicate Reg.   → "Already registered for this event"
            → Server Error     → "Registration failed"
```

---

## Performance Flow

```
User Registration Process Timeline:

t=0ms    User clicks "Register Now"
         └─ Registration modal opens instantly

t=50ms   User starts filling form
         └─ Client-side validation runs on input

t=2000ms User clicks "Complete Registration"
         └─ Form validation checks

t=2010ms HTTP POST request sent to API
         └─ API receives request

t=2050ms API validates all fields
         └─ Loads JSON files from disk

t=2100ms API creates registration record
         └─ Saves to registrations.json

t=2150ms API saves updated events.json
         └─ Returns JSON response

t=2160ms Response received in frontend
         └─ Parses JSON

t=2170ms Confirmation modal displays
         └─ Shows success message

t=2200ms User sees confirmation
         └─ With animation

t=3200ms User clicks "Done"
         └─ Modals close

t=3210ms Events reload
         └─ Seat counts update

t=3300ms UI shows new registered count
         └─ Page fully updated
```

---

## File Dependencies

```
home.html
├─ Requires: home-events.js
│  ├─ Requires: api/events.php (API calls)
│  │  ├─ Requires: api/config.php (helper functions)
│  │  └─ Requires: data/events.json (read)
│  │  └─ Requires: data/registrations.json (write)
│  └─ Requires: home.css (styling)
│
└─ Requires: home.js (gallery)
   ├─ Requires: home.css (styling)
   └─ Local storage (browser)
```

---

## Success Metrics

- ✅ User can view events
- ✅ User can register for events
- ✅ Seat capacity is enforced
- ✅ Duplicate registrations prevented
- ✅ Confirmation messages displayed
- ✅ Data persisted to JSON files
- ✅ Form validation works
- ✅ Error messages clear
- ✅ UI updates in real-time
- ✅ Mobile responsive

