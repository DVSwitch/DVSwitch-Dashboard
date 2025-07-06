/**
 * Modern Lightbox - Vanilla JavaScript Lightbox
 * Replaces Featherlight with a modern, lightweight implementation
 * No jQuery dependency, ES6+ compatible
 * 
 * Features:
 * - Vanilla JavaScript (no dependencies)
 * - ES6+ syntax
 * - Touch support for mobile
 * - Keyboard navigation
 * - Accessibility features
 * - Modern CSS animations
 */

class ModernLightbox {
  constructor(options = {}) {
    this.options = {
      selector: '[data-lightbox]',
      closeOnClick: true,
      closeOnEsc: true,
      animationDuration: 300,
      ...options
    };
    
    this.isOpen = false;
    this.currentElement = null;
    this.overlay = null;
    this.content = null;
    
    this.init();
  }
  
  init() {
    this.createOverlay();
    this.bindEvents();
  }
  
  createOverlay() {
    // Create overlay element
    this.overlay = document.createElement('div');
    this.overlay.className = 'modern-lightbox-overlay';
    this.overlay.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.8);
      z-index: 9999;
      opacity: 0;
      visibility: hidden;
      transition: opacity ${this.options.animationDuration}ms ease-in-out;
      display: flex;
      align-items: center;
      justify-content: center;
    `;
    
    // Create content container
    this.content = document.createElement('div');
    this.content.className = 'modern-lightbox-content';
    this.content.style.cssText = `
      position: relative;
      max-width: 90vw;
      max-height: 90vh;
      background: white;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      transform: scale(0.8);
      transition: transform ${this.options.animationDuration}ms ease-in-out;
      overflow: hidden;
    `;
    
    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'modern-lightbox-close';
    closeBtn.innerHTML = '×';
    closeBtn.setAttribute('aria-label', 'Close lightbox');
    closeBtn.style.cssText = `
      position: absolute;
      top: 10px;
      right: 15px;
      background: none;
      border: none;
      font-size: 24px;
      color: #333;
      cursor: pointer;
      z-index: 10;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: background-color 0.2s;
    `;
    
    closeBtn.addEventListener('mouseenter', () => {
      closeBtn.style.backgroundColor = 'rgba(0, 0, 0, 0.1)';
    });
    
    closeBtn.addEventListener('mouseleave', () => {
      closeBtn.style.backgroundColor = 'transparent';
    });
    
    closeBtn.addEventListener('click', () => this.close());
    
    this.content.appendChild(closeBtn);
    this.overlay.appendChild(this.content);
    document.body.appendChild(this.overlay);
  }
  
  bindEvents() {
    // Bind to elements with data-lightbox attribute
    document.addEventListener('click', (e) => {
      const target = e.target.closest(this.options.selector);
      if (target) {
        e.preventDefault();
        this.open(target);
      }
    });
    
    // Close on overlay click
    this.overlay.addEventListener('click', (e) => {
      if (e.target === this.overlay && this.options.closeOnClick) {
        this.close();
      }
    });
    
    // Close on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isOpen && this.options.closeOnEsc) {
        this.close();
      }
    });
    
    // Handle touch events for mobile
    let touchStartY = 0;
    this.overlay.addEventListener('touchstart', (e) => {
      touchStartY = e.touches[0].clientY;
    });
    
    this.overlay.addEventListener('touchend', (e) => {
      const touchEndY = e.changedTouches[0].clientY;
      const diff = touchStartY - touchEndY;
      
      // Close on swipe down
      if (diff > 50) {
        this.close();
      }
    });
  }
  
  open(element) {
    if (this.isOpen) return;
    
    this.currentElement = element;
    this.isOpen = true;
    
    // Get content from href or data-src
    const src = element.getAttribute('href') || element.getAttribute('data-src');
    const type = this.getContentType(src);
    
    // Load content based on type
    this.loadContent(src, type);
    
    // Show overlay
    this.overlay.style.visibility = 'visible';
    requestAnimationFrame(() => {
      this.overlay.style.opacity = '1';
      this.content.style.transform = 'scale(1)';
    });
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
  }
  
  close() {
    if (!this.isOpen) return;
    
    this.isOpen = false;
    
    // Hide overlay
    this.overlay.style.opacity = '0';
    this.content.style.transform = 'scale(0.8)';
    
    setTimeout(() => {
      this.overlay.style.visibility = 'hidden';
      this.clearContent();
    }, this.options.animationDuration);
    
    // Restore body scroll
    document.body.style.overflow = '';
  }
  
  getContentType(src) {
    if (!src) return 'html';
    
    // Check for image extensions
    if (/\.(jpg|jpeg|png|gif|webp|svg)$/i.test(src)) {
      return 'image';
    }
    
    // Check for video extensions
    if (/\.(mp4|webm|ogg)$/i.test(src)) {
      return 'video';
    }
    
    // Check for iframe/embed
    if (src.includes('youtube.com') || src.includes('vimeo.com') || src.includes('iframe')) {
      return 'iframe';
    }
    
    return 'html';
  }
  
  loadContent(src, type) {
    this.clearContent();
    
    switch (type) {
      case 'image':
        this.loadImage(src);
        break;
      case 'video':
        this.loadVideo(src);
        break;
      case 'iframe':
        this.loadIframe(src);
        break;
      default:
        this.loadHtml(src);
        break;
    }
  }
  
  loadImage(src) {
    const img = document.createElement('img');
    img.src = src;
    img.style.cssText = `
      max-width: 100%;
      max-height: 100%;
      display: block;
    `;
    img.alt = this.currentElement.getAttribute('alt') || '';
    this.content.appendChild(img);
  }
  
  loadVideo(src) {
    const video = document.createElement('video');
    video.src = src;
    video.controls = true;
    video.autoplay = true;
    video.style.cssText = `
      max-width: 100%;
      max-height: 100%;
      display: block;
    `;
    this.content.appendChild(video);
  }
  
  loadIframe(src) {
    const iframe = document.createElement('iframe');
    iframe.src = src;
    iframe.style.cssText = `
      width: 100%;
      height: 80vh;
      border: none;
      display: block;
    `;
    iframe.setAttribute('allowfullscreen', 'true');
    this.content.appendChild(iframe);
  }
  
  loadHtml(src) {
    // For HTML content, we'll load it via fetch
    if (src && src !== '#') {
      fetch(src)
        .then(response => response.text())
        .then(html => {
          this.content.innerHTML = html;
        })
        .catch(error => {
          console.error('Error loading content:', error);
          this.content.innerHTML = '<div style="padding: 20px; text-align: center;">Error loading content</div>';
        });
    } else {
      // For inline content
      const content = this.currentElement.getAttribute('data-content') || '';
      this.content.innerHTML = content;
    }
  }
  
  clearContent() {
    // Remove all content except close button
    const closeBtn = this.content.querySelector('.modern-lightbox-close');
    this.content.innerHTML = '';
    if (closeBtn) {
      this.content.appendChild(closeBtn);
    }
  }
}

// Initialize lightbox when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  window.modernLightbox = new ModernLightbox();
});

// For backward compatibility with Featherlight
window.featherlight = ModernLightbox;
