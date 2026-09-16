<?php

declare(strict_types=1);

class RedditSubExtension extends Minz_Extension {
	private const DEFAULT_PREFIX_FORMAT = '/r/%s/ - ';
	private const DEFAULT_USE_STYLING = true;
	private const DEFAULT_ENABLE_CUSTOM_COLOR = false;
	private const DEFAULT_CUSTOM_COLOR = '#ff4500';

	public function init(): void {
		$this->registerHook('entry_before_display', [$this, 'renderEntry']);

		if ($this->getUseStyling()) {
			Minz_View::appendStyle($this->getFileUrl('style.css', 'css'));
		}

		if ($this->getEnableCustomColor()) {
			$customColor = $this->getCustomColor();
			if (!$this->hasFile('custom.css')) {
				$this->saveFile('custom.css', ".reddit_sub_prefix { color: {$customColor} !important; opacity: 1 !important; }\n");
			}
			Minz_View::appendStyle($this->getFileUrl('custom.css', isStatic: false));
		}
	}

	public function handleConfigureAction(): void {
		if (Minz_Request::isPost()) {
			$prefixFormat = Minz_Request::paramString('prefix_format', false);
			if ($prefixFormat === '' || strpos($prefixFormat, '%s') === false) {
				$prefixFormat = self::DEFAULT_PREFIX_FORMAT;
			}

			$enableCustomColor = Minz_Request::paramBoolean('enable_custom_color');
			$customColor = trim(Minz_Request::paramString('custom_color', false));
			if ($customColor === '' || !preg_match('~^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$~', $customColor)) {
				$customColor = self::DEFAULT_CUSTOM_COLOR;
			}

			$configuration = [
				'prefix_format' => $prefixFormat,
				'use_styling' => Minz_Request::paramBoolean('use_styling'),
				'enable_custom_color' => $enableCustomColor,
				'custom_color' => $customColor,
			];
			$this->setUserConfiguration($configuration);

			if ($enableCustomColor) {
				$this->saveFile('custom.css', ".reddit_sub_prefix { color: {$customColor} !important; opacity: 1 !important; }\n");
			} else {
				$this->removeFile('custom.css');
			}
		}
	}

	public function getPrefixFormat(): string {
		return (string) $this->getUserConfigurationValue('prefix_format', self::DEFAULT_PREFIX_FORMAT);
	}

	public function getUseStyling(): bool {
		return (bool) $this->getUserConfigurationValue('use_styling', self::DEFAULT_USE_STYLING);
	}

	public function getEnableCustomColor(): bool {
		return (bool) $this->getUserConfigurationValue('enable_custom_color', self::DEFAULT_ENABLE_CUSTOM_COLOR);
	}

	public function getCustomColor(): string {
		return (string) $this->getUserConfigurationValue('custom_color', self::DEFAULT_CUSTOM_COLOR);
	}

	protected function extractSubreddit(FreshRSS_Entry $entry): ?string {
		// 1. Check entry permalink (e.g. https://www.reddit.com/r/jellyfin/comments/...)
		$link = $entry->link(true);
		if ($link !== '' && preg_match('~https?://(?:[a-zA-Z0-9_.-]+\.)?reddit\.com/r/([^/?#\s]+)~i', $link, $matches)) {
			return trim($matches[1]);
		}

		// 2. Check entry tags (FreshRSS maps <category> to tags, formatted like "#r/sub" or "r/sub")
		$tags = $entry->tags();
		if (is_array($tags)) {
			foreach ($tags as $tag) {
				if (preg_match('~^#?r/([a-zA-Z0-9_.-]+)$~i', trim((string) $tag), $matches)) {
					return trim($matches[1]);
				}
			}
		} elseif (is_string($tags) && $tags !== '') {
			if (preg_match('~(?:^|\s)#?r/([a-zA-Z0-9_.-]+)~i', $tags, $matches)) {
				return trim($matches[1]);
			}
		}

		// 3. Check HTML content for Reddit submission link (<a href="https://www.reddit.com/r/sub/"> r/sub </a>)
		$content = $entry->content(false);
		if ($content !== '' && preg_match('~reddit\.com/r/([a-zA-Z0-9_.-]+)/~i', $content, $matches)) {
			return trim($matches[1]);
		}

		// 4. Check feed URL if it is a single-subreddit feed (e.g. /r/jellyfin/.rss)
		try {
			$feed = $entry->feed();
			if ($feed !== null) {
				$feedUrl = $feed->url();
				if ($feedUrl !== '' && preg_match('~https?://(?:[a-zA-Z0-9_.-]+\.)?reddit\.com/r/([^/?#\s]+)~i', $feedUrl, $matches)) {
					$sub = trim($matches[1]);
					if (!in_array(strtolower($sub), ['all', 'popular', 'mod'], true)) {
						return $sub;
					}
				}
			}
		} catch (Throwable $e) {
			// Ignore if feed DAO / context is not available
		}

		return null;
	}

	public function renderEntry(?FreshRSS_Entry $entry): ?FreshRSS_Entry {
		if ($entry === null) {
			return null;
		}

		$subreddit = $this->extractSubreddit($entry);
		if ($subreddit === null || $subreddit === '') {
			// Non-Reddit entry: leave untouched!
			return $entry;
		}

		$format = $this->getPrefixFormat();
		$formattedPrefix = sprintf($format, $subreddit);

		$originalTitle = $entry->title();

		// Prevent double-prefixing if already applied
		if (strpos($originalTitle, $formattedPrefix) !== false || strpos($originalTitle, 'reddit_sub_prefix') !== false) {
			return $entry;
		}

		// Ensure space between prefix and title
		$spacer = (substr($formattedPrefix, -1) === ' ') ? '' : ' ';

		if ($this->getUseStyling() || $this->getEnableCustomColor()) {
			$prefixHtml = '<span class="reddit_sub_prefix">' . htmlspecialchars($formattedPrefix, ENT_QUOTES, 'UTF-8') . '</span>' . $spacer;
		} else {
			$prefixHtml = htmlspecialchars($formattedPrefix, ENT_QUOTES, 'UTF-8') . $spacer;
		}

		$entry->_title($prefixHtml . $originalTitle);
		return $entry;
	}
}
