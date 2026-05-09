# Registration System - Example Test Data

## Sample Registration JSON
This is what gets stored in `/data/registrations.json` when users register:

```json
[
    {
        "id": "id_1714003620_5234",
        "event_id": "event_001",
        "name": "Sarah Johnson",
        "email": "sarah.johnson@example.com",
        "phone": "+1-555-0101",
        "address": "456 Oak Avenue, Photography City, PC 12345",
        "institute": "City University",
        "gender": "Female",
        "academic_year": "2nd Year",
        "experience": "Intermediate",
        "registered_at": "2026-05-05 14:30:22"
    },
    {
        "id": "id_1714003750_8192",
        "event_id": "event_001",
        "name": "Michael Chen",
        "email": "michael.chen@example.com",
        "phone": "+1-555-0202",
        "address": "789 Pine Road, Photo Town, PT 54321",
        "institute": "Technical Institute",
        "gender": "Male",
        "academic_year": "3rd Year",
        "experience": "Beginner",
        "registered_at": "2026-05-05 15:45:10"
    },
    {
        "id": "id_1714003925_3456",
        "event_id": "event_002",
        "name": "Emily Rodriguez",
        "email": "emily.rodriguez@example.com",
        "phone": "+1-555-0303",
        "address": "321 Maple Lane, Art Valley, AV 67890",
        "institute": "Art & Design College",
        "gender": "Female",
        "academic_year": "1st Year",
        "experience": "Advanced",
        "registered_at": "2026-05-05 16:20:55"
    }
]
```

## Test Registration Requests

### Test 1: Successful Registration
**HTTP Method:** POST  
**URL:** `http://localhost/Club_Portfolio/api/events.php?action=register`

**Request Body:**
```json
{
  "event_id": "event_001",
  "name": "Alex Thompson",
  "email": "alex.thompson@example.com",
  "phone": "+1-555-0404",
  "address": "555 Elm Street, Photo District",
  "institute": "Photography Academy",
  "gender": "Other",
  "academic_year": "4th Year",
  "experience": "Professional"
}
```

**Expected Response:**
```json
{
  "status": "success",
  "message": "Successfully registered!",
  "data": {
    "id": "id_1714004050_7890",
    "event_id": "event_001",
    "name": "Alex Thompson",
    "email": "alex.thompson@example.com",
    "phone": "+1-555-0404",
    "address": "555 Elm Street, Photo District",
    "institute": "Photography Academy",
    "gender": "Other",
    "academic_year": "4th Year",
    "experience": "Professional",
    "registered_at": "2026-05-05 17:10:45"
  }
}
```

---

### Test 2: Event Full Error
**Scenario:** Trying to register for an event with 0 seats left

**Request Body:**
```json
{
  "event_id": "event_001",
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1-555-0505",
  "address": "999 Camera Lane",
  "institute": "Film School",
  "gender": "Male",
  "academic_year": "2nd Year",
  "experience": "Beginner"
}
```

**Expected Response:**
```json
{
  "status": "error",
  "message": "Event is full"
}
```

---

### Test 3: Duplicate Registration Error
**Scenario:** Trying to register same email for same event twice

**Request Body:**
```json
{
  "event_id": "event_001",
  "name": "Sarah Johnson",
  "email": "sarah.johnson@example.com",
  "phone": "+1-555-0101",
  "address": "456 Oak Avenue, Photography City, PC 12345",
  "institute": "City University",
  "gender": "Female",
  "academic_year": "2nd Year",
  "experience": "Intermediate"
}
```

**Expected Response:**
```json
{
  "status": "error",
  "message": "Already registered for this event"
}
```

---

### Test 4: Missing Required Field
**Scenario:** Trying to register without name field

**Request Body:**
```json
{
  "event_id": "event_001",
  "email": "test@example.com",
  "phone": "+1-555-9999",
  "address": "123 Test St",
  "institute": "Test School",
  "gender": "Male",
  "academic_year": "1st Year"
}
```

**Expected Response:**
```json
{
  "status": "error",
  "message": "Field 'experience' is required"
}
```

---

### Test 5: Event Not Found
**Scenario:** Trying to register for non-existent event

**Request Body:**
```json
{
  "event_id": "invalid_event",
  "name": "Jane Doe",
  "email": "jane@example.com",
  "phone": "+1-555-9999",
  "address": "123 Test St",
  "institute": "Test School",
  "gender": "Female",
  "academic_year": "1st Year",
  "experience": "Beginner"
}
```

**Expected Response:**
```json
{
  "status": "error",
  "message": "Event not found"
}
```

---

## Testing with cURL

### Register for Event
```bash
curl -X POST "http://localhost/Club_Portfolio/api/events.php?action=register" \
  -H "Content-Type: application/json" \
  -d '{
    "event_id": "event_001",
    "name": "Test User",
    "email": "test@example.com",
    "phone": "+1-555-0001",
    "address": "123 Test Street",
    "institute": "Test University",
    "gender": "Male",
    "academic_year": "1st Year",
    "experience": "Beginner"
  }'
```

### Get All Registrations for Event
```bash
curl "http://localhost/Club_Portfolio/api/events.php?action=get_registrations&event_id=event_001"
```

---

## Updated Events Structure

After registration, the event's `registered_count` is incremented. 

**Before Registration:**
```json
{
  "id": "event_001",
  "title": "Photo Walk",
  "capacity": 20,
  "registered_count": 5
}
```

**After One Registration:**
```json
{
  "id": "event_001",
  "title": "Photo Walk",
  "capacity": 20,
  "registered_count": 6
}
```

---

## UI Flow - Step by Step

### Step 1: Browse Events
- User sees event cards with:
  - Event title
  - Category badge
  - Price
  - Status badge
  - "View Details" button

### Step 2: View Event Details
- Modal opens showing:
  - Event banner
  - Title, date, time, location
  - Organizer info
  - Agenda
  - Equipment list
  - **"X seats left" display**
  - "Register Now" button (enabled if seats available)
  - Comments section

### Step 3: Click Register
- Registration form modal opens
- Form shows:
  - Event name in header
  - 7 form fields (2-column layout)
  - "Complete Registration" button

### Step 4: Fill & Submit
- User enters all required information
- Client validates (HTML5 + JavaScript)
- Sends to API

### Step 5: Confirmation
- Success modal displays:
  - Green checkmark animation
  - "Registration Successful!" message
  - Confirmation email address
  - Event details
  - "Done" button

### Step 6: Auto-Update
- Event card updates with new registered count
- Seat availability updates in real-time
- UI shows updated capacity

---

## Form Validation

### Client-Side (Browser)
- HTML5 required attribute
- Email field validation
- Tel field validation
- Custom JavaScript validation

### Server-Side (PHP)
- Check empty fields
- Validate event exists
- Validate seat availability
- Check duplicate registration
- All required fields present

---

## Error Handling

### Error Messages Users See

1. **"Event is full"** - No more seats available
2. **"Already registered for this event"** - Same email registered
3. **"Field '[name]' is required"** - Missing required field
4. **"Event not found"** - Invalid event ID
5. **"Registration failed"** - Server error
6. **Form Validation Errors** - Missing name, invalid email, etc.

---

## Registration Statistics

### Event 001: Photo Walk
- **Capacity:** 20 seats
- **Registered:** 6 users
- **Available:** 14 seats

### Event 002: Editing Workshop
- **Capacity:** 30 seats
- **Registered:** 12 users
- **Available:** 18 seats

### Event 003: Photography Competition
- **Capacity:** 50 seats
- **Registered:** 28 users
- **Available:** 22 seats

---

## Quick Testing Checklist

- [ ] Can view events
- [ ] Can click "View Details"
- [ ] Can see event information
- [ ] Can see "X seats left"
- [ ] Can click "Register Now"
- [ ] Registration form opens
- [ ] Form has all required fields
- [ ] Can fill form successfully
- [ ] Can submit form
- [ ] Confirmation modal appears
- [ ] Confirmation shows correct data
- [ ] Can close confirmation
- [ ] Seat count updates
- [ ] Cannot register twice from same email
- [ ] Full events show "Event Full"

