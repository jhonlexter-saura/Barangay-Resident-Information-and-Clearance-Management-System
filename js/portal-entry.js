// ============================================
// portal-entry.js
// Scripts for the LGU Portal entry/role selection page
// ============================================

// No interactive scripts needed for the entry page.
// This file is reserved for future enhancements such as:
// - Keyboard navigation between the two portal cards
// - Analytics tracking on card clicks
// - Session-based auto-redirect if user role is remembered

document.addEventListener('DOMContentLoaded', () => {
  
  // Highlight card on keyboard focus for accessibility
  const cards = document.querySelectorAll('.portal-card');
  
  cards.forEach(card => {
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
      }
    });
  });
  
});