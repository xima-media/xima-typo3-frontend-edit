import FormEngine from '@typo3/backend/form-engine.js'

class SaveClose {
  constructor() {
    const saveCloseButton = document.querySelector('[data-js="save-close"]');
    if (!saveCloseButton) {
      return;
    }
    saveCloseButton.addEventListener('click', (e) => {
      e.preventDefault()
      FormEngine.saveAndCloseDocument()
    });
  }
}

export default new SaveClose()
