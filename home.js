// Configuration settings
const CONFIG = {
  MAX_PHOTO_SIZE: 5 * 1024 * 1024, // Max upload size (5MB)
  PHOTOS_PER_PAGE: 12, // Photos shown on main gallery
  STORAGE_KEY: 'photoclub_photos',// Key for localStorage
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
// Handle contact form submission
function setupContactForm() {
  const form = document.getElementById('contactForm');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const inputs = form.querySelectorAll('input, textarea');
      const name = inputs[0].value;
      const email = inputs[1].value;
      const message = inputs[2].value;

      try {
        // Send data to fake API
        const res = await fetch('https://jsonplaceholder.typicode.com/posts', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, message })
        });

        if (res.ok) {
          alert('Message sent successfully!');
          form.reset();
        } else {
          alert('Error sending message.');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('Error sending message.');
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

  if (lightbox.style.display === 'flex') {
    if (e.key === 'ArrowRight') {
      navigateLightbox(1);
    } else if (e.key === 'ArrowLeft') {
      navigateLightbox(-1);
    } else if (e.key === 'Escape') {
      closeLightbox();
    }
  } else if (adminPanel.classList.contains('active')) {
    if (e.key === 'Escape') {
      closeAdminPanel();
    }
  } else if (fullGalleryPage.style.display === 'block') {
    if (e.key === 'Escape') {
      backToMain();
    }
  }
});
// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
  loadGallery();
  setupExploreButton();
  setupContactForm();
  setupSearchFunctionality();
});