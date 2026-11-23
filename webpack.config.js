/**
 * SPDX-FileCopyrightText: 2026 Adam Monsen <haircut@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry = {
	main: path.join(__dirname, 'src', 'main.js'),
}

// webpack's default budget (244 KiB) assumes a typical multi-page site
// doing route-based code splitting. We're a single-entry, single-page app:
// the whole bundle loads once per session and is cached long-term (the
// output filename is content-hashed and served immutable), so there's no
// later page load to defer cost to. Per-module stats (`npx webpack --json`)
// show the overage isn't our own code: NcContent/NcAppContent alone pull in
// vue-router, @vueuse/core, dompurify, and splitpanes transitively, just to
// get Nextcloud's standard app chrome. Raised the budget to match reality
// instead of chasing an unactionable warning; this will still catch a
// genuine regression (e.g. a stray full-locale date library).
webpackConfig.performance = {
	maxAssetSize: 350 * 1024,
	maxEntrypointSize: 350 * 1024,
}

module.exports = webpackConfig
