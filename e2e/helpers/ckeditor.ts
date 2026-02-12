import { Page } from '@playwright/test';

/**
 * Fill a CKEditor 5 instance by simulating real user input.
 * This avoids the innerHTML hack which bypasses CKEditor's internal model
 * and leaves the underlying textarea out of sync.
 */
export async function fillCKEditor(
  page: Page,
  content: string,
  selector = '.ck-editor__editable',
) {
  const editable = page.locator(selector);
  await editable.waitFor({ state: 'visible', timeout: 10_000 });
  await editable.click();
  await page.keyboard.press('ControlOrMeta+a');
  await page.keyboard.type(content, { delay: 0 });
}

export async function getCKEditorContent(
  page: Page,
  selector = '.ck-editor__editable',
): Promise<string> {
  return page.locator(selector).innerText();
}
