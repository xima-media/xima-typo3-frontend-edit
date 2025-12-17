/**
 * Scroll to content element in Page Layout module.
 *
 * This module reads the scrollToElement parameter from the URL
 * and scrolls to the corresponding content element.
 *
 * WORKAROUND: This is necessary because the TYPO3 backend uses an iframe
 * architecture. URL fragments (e.g. #element-tt_content-123) are applied
 * to the outer document, not the iframe content. When navigating from the
 * frontend to the Page Layout module, the fragment is lost because the
 * iframe is loaded separately without preserving the fragment.
 *
 * Native TYPO3 scroll-to-element works after editing because the redirect
 * happens WITHIN the iframe context, preserving the fragment.
 *
 * @see https://forge.typo3.org/issues/89678 - Related TYPO3 Core issue
 */
class ScrollToElement {
  constructor() {
    const urlParams = new URLSearchParams(window.location.search);
    const elementUid = urlParams.get('scrollToElement');

    if (!elementUid) {
      return;
    }

    const elementId = `element-tt_content-${elementUid}`;
    this.scrollToElement(elementId);
  }

  scrollToElement(elementId) {
    // Wait for DOM to be fully loaded
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.performScroll(elementId));
    } else {
      // Small delay to ensure all dynamic content is rendered
      setTimeout(() => this.performScroll(elementId), 100);
    }
  }

  performScroll(elementId) {
    const element = document.getElementById(elementId);

    if (!element) {
      return;
    }

    element.scrollIntoView({
      behavior: 'smooth',
      block: 'center'
    });
  }
}

export default new ScrollToElement();
