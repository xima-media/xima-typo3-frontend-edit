import type { Page, Locator } from '@playwright/test';

export class HoverMenu {
  constructor(private readonly page: Page) {}

  contentElement(uid: number): Locator {
    return this.page.locator(`#c${uid}`);
  }

  toolbar(uid: number): Locator {
    return this.page.locator(`.frontend-edit__toolbar[data-cid="${uid}"]`);
  }

  editButton(uid: number): Locator {
    return this.toolbar(uid).locator('.frontend-edit__btn--edit');
  }

  kebabButton(uid: number): Locator {
    return this.toolbar(uid).locator('.frontend-edit__btn--kebab');
  }

  dropdown(uid: number): Locator {
    return this.page.locator(`.frontend-edit__dropdown[data-cid="${uid}"]`);
  }

  async hover(uid: number): Promise<void> {
    await this.contentElement(uid).hover();
  }

  async openDropdown(uid: number): Promise<void> {
    await this.hover(uid);
    await this.kebabButton(uid).click();
  }
}
