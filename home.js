// Configuration settings home.js file
const CONFIG = {
  MAX_PHOTO_SIZE: 5 * 1024 * 1024, // Max upload size (5MB)
  PHOTOS_PER_PAGE: 12, // Photos shown on main gallery
  STORAGE_KEY: 'photoclub_photos',// Key for localStorage
  API_BASE: '/Club_Portfolio/api/', // API base URL
  // Default images (shown if no saved data)
  DEFAULT_PHOTOS: [
    {
      id: 'default-0',
      src: 'kuet.jpeg',
      alt: 'Photo 0',
      type: 'remote'
    },
    {
      id: 'default-1',
      src: 'https://images.unsplash.com/photo-1495567720989-cebdbdd97913?w=400&h=300&fit=crop',
      alt: 'Photo 1',
      type: 'remote'
    },
    {
      id: 'default-2',
      src: 'https://images.unsplash.com/photo-1492724441997-5dc865305da7?w=400&h=300&fit=crop',
      alt: 'Photo 2',
      type: 'remote'
    },
    {
      id: 'default-3',
      src: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400&h=300&fit=crop',
      alt: 'Photo 3',
      type: 'remote'
    },
    {
      id: 'default-4',
      src: 'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?w=400&h=300&fit=crop',
      alt: 'Photo 4',
      type: 'remote'
    },
    {
      id: 'default-5',
      src: 'https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?w=400&h=300&fit=crop',
      alt: 'Photo 5',
      type: 'remote'
    },
    {
      id: 'default-6',
      src: 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=400&h=300&fit=crop',
      alt: 'Photo 6',
      type: 'remote'
    },
    {
      id: 'default-7',
      src: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=400&h=300&fit=crop',
      alt: 'Photo 7',
      type: 'remote'
    },
    {
      id: 'default-8',
      src: 'https://images.unsplash.com/photo-1496307042754-b4aa456c4a2d?w=400&h=300&fit=crop',
      alt: 'Photo 8',
      type: 'remote'
    },
    {
      id: 'default-9',
      src: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=400&h=300&fit=crop',
      alt: 'Photo 9',
      type: 'remote'
    },
    {
      id: 'default-10',
      src: 'https://images.unsplash.com/photo-1526498460520-4c246339dccb?w=400&h=300&fit=crop',
      alt: 'Photo 10',
      type: 'remote'
    },
    {
      id: 'default-11',
      src: 'https://images.unsplash.com/photo-1494253109108-2e30c049369b?w=400&h=300&fit=crop',
      alt: 'Photo 11',
      type: 'remote'
    },
    {
      id: 'default-12',
      src: 'https://images.unsplash.com/photo-1500534623283-312aade485b7?w=400&h=300&fit=crop',
      alt: 'Photo 12',
      type: 'remote'
    },
    {
      id: 'default-13',
      src: 'https://images.unsplash.com/photo-1491895200222-0fc4a4c35e18?w=400&h=300&fit=crop',
      alt: 'Photo 13',
      type: 'remote'  
    },
    {
      id: 'default-14',
      src: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=400&h=300&fit=crop',
      alt: 'Photo 14',
      type: 'remote'
    },
    {
      id: 'default-15',
      src: 'https://images.unsplash.com/photo-1501785888041-af3ef285b470',
      alt: 'Photo 15',
      type: 'remote'  
    },
    {
      id: 'default-16',
      src: 'https://images.unsplash.com/photo-1519681393784-d120267933ba',
      alt: 'Photo 16',
      type: 'remote'
    }
  ]
};

// Class to handle photo storage
class PhotoStorage {
  constructor() {
    this.photos = this.loadPhotos();
  }
  // Load photos from localStorage
  loadPhotos() {
    const stored = localStorage.getItem(CONFIG.STORAGE_KEY);
    if (stored) {
      try {
        return JSON.parse(stored); // Convert JSON string to array
      } catch (e) {
        console.error('Failed to load photos from storage:', e);
        return CONFIG.DEFAULT_PHOTOS;
      }
    }
    return CONFIG.DEFAULT_PHOTOS;
  }

  // Save photos to localStorage
  savePhotos() {
    localStorage.setItem(CONFIG.STORAGE_KEY, JSON.stringify(this.photos));
  }
  // Get all photos
  getPhotos() {
    return this.photos;
  }
  // Get limited number of photos
  getPhotosPaginated(limit) {
    return this.photos.slice(0, limit);
  }
  // Search photos by alt text
  searchPhotos(query) {
    const lowerQuery = query.toLowerCase();
    return this.photos.filter(p => p.alt.toLowerCase().includes(lowerQuery));
  }
}

const photoStorage = new PhotoStorage();

// Load main gallery (first page)
function loadGallery() {
  const container = document.getElementById('galleryContainer');
  const moreWrapper = document.getElementById('moreWrapper');
  const allPhotos = photoStorage.getPhotos();

  const displayPhotos = allPhotos.slice(0, CONFIG.PHOTOS_PER_PAGE);
  // Render images with click handlers for lightbox
  container.innerHTML = displayPhotos.map((photo, index) => `
    <img src="${photo.src}" alt="${photo.alt}" style="cursor: pointer;" onclick="setupLightboxAndOpen(${index}, true)" />
  `).join('');

  // Show "More" button if extra photos exist
  if (allPhotos.length > CONFIG.PHOTOS_PER_PAGE) {
    moreWrapper.style.display = 'block';
  } else {
    moreWrapper.style.display = 'none';
  }
}
// Open full gallery page
function openFullGallery() {
  const fullGalleryPage = document.getElementById('fullGalleryPage');
  fullGalleryPage.style.display = 'block';
  loadFullGallery();
}
// Load full gallery (with optional search)
function loadFullGallery(searchQuery = '') {
  const container = document.getElementById('galleryFull');
  let photos = photoStorage.getPhotos();
  // Apply search filter
  if (searchQuery) {
    photos = photoStorage.searchPhotos(searchQuery);
  }
  // If no results found
  if (photos.length === 0) {
    container.innerHTML = '<div class="no-photos-message">No photos found.</div>';
    return;
  }
  // Render all images
  container.innerHTML = photos.map((photo, index) => `
    <img src="${photo.src}" alt="${photo.alt}" style="cursor: pointer;" onclick="setupLightboxAndOpen(${index}, false)" />
  `).join('');
}
// Go back to main page
function backToMain() {
  const fullGalleryPage = document.getElementById('fullGalleryPage');
  fullGalleryPage.style.display = 'none';
}
// Prepare photos and open lightbox
function setupLightboxAndOpen(index, isMainGallery) {
  const allPhotos = photoStorage.getPhotos();

  if (isMainGallery) {
    // Only first page photos
    currentLightboxPhotos = allPhotos.slice(0, CONFIG.PHOTOS_PER_PAGE);
  } else {
    // Use search results if available
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value) {
      currentLightboxPhotos = photoStorage.searchPhotos(searchInput.value);
    } else {
      currentLightboxPhotos = allPhotos;
    }
  }

  openLightbox(index);
}
// Setup live search
function setupSearchFunctionality() {
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      loadFullGallery(e.target.value);
    });
  }
}
// Scroll to gallery section
function setupExploreButton() {
  const exploreBtn = document.getElementById('exploreBtn');
  if (exploreBtn) {
    exploreBtn.addEventListener('click', () => {
      const gallerySection = document.getElementById('gallery');
      if (gallerySection) {
        gallerySection.scrollIntoView({ 
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  }
}

// Lightbox state
let currentLightboxPhotos = [];
let currentLightboxIndex = 0;
// Open lightbox
function openLightbox(photoIndex) {
  currentLightboxIndex = photoIndex;
  const lightbox = document.getElementById('lightbox');
  const lightboxImage = document.getElementById('lightboxImage');
  const lightboxCounter = document.getElementById('lightboxCounter');

  lightboxImage.src = currentLightboxPhotos[currentLightboxIndex].src;
  lightboxCounter.textContent = `${currentLightboxIndex + 1} / ${currentLightboxPhotos.length}`;

  lightbox.style.display = 'flex';
  document.body.style.overflow = 'hidden'; // disable scroll
}
// Close lightbox
function closeLightbox() {
  const lightbox = document.getElementById('lightbox');
  lightbox.style.display = 'none';
  document.body.style.overflow = 'auto';
}

// Navigate next/previous image
function navigateLightbox(direction) {
  currentLightboxIndex += direction;

  if (currentLightboxIndex >= currentLightboxPhotos.length) {
    currentLightboxIndex = 0;
  } else if (currentLightboxIndex < 0) {
    currentLightboxIndex = currentLightboxPhotos.length - 1;
  }

  const lightboxImage = document.getElementById('lightboxImage');
  const lightboxCounter = document.getElementById('lightboxCounter');

  lightboxImage.src = currentLightboxPhotos[currentLightboxIndex].src;
  lightboxCounter.textContent = `${currentLightboxIndex + 1} / ${currentLightboxPhotos.length}`;
}
// Keyboard controls
document.addEventListener('keydown', (e) => {
  const lightbox = document.getElementById('lightbox');
  const adminPanel = document.getElementById('adminPanel');
  const fullGalleryPage = document.getElementById('fullGalleryPage');

  if (lightbox && lightbox.style.display === 'flex') {
    if (e.key === 'ArrowRight') {
      navigateLightbox(1);
    } else if (e.key === 'ArrowLeft') {
      navigateLightbox(-1);
    } else if (e.key === 'Escape') {
      closeLightbox();
    }
  }
  else if (adminPanel && adminPanel.classList.contains('active')) {
    if (e.key === 'Escape') {
      closeAdminPanel();
    }
  }
  else if (fullGalleryPage && fullGalleryPage.style.display === 'block') {
    if (e.key === 'Escape') {
      backToMain();
    }
  }
});

// ============================================
// TEAM MEMBERS LOADING FROM DATABASE - FIXED
// ============================================
function loadTeamMembers() {
    console.log("loadTeamMembers() called");
  const teamContainer = document.getElementById('teamContainer');
  if (!teamContainer) {
    console.warn('Team container not found on page');
    return;
  }
  
  console.log('Loading team members from API...');
  
  fetch(CONFIG.API_BASE + 'crud_team.php')
    .then(response => {
      console.log('Team API response status:', response.status);
      return response.json();
    })
    .then(data => {
      console.log('Team API response data:', data);
      let team = [];
      
      if (data.status === 'success' && data.data) {
        team = data.data;
      } else if (data.data) {
        team = data.data;
      }
      
      // Filter only active team members for website display
      const activeTeam = team.filter(member => member.is_active == 1);
      console.log('Active team members found:', activeTeam.length);
      
      if (activeTeam.length === 0) {
        teamContainer.innerHTML = '<div class="no-data-message"><p>No team members available.</p></div>';
        return;
      }
      
      teamContainer.innerHTML = '';
      activeTeam.forEach(member => {
        const memberDiv = document.createElement('div');
        memberDiv.className = 'member';
        const defaultImage = 'https://i.pravatar.cc/150?img=' + (member.team_id % 10);
        memberDiv.innerHTML = `
          ${member.image_url ? `<img src="${escapeHtml(member.image_url)}" alt="${escapeHtml(member.full_name)}" onerror="this.src='${defaultImage}'">` : `<img src="${defaultImage}" alt="${escapeHtml(member.full_name)}">`}
          <h4>${escapeHtml(member.full_name)}</h4>
          <p>${escapeHtml(member.position)}</p>
          ${member.bio ? `<p class="member-bio">${escapeHtml(member.bio.substring(0, 100))}${member.bio.length > 100 ? '...' : ''}</p>` : ''}
        `;
        teamContainer.appendChild(memberDiv);
      });
    })
    .catch(error => {
      console.error('Error loading team members:', error);
      const teamContainer = document.getElementById('teamContainer');
      if (teamContainer) {
        teamContainer.innerHTML = '<div class="error-message"><p>Error loading team members. Please try again later.</p></div>';
      }
    });
}

// ============================================
// CONTACT FORM SUBMISSION TO DATABASE - FIXED
// ============================================
function setupContactFormSubmission() {
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    // Remove any old event listeners
    const newForm = contactForm.cloneNode(true);
    contactForm.parentNode.replaceChild(newForm, contactForm);
    newForm.addEventListener('submit', submitContactForm);
    console.log('Contact form event listener attached');
  }
}

function submitContactForm(event) {
  event.preventDefault();
  
  console.log('Submitting contact form...');
  
  const nameInput = document.getElementById('contactName');
  const emailInput = document.getElementById('contactEmail');
  const messageInput = document.getElementById('contactMessageText');
  
  // Validate inputs
  if (!nameInput.value.trim() || !emailInput.value.trim() || !messageInput.value.trim()) {
    showContactMessage('Please fill in all fields', 'error');
    return;
  }
  
  // Validate email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(emailInput.value.trim())) {
    showContactMessage('Please enter a valid email address', 'error');
    return;
  }
  
  const data = {
    full_name: nameInput.value.trim(),
    email: emailInput.value.trim(),
    message: messageInput.value.trim()
  };
  
  console.log('Sending contact data:', data);
  
  // Send to API
  fetch(CONFIG.API_BASE + 'crud_contact.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data)
  })
  .then(response => response.json())
  .then(result => {

    console.log(result);

    if (result.status === 'success') {

        showContactMessage(
            'Thank you! Your message has been sent successfully.',
            'success'
        );

        document.getElementById('contactName').value = '';
        document.getElementById('contactEmail').value = '';
        document.getElementById('contactMessageText').value = '';

    } else {

        showContactMessage(
            result.message || 'Submission failed',
            'error'
        );

    }

})
}

function showContactMessage(message, type) {
  const messageDiv = document.getElementById('contactStatusMessage');
  if (!messageDiv) return;
  
  messageDiv.textContent = message;
  messageDiv.style.display = 'block';
  messageDiv.style.padding = '15px';
  messageDiv.style.borderRadius = '5px';
  messageDiv.style.marginTop = '15px';
  
  if (type === 'success') {
    messageDiv.style.background = '#c6f6d5';
    messageDiv.style.color = '#22543d';
    messageDiv.style.border = '1px solid #9ae6b4';
  } else {
    messageDiv.style.background = '#fed7d7';
    messageDiv.style.color = '#742a2a';
    messageDiv.style.border = '1px solid #fc8181';
  }
  
  // Auto-hide after 5 seconds
  setTimeout(() => {
    messageDiv.style.display = 'none';
  }, 5000);
}

function escapeHtml(text) {
  if (!text) return '';
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
  loadGallery();
  setupExploreButton();
  setupContactFormSubmission();
  setupSearchFunctionality();
  loadTeamMembers(); // Load team members from database
});