import type { Page } from '@playwright/test';

export class BackendPage {
  constructor(private readonly page: Page) {}

  async login(username: string, password: string): Promise<void> {
    await this.page.goto('/typo3/');
    await this.page.locator('#t3-username').fill(username);
    await this.page.locator('#t3-password').fill(password);
    await this.page.locator('#t3-login-submit').click();
    // The login form is a real page (no SPA routing), so a successful login
    // fully replaces the DOM. Waiting for the username field to detach avoids
    // depending on the exact markup of whichever module loads after login.
    await this.page.locator('#t3-username').waitFor({ state: 'detached', timeout: 15_000 });
  }
}
