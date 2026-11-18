# Search keyboard shortcut

Theme 0.3.12 opens the existing search dialog with **Ctrl+K** on Windows/Linux
and **Command+K** on Apple keyboards. Modern platform metadata is used when
available, with the legacy platform fallback needed by Safari and iPad keyboards.
The search trigger exposes the matching `aria-keyshortcuts` and tooltip.

The shortcut focuses the search field. Repeating it preserves the query and
refocuses the open dialog. Escape restores the original visible focus, with the
header search trigger as fallback when the previous element is now hidden.
Opening search replaces mobile navigation and retains the existing focus trap,
scroll lock and search submission. Queued reopening waits for the close animation;
a stale search-close callback does not steal focus from a replacement menu.

Other inputs/editors retain their shortcuts. Shift/Alt combinations, composition
and previously handled events are left alone. Apple Control+K is preserved.
Without the dialog or its input the browser shortcut is not intercepted. The
existing search link continues to work without JavaScript.

Implementation: `assets/js/theme.js`. No new library, page layout or search
backend is introduced. No article, URL, form handler or private setting is changed.

## Verification

Sixteen dedicated desktop/mobile cases cover Windows, Linux, macOS and iPad
platform reporting; repeated/refocused search, typed-form preservation, editing
guards, mobile menu handoff, dark mode, normal-motion close/reopen, an actual
published-post search, and the WebKit engine's Apple platform fallback.
These simulate platform metadata in Chromium/WebKit; they are not physical-device
tests. JavaScript lint, PHP syntax and clean 239-file packaging also pass.

An initial broad preview run passed 128 cases. After the final replacement-menu
focus guard, the repeat passed 124 with four form/search failures. Resetting only
the isolated preview rate counters and rerunning those four unchanged tests
passed all four. Production verification and exact release/backup records are
recorded in PROJECT-MEMORY.md. Prior local deletion rejections must not be bypassed.
