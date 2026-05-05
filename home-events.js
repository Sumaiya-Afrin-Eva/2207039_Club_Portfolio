// API Configuration
const API_BASE = 'api/events.php';

// Event Management System
class EventManager {
  constructor() {
    this.events = [];
    this.currentEventId = null;
    this.currentCalendarDate = new Date();
    this.filteredEvents = [];
    this.reminderEmail = '';
  }

  async loadEvents() {
    try {
      const response = await fetch(`${API_BASE}?action=get_events`);
      const data = await response.json();
      if (data.status === 'success') {
        this.events = data.data || [];
        this.filterAndDisplayEvents();
        this.initializeCalendar();
      }
    } catch (error) {
      console.error('Failed to load events:', error);
    }
  }

  filterAndDisplayEvents(category = 'all', searchQuery = '') {
    let filtered = this.events.filter(e => e.status === 'upcoming' || e.status === 'ongoing');

    if (category !== 'all') {
      filtered = filtered.filter(e => e.category === category);
    }

    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(e =>
        e.title.toLowerCase().includes(query) ||
        e.description.toLowerCase().includes(query)
      );
    }

    this.filteredEvents = filtered;
    this.displayEventCards(filtered);

    // Display past events
    const pastEvents = this.events.filter(e => e.status === 'completed');
    this.displayPastEvents(pastEvents);
  }

  displayEventCards(events) {
    const container = document.getElementById('eventsContainer');
    if (!container) return;

    if (events.length === 0) {
      container.innerHTML = '<div class="no-events-message">No events found.</div>';
      return;
    }

    container.innerHTML = events.map(event => {
      const seatsLeft = event.capacity - event.registered_count;
      const capacityPercent = (event.registered_count / event.capacity) * 100;

      return `
        <div class="event-card" onclick="eventManager.showEventDetail('${event.id}')">
          <div class="event-card-image">
            <img src="${event.image_url}" alt="${event.title}" crossorigin="anonymous" />
          </div>
          <div class="event-card-body">
            <div class="event-card-badges">
              <span class="event-card-badge status">${this.getStatusBadge(event.status)}</span>
              <span class="event-card-badge category">${event.category}</span>
              ${event.price > 0 ? `<span class="event-card-badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">$${event.price.toFixed(2)}</span>` : ''}
            </div>
            <h3 class="event-card-title">${event.title}</h3>
            <p class="event-card-description">${event.description}</p>
            
            <div class="event-card-meta">
              <div class="event-card-meta-item">
                📅 ${this.formatDate(event.date)}
              </div>
              <div class="event-card-meta-item">
                🕐 ${event.time}
              </div>
            </div>

            <div class="event-card-capacity">
              <span>${seatsLeft} seats left</span>
              <div style="width: 100px; margin-left: 10px;">
                <div class="capacity-bar">
                  <div class="capacity-fill" style="width: ${capacityPercent}%"></div>
                </div>
              </div>
            </div>

            <div class="event-card-buttons">
              <button class="event-card-btn view" onclick="event.stopPropagation(); eventManager.showEventDetail('${event.id}')">View Details</button>
              <button class="event-card-btn remind" onclick="event.stopPropagation(); eventManager.setReminder()">🔔 Remind Me</button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  displayPastEvents(events) {
    const container = document.getElementById('pastEventsContainer');
    if (!container) return;

    if (events.length === 0) {
      container.innerHTML = '<p style="text-align: center; color: #cbd5f5;">No past events yet.</p>';
      return;
    }

    container.innerHTML = events.map(event => `
      <div class="event-card" onclick="eventManager.showEventDetail('${event.id}')">
        <div class="event-card-image" style="opacity: 0.7;">
          <img src="${event.image_url}" alt="${event.title}" crossorigin="anonymous" />
        </div>
        <div class="event-card-body">
          <div class="event-card-badges">
            <span class="event-card-badge status" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">✓ Completed</span>
            <span class="event-card-badge category">${event.category}</span>
          </div>
          <h3 class="event-card-title">${event.title}</h3>
          <p class="event-card-description">${event.description}</p>
          <button class="btn" onclick="event.stopPropagation(); eventManager.showEventDetail('${event.id}')">View Highlights</button>
        </div>
      </div>
    `).join('');
  }

  getStatusBadge(status) {
    const badges = {
      'upcoming': '🟢 Upcoming',
      'ongoing': '🔵 Ongoing',
      'completed': '🔴 Completed'
    };
    return badges[status] || status;
  }

  formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  async showEventDetail(eventId) {
    const event = this.events.find(e => e.id === eventId);
    if (!event) return;

    this.currentEventId = eventId;
    this.reminderEmail = ''; // Reset reminder email

    // Populate event detail modal
    const bannerImg = document.getElementById('eventBanner');
    bannerImg.src = event.image_url;
    bannerImg.setAttribute('crossorigin', 'anonymous');
    document.getElementById('eventTitle').textContent = event.title;
    document.getElementById('eventStatus').className = `event-status ${event.status}`;
    document.getElementById('eventStatus').textContent = this.getStatusBadge(event.status);
    document.getElementById('eventCategory').textContent = event.category;
    document.getElementById('eventPrice').textContent = event.price > 0 ? `$${event.price.toFixed(2)}` : 'Free';
    
    document.getElementById('eventDate').textContent = this.formatDate(event.date);
    document.getElementById('eventTime').textContent = `${event.time} - ${event.end_time}`;
    document.getElementById('eventLocation').textContent = event.location;
    document.getElementById('eventCapacity').textContent = `${event.registered_count}/${event.capacity} registered`;
    document.getElementById('eventDescription').textContent = event.description;

    // Organizer info
    document.getElementById('organizerName').textContent = event.organizer;
    document.getElementById('organizerBio').textContent = event.organizer_bio;
    const organizerImg = document.getElementById('organizerImage');
    organizerImg.src = event.organizer_image;
    organizerImg.setAttribute('crossorigin', 'anonymous');

    // Agenda
    const agendaList = document.getElementById('agendaList');
    agendaList.innerHTML = event.agenda.map(item => `
      <div class="agenda-item">
        <div class="agenda-time">${item.time}</div>
        <div class="agenda-activity">${item.activity}</div>
      </div>
    `).join('');

    // Equipment
    const equipmentList = document.getElementById('equipmentList');
    equipmentList.innerHTML = event.required_equipment.map(item => `<li>✓ ${item}</li>`).join('');

    // Seats left
    const seatsLeft = event.capacity - event.registered_count;
    document.getElementById('seatsLeft').textContent = `${seatsLeft} seats left`;

    // Show countdown for upcoming events
    const countdownContainer = document.getElementById('countdownContainer');
    if (event.status === 'upcoming') {
      countdownContainer.style.display = 'block';
      this.updateCountdown(event.date);
    } else {
      countdownContainer.style.display = 'none';
    }

    // Load comments
    this.loadComments(eventId);

    // Registration button status
    const registerBtn = document.getElementById('registerBtn');
    if (seatsLeft <= 0) {
      registerBtn.textContent = '❌ Event Full';
      registerBtn.disabled = true;
      registerBtn.style.opacity = '0.5';
    } else {
      registerBtn.textContent = 'Register Now';
      registerBtn.disabled = false;
      registerBtn.style.opacity = '1';
    }

    // Show modal
    document.getElementById('eventDetailModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  updateCountdown(eventDate) {
    const resetCountdown = () => {
      const now = new Date();
      const event = new Date(eventDate);
      const diff = event - now;

      if (diff <= 0) {
        document.getElementById('countdownDays').textContent = '0';
        document.getElementById('countdownHours').textContent = '0';
        document.getElementById('countdownMinutes').textContent = '0';
        return;
      }

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
      const minutes = Math.floor((diff / 1000 / 60) % 60);

      document.getElementById('countdownDays').textContent = days;
      document.getElementById('countdownHours').textContent = hours;
      document.getElementById('countdownMinutes').textContent = minutes;
    };

    resetCountdown();
    setInterval(resetCountdown, 60000); // Update every minute
  }

  async loadComments(eventId) {
    try {
      const response = await fetch(`${API_BASE}?action=get_comments&event_id=${eventId}`);
      const data = await response.json();

      const commentsList = document.getElementById('commentsList');
      if (data.data && data.data.length > 0) {
        commentsList.innerHTML = data.data.map(comment => `
          <div class="comment-item">
            <div class="comment-header">
              <span class="comment-name">${escapeHtml(comment.name)}</span>
              <span class="comment-time">${this.formatDate(comment.created_at)}</span>
            </div>
            <p class="comment-text">${escapeHtml(comment.comment)}</p>
          </div>
        `).join('');
      } else {
        commentsList.innerHTML = '<p style="text-align: center; color: #cbd5f5;">No comments yet. Be the first to ask a question!</p>';
      }
    } catch (error) {
      console.error('Failed to load comments:', error);
    }
  }

  setReminder() {
    const email = prompt('Enter your email to set a reminder:');
    if (email && email.trim()) {
      this.reminderEmail = email;
      this.submitReminder();
    }
  }

  async submitReminder() {
    if (!this.reminderEmail) return;

    try {
      const response = await fetch(`${API_BASE}?action=set_reminder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          event_id: this.currentEventId,
          email: this.reminderEmail
        })
      });

      const data = await response.json();
      if (data.status === 'success') {
        alert('✓ Reminder set! You will receive a notification before this event.');
      } else {
        alert(data.message || 'Failed to set reminder');
      }
    } catch (error) {
      console.error('Failed to set reminder:', error);
      alert('Error setting reminder');
    }
  }

  initializeCalendar() {
    this.renderCalendar();
  }

  renderCalendar() {
    const year = this.currentCalendarDate.getFullYear();
    const month = this.currentCalendarDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    // Update header
    const monthYearSpan = document.getElementById('calendarMonthYear');
    if (monthYearSpan) {
      monthYearSpan.textContent = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    // Create calendar grid
    const calendarGrid = document.getElementById('calendarGrid');
    if (!calendarGrid) return;

    calendarGrid.innerHTML = '';

    // Day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
      const header = document.createElement('div');
      header.className = 'calendar-day-header';
      header.textContent = day;
      calendarGrid.appendChild(header);
    });

    // Empty cells before month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
      const emptyDay = document.createElement('div');
      emptyDay.className = 'calendar-day other-month';
      calendarGrid.appendChild(emptyDay);
    }

    // Calendar days
    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const dayEvents = this.events.filter(e =>
        e.date === dateStr && (e.status === 'upcoming' || e.status === 'ongoing')
      );

      const dayElement = document.createElement('div');
      dayElement.className = 'calendar-day';
      if (dayEvents.length > 0) {
        dayElement.style.borderColor = '#38bdf8';
      }

      const dayNumber = document.createElement('div');
      dayNumber.className = 'calendar-day-number';
      dayNumber.textContent = day;
      dayElement.appendChild(dayNumber);

      if (dayEvents.length > 0) {
        const eventsDiv = document.createElement('div');
        eventsDiv.className = 'calendar-day-events';
        dayEvents.slice(0, 2).forEach(event => {
          const eventSpan = document.createElement('div');
          eventSpan.className = 'calendar-day-event';
          eventSpan.textContent = event.title.substring(0, 10);
          eventSpan.title = event.title;
          eventSpan.onclick = (e) => {
            e.stopPropagation();
            this.showEventDetail(event.id);
          };
          eventsDiv.appendChild(eventSpan);
        });
        dayElement.appendChild(eventsDiv);
      }

      calendarGrid.appendChild(dayElement);
    }
  }

  nextMonth() {
    this.currentCalendarDate.setMonth(this.currentCalendarDate.getMonth() + 1);
    this.renderCalendar();
  }

  prevMonth() {
    this.currentCalendarDate.setMonth(this.currentCalendarDate.getMonth() - 1);
    this.renderCalendar();
  }
}

// Global event manager instance
let eventManager = new EventManager();

// UI Functions
function closeEventDetail() {
  document.getElementById('eventDetailModal').style.display = 'none';
  document.body.style.overflow = 'auto';
}

function openRegistrationForm() {
  const event = eventManager.events.find(e => e.id === eventManager.currentEventId);
  if (event) {
    document.getElementById('regEventTitle').textContent = event.title;
    document.getElementById('registrationModal').style.display = 'flex';
  }
}

function closeRegistrationForm() {
  document.getElementById('registrationModal').style.display = 'none';
}

function closeConfirmation() {
  document.getElementById('confirmationModal').style.display = 'none';
  closeRegistrationForm();
  closeEventDetail();
}

async function submitRegistration(e) {
  e.preventDefault();
  
  const form = document.getElementById('eventRegistrationForm');
  const formData = new FormData(form);
  const event = eventManager.events.find(ev => ev.id === eventManager.currentEventId);

  const registrationData = {
    event_id: eventManager.currentEventId,
    name: formData.get('name'),
    email: formData.get('email'),
    phone: formData.get('phone'),
    address: formData.get('address'),
    institute: formData.get('institute'),
    academic_year: formData.get('academic_year'),
    experience: formData.get('experience')
  };

  try {
    const response = await fetch(`${API_BASE}?action=register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(registrationData)
    });

    const data = await response.json();

    if (data.status === 'success') {
      // Update event registered count
      event.registered_count++;

      // Show confirmation modal
      document.getElementById('confirmEventName').textContent = event.title;
      document.getElementById('confirmEmail').textContent = registrationData.email;
      document.getElementById('confirmLocation').textContent = event.location;
      document.getElementById('confirmDate').textContent = eventManager.formatDate(event.date);
      document.getElementById('confirmTime').textContent = `${event.time} - ${event.end_time}`;

      document.getElementById('confirmationModal').style.display = 'flex';
      form.reset();

      // Reload events to update UI
      setTimeout(() => eventManager.loadEvents(), 1000);
    } else {
      alert(data.message || 'Registration failed');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error submitting registration');
  }
}

async function submitComment() {
  const name = document.getElementById('commentName').value.trim();
  const comment = document.getElementById('commentText').value.trim();

  if (!name || !comment) {
    alert('Please enter both name and comment');
    return;
  }

  try {
    const response = await fetch(`${API_BASE}?action=add_comment`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        event_id: eventManager.currentEventId,
        name: name,
        comment: comment
      })
    });

    const data = await response.json();

    if (data.status === 'success') {
      document.getElementById('commentName').value = '';
      document.getElementById('commentText').value = '';
      eventManager.loadComments(eventManager.currentEventId);
    } else {
      alert(data.message || 'Failed to post comment');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error posting comment');
  }
}

function setReminder() {
  eventManager.setReminder();
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Event listeners for toolbar
document.addEventListener('DOMContentLoaded', () => {
  // Load gallery and events
  if (typeof loadGallery === 'function') {
    loadGallery();
  }
  if (typeof setupExploreButton === 'function') {
    setupExploreButton();
  }
  if (typeof setupContactForm === 'function') {
    setupContactForm();
  }
  if (typeof setupSearchFunctionality === 'function') {
    setupSearchFunctionality();
  }

  // Load events
  eventManager.loadEvents();

  // Filter buttons
  const filterBtns = document.querySelectorAll('.filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.getAttribute('data-filter');
      const searchQuery = document.getElementById('eventSearchInput')?.value || '';
      eventManager.filterAndDisplayEvents(filter, searchQuery);
    });
  });

  // Search input
  const searchInput = document.getElementById('eventSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const activeFilter = document.querySelector('.filter-btn.active')?.getAttribute('data-filter') || 'all';
      eventManager.filterAndDisplayEvents(activeFilter, e.target.value);
    });
  }

  // Calendar toggle
  const viewToggleBtn = document.getElementById('viewToggleBtn');
  const calendarView = document.getElementById('calendarView');
  const eventsCardsView = document.querySelector('section[id="events"] .cards');

  if (viewToggleBtn && calendarView) {
    viewToggleBtn.addEventListener('click', () => {
      const isCalendarVisible = calendarView.style.display !== 'none';
      calendarView.style.display = isCalendarVisible ? 'none' : 'block';
      if (eventsCardsView) {
        eventsCardsView.style.display = isCalendarVisible ? 'grid' : 'none';
      }
    });
  }

  // Calendar navigation
  const prevMonthBtn = document.getElementById('prevMonth');
  const nextMonthBtn = document.getElementById('nextMonth');

  if (prevMonthBtn) {
    prevMonthBtn.addEventListener('click', () => eventManager.prevMonth());
  }
  if (nextMonthBtn) {
    nextMonthBtn.addEventListener('click', () => eventManager.nextMonth());
  }

  // Close modals on background click
  const modals = document.querySelectorAll('.modal');
  modals.forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
      }
    });
  });
});
