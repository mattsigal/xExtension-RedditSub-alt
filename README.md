# xExtension-RedditSub

A modern extension for [FreshRSS](https://github.com/FreshRSS/FreshRSS) that prefixes Reddit entries with their subreddit name (e.g. `/r/jellyfin/ - `).

Forked and modernized from `jesuslop/xExtension-RedditSub-alt` and `balthisar/xExtension-RedditSub`.

## Features

- **Strict Reddit Scoping**: Only modifies Reddit entries (via permalinks, tags, multireddit categories, or content). Non-Reddit feeds are left completely untouched.
- **No Text Truncation**: Unlike older forks that clipped subreddit names at a fixed 100px width, names are fully displayed with proper inline baseline alignment.
- **Theme-Adaptive & Custom Styling**: Uses opacity and weight adjustments to look crisp across dark, light, and custom themes, with an optional custom color picker (e.g. Reddit Orange `#ff4500`).
- **Configurable Prefix**: Easily customize the format string via the FreshRSS Extensions configuration UI (e.g., `/r/%s/ - `, `[r/%s] `, or `r/%s: `).
- **PHP 8.4 & FreshRSS 1.30+ Compatible**: Type-safe implementation adhering to modern FreshRSS extension specifications.

## Configuration

Navigate to **FreshRSS Settings -> Extensions -> RedditSub -> Configure**:

- **Prefix Format**: Defines the prefix template. Use `%s` where the subreddit name should be inserted (Default: `/r/%s/ - `).
- **Style Subreddit Prefix**: Toggle subtle emphasis and theme-adaptive styling.
- **Subreddit Text Color**: Enable a custom accent color for the subreddit text using an interactive color picker or hex code (e.g. `#ff4500`).

## Installation

Clone or copy this directory to your FreshRSS extensions folder:

```bash
cd /path/to/FreshRSS/extensions/
git clone https://github.com/mattsigal/xExtension-RedditSub-alt.git xExtension-RedditSub
```

Then enable **RedditSub** under **Settings -> Extensions** in the FreshRSS web interface.

## License

MIT License. See [LICENSE](LICENSE) for details.
