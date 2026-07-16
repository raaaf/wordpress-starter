## [2.21.1](https://github.com/raaaf/wordpress-starter/compare/v2.21.0...v2.21.1) (2026-07-16)

### Bug Fixes

- **ci:** point repository and theme_uri at renamed wordpress-starter repo ([2ac25fc](https://github.com/raaaf/wordpress-starter/commit/2ac25fc2d5a39bd1539c32df3900e168d110b1ff))
- **kses:** keep video/audio shortcode source tags in sanitized WYSIWYG output ([94b08dc](https://github.com/raaaf/wordpress-starter/commit/94b08dcbd1142795164c71c6d1e540a1df2158f5))

# 1.0.0 (2026-06-17)

### Bug Fixes

- **a11y:** add aria-labelledby to accordion panels in image layouts ([12f3e7d](https://github.com/raaaf/starter/commit/12f3e7d24c2010bebc693978de3144f152083a04))
- **a11y:** remove invalid ARIA roles from navigation menu ([b9676e0](https://github.com/raaaf/starter/commit/b9676e09f58b325bb48c36cec386aed7a2b8e173))
- **a11y:** resolve WCAG issues across components and templates ([dd34b39](https://github.com/raaaf/starter/commit/dd34b3944596173753176076c7696e91d6bc7d51))
- **acf:** add content guards and fix DocBlocks for image layouts ([7775601](https://github.com/raaaf/starter/commit/7775601c73bae938082555e72cb3bf8d3e74762c))
- **acf:** apply [br] replacement in templates instead of ACF filter ([e7fbe66](https://github.com/raaaf/starter/commit/e7fbe66bc7d48ce3e92edd56e32a69d340c8e20f))
- **acf:** fix field width grouping in image column layouts ([a11e98e](https://github.com/raaaf/starter/commit/a11e98e6b71c5463493bf3592e79e92850ea593a))
- **acf:** reorder section header fields and align button group choices ([ff9a66c](https://github.com/raaaf/starter/commit/ff9a66ca40e243325b31e6b915fc71e171175b4e))
- add chevron icon and appearance-none to select filters in member area ([9f1d59e](https://github.com/raaaf/starter/commit/9f1d59e13b33468f985886a676f8b96427451d88))
- add margin between label and image in column layouts ([3928adc](https://github.com/raaaf/starter/commit/3928adc29f3637763ceabdfc76978de9b5a79f10))
- add missing PHPStan return type annotations for image layout fields ([69ce835](https://github.com/raaaf/starter/commit/69ce835d80d14ac8c6411099b882a7a572eb8e60))
- add space between number and suffix in stats block ([e1ef9bb](https://github.com/raaaf/starter/commit/e1ef9bbb4391fb9686edb1be196ddea745f6f36c))
- address audit findings for perf refactor ([59194ec](https://github.com/raaaf/starter/commit/59194ece673bfcaf45f970c1d47d4d346c4c9e0b))
- **admin:** use flex layout for demo post action buttons ([98bbc3d](https://github.com/raaaf/starter/commit/98bbc3dd3bae5d3773744c5ad1a26ad56aa53087))
- alert bar design tweaks and WYSIWYG field ([1645e6c](https://github.com/raaaf/starter/commit/1645e6ca6b373b9dc2a915220bd7d7c4096a75b2))
- align icons with text, fix logo slider gradient and prose code color ([a15987d](https://github.com/raaaf/starter/commit/a15987d023445f9708ba43ee8d8e00721d2c112b))
- **auth:** always grant member area access to administrators ([e5089a6](https://github.com/raaaf/starter/commit/e5089a61cb7ef17283e886e55db2158c4ae778b0))
- **auth:** pass is_ssl() to wp_signon for correct cookie handling ([4149cb9](https://github.com/raaaf/starter/commit/4149cb9f6961d79e0fecd7394314bfa4106791d4))
- **auth:** stop sanitizing passwords and improve login error messages ([a2b0130](https://github.com/raaaf/starter/commit/a2b01303ce62b11c0ced8beab8f5e8b7153b6ffc))
- **build:** pin archiver to ^7 (v8 is ESM-only, breaks package script) ([1d70aa5](https://github.com/raaaf/starter/commit/1d70aa580f79659687daf77e91fa65415db58cf3))
- **button:** increase specificity of sm/lg size modifiers to override variant defaults ([5e7f5b7](https://github.com/raaaf/starter/commit/5e7f5b7c59c3322ceffa1ac8940ee95ca23f360d))
- **button:** move md sizing after variants so sm/lg overrides work correctly ([871fb7d](https://github.com/raaaf/starter/commit/871fb7d14a6bdd8dd57801b6e5a49ee1d989a13b))
- **button:** use correct CSS custom property names in button classes ([4be9281](https://github.com/raaaf/starter/commit/4be9281fd65da1cc43a5368f53bb4de8d024ce36))
- **cf7:** harden wpcf7_spam callback against older CF7 and non-bool input ([4ef9b9f](https://github.com/raaaf/starter/commit/4ef9b9f5161b722dd7d53fe03cdd1d04e425206c))
- **ci:** ignore ITSEC_Modules in PHPStan — premium plugin not in CI ([e98a4e8](https://github.com/raaaf/starter/commit/e98a4e86df8070eb71cc3d1d5e427a4320ebe790))
- **ci:** include dev dependencies in theme package ZIP ([d86916c](https://github.com/raaaf/starter/commit/d86916c554d4b7bb9228fe63f08cc09daa20e6cf))
- **ci:** mark compiled directory as optional in PHPStan config ([9a22842](https://github.com/raaaf/starter/commit/9a22842385e22c844b5034ce691638e1813a766c))
- **ci:** restore --no-dev for packaging to reduce ZIP size ([18dd93a](https://github.com/raaaf/starter/commit/18dd93a55249c11f7f7b8d1871131ec7f6a1db75))
- correct composer installer-paths and ignore theme-local plugins dir ([a5035b2](https://github.com/raaaf/starter/commit/a5035b21700025f53b3a894c16210ab8ca1963aa))
- correct WordPress auth mode comparison and login session handling ([38175d7](https://github.com/raaaf/starter/commit/38175d7e183533877a748fa67f56da01a766a705))
- CSS layer architecture, namespace consistency, and Alpine.js syntax ([cac0761](https://github.com/raaaf/starter/commit/cac07614dcb76d986dd83b81eb351a5174634e67))
- **css:** add height auto to mejs-container for responsive video sizing ([a3586a9](https://github.com/raaaf/starter/commit/a3586a977323044ea991af435f631c13b2344155))
- **css:** prevent inline video collapse and fix play button centering ([72b9021](https://github.com/raaaf/starter/commit/72b90212f312a6bf61b37261433c8dcc8debba2d))
- **css:** use aspect-ratio on mejs-container for responsive video sizing ([0cfd06b](https://github.com/raaaf/starter/commit/0cfd06b8f76c59cb75ce7ac25bb1ca82e261e688))
- dark mode variant, WCAG contrast overrides, and semantic token usage in templates ([a161d75](https://github.com/raaaf/starter/commit/a161d75bb6d76db479b071e3fa761e841425128d))
- **deploy:** track vendor directory for WordPress SFTP deployments ([22c6cbb](https://github.com/raaaf/starter/commit/22c6cbb87a5b676a731d2a852ab94ec19d823af1))
- **deps:** bump phpseclib to 3.0.52 (CVE fix) and update bundled deps ([8792251](https://github.com/raaaf/starter/commit/87922515def58f1b8ac2b5ce173174424eaf2a67))
- **deps:** sync composer.lock with webp-express package ([b51aae3](https://github.com/raaaf/starter/commit/b51aae3fbf67a1abccf67f69e090dcd25e139a68))
- **deps:** sync package-lock.json with updated dependencies ([048e4d1](https://github.com/raaaf/starter/commit/048e4d11c0032055a6c9933117d023b5d9a320a0))
- **deps:** upgrade illuminate 12->13 and symfony 7->8 (requires PHP 8.3) ([c25cd5b](https://github.com/raaaf/starter/commit/c25cd5b5fda3ad677c106928e9136f8d760cbe07))
- disable rate limiting in WP_DEBUG mode ([044cdce](https://github.com/raaaf/starter/commit/044cdce18f77785eb81ed5f22f962b187cdc647f))
- escape wp_die message with esc_html\_\_ to pass PHPCS ([952f8fe](https://github.com/raaaf/starter/commit/952f8fe959e464e68cf9cb473ce5edc1ade5ab8d))
- full codebase audit — security, a11y, perf, and code quality fixes ([9c39aa5](https://github.com/raaaf/starter/commit/9c39aa560428981bc7491a8633636c378ed82226))
- full codebase audit — security, a11y, performance, DRY and docs ([d75506b](https://github.com/raaaf/starter/commit/d75506b3d0bffe3308b367b5e89bdca766ee9569))
- **header:** use h-12 w-auto for logo instead of w-auto max-h-12 ([05c47fa](https://github.com/raaaf/starter/commit/05c47fac2721d54bbbc03306dafbdf4e7bf42d5b))
- **hero:** use direct opacity instead of Tailwind CSS variable ([9c0809c](https://github.com/raaaf/starter/commit/9c0809c5414017724ca9985b6762e37b85940339))
- **i18n:** add missing translators comment for PHPCS ([fb6a6cb](https://github.com/raaaf/starter/commit/fb6a6cbb669ad1ef15d00f0c058c494817a5b5b9))
- **images:** re-enable medium size and fix wp_content_img_tag filter signature ([ed548de](https://github.com/raaaf/starter/commit/ed548de275dd6f8f66274ecb9e1b893d2f90af81))
- **images:** rebuild srcset for full-size and intermediate editor images ([6bfe538](https://github.com/raaaf/starter/commit/6bfe538a316df13d166529b8875918fa26dd5ecb))
- improve dark mode support and WCAG accessibility ([fe2044c](https://github.com/raaaf/starter/commit/fe2044c3dba41834cc780adef70ebcd43482265f)), closes [#e5541d](https://github.com/raaaf/starter/issues/e5541d)
- isolate option keys for multisite compatibility ([60bdc3f](https://github.com/raaaf/starter/commit/60bdc3f2ac7772c16bfb2470e0298899e789daee))
- lower PHP requirement to 8.2 for broader hosting compatibility ([b0d4427](https://github.com/raaaf/starter/commit/b0d4427a6c9decd11cfafa09b968d696738b14e7))
- make WordPress video embeds responsive with rounded corners ([68c9261](https://github.com/raaaf/starter/commit/68c9261b1dc71e9d998ce4a9ddf0a71d71d0c8b1))
- make WordPress video player responsive inside card containers ([d0e4584](https://github.com/raaaf/starter/commit/d0e4584d35c2e66168de18b87f18ff74333564cc))
- **member-area:** fetch nonces on-demand to bypass page caches ([1b5dc64](https://github.com/raaaf/starter/commit/1b5dc644e8194096d819bb57bfddb1e1356f8991))
- multisite autoloader guard and remove spatie/schema-org dependency ([c0d64c4](https://github.com/raaaf/starter/commit/c0d64c43e6b9ed015b9789cf8d3d70be5b57a2bc))
- **phpcs:** convert indented comments to block comments in ImageServiceProvider ([528b6d8](https://github.com/raaaf/starter/commit/528b6d8880a4e5d95f98f3df5498ba68732bfe45))
- **phpcs:** resolve empty catch and post-increment violations ([ac292a8](https://github.com/raaaf/starter/commit/ac292a817422cf067f3b6b92d1b89fc7e1cb6e16))
- **phpcs:** suppress slow-query and spelling warnings; fix comment format ([8612927](https://github.com/raaaf/starter/commit/8612927ed36a2e5cd17ac9d81a288c757d66b6a2))
- **release:** auto-update style.css version on release ([e92daff](https://github.com/raaaf/starter/commit/e92daff728d6a6226a1741d536681917bd9ad335))
- remove duplicate chevron icons from select; noindex member area pages ([b3dc9ff](https://github.com/raaaf/starter/commit/b3dc9ffa63b29d66ad173ba0d1b633baa15cb147))
- remove extra blank line after namespace declaration ([e01fdbd](https://github.com/raaaf/starter/commit/e01fdbd63b5ecc08241a4e5f7c305a92b273f62a))
- replace remaining wp*starter* keys in Acf/Options ([ced9c8e](https://github.com/raaaf/starter/commit/ced9c8e5d212cbcb5b170fc467d8ddc060e1bcaa))
- replace spatie/schema-org with direct PHP arrays in breadcrumb schema ([a8ddddf](https://github.com/raaaf/starter/commit/a8ddddfdd9429bb321beb7fcaee7750291e11219))
- resolve namespace from composer.json in NamespaceConsistencyTest ([9bd08e2](https://github.com/raaaf/starter/commit/9bd08e27a62336ba0b96cb7fb8e7913d8a89256e))
- resolve PHPCS and PHPUnit CI failures ([be0b146](https://github.com/raaaf/starter/commit/be0b146c2d19a09c887ab563d451f95676c2f2ed))
- resolve phpcs warning in DesignTokenServiceProvider and add [br] hint ([973fe00](https://github.com/raaaf/starter/commit/973fe00f7f3b604f4939a205f5dfe4c63c61294c))
- resolve PHPStan CI failures ([3782440](https://github.com/raaaf/starter/commit/37824409ecfc7dc48f7951d9c96109bdcf02b8d1))
- resolve security and performance findings from audit ([ed800a5](https://github.com/raaaf/starter/commit/ed800a53ab8fc68d8b2a542d64681dcbc54d54c6))
- restore WYSIWYG content fields in image column layouts ([d6d62d0](https://github.com/raaaf/starter/commit/d6d62d0a28fa3f87dde8ac72800e2c56957be153))
- **security:** add media-src CSP directive for external video URLs ([fba13ef](https://github.com/raaaf/starter/commit/fba13ef26d84cf68423ce27f6ec4e4cd9301e932))
- **security:** hardening set from audit follow-up ([9890b91](https://github.com/raaaf/starter/commit/9890b918185caa749ad44627dc02081614bd146d)), closes [#8](https://github.com/raaaf/starter/issues/8)
- **seo:** add is_multisite mock to test bootstrap ([a35f654](https://github.com/raaaf/starter/commit/a35f654154944f428e17f1254cb4fcd16d23b8ad))
- **seo:** correct robots.txt sitemap URL for multisite with domain mapping ([0995dab](https://github.com/raaaf/starter/commit/0995dab059f6ac85a7e3c6a01b66710ae85002d9))
- **setup:** add ThemeUpdateProvider config and fix CLAUDE.md patterns ([f18a8b2](https://github.com/raaaf/starter/commit/f18a8b2653145bd1edaa27e4c0a382dc0d2571ef))
- **setup:** replace use statements in Blade and hardcoded references ([c32fe7e](https://github.com/raaaf/starter/commit/c32fe7ec21554d5fe449d104b61e4f80e128349d))
- skip scroll animations on anchor hash navigation ([727d6ab](https://github.com/raaaf/starter/commit/727d6ab0620e17a3bd6176dda064a67ba1cbe091))
- stretch column cards to full row height in image layouts ([bbbd3ca](https://github.com/raaaf/starter/commit/bbbd3caae91c8bdefe7593573eb68e61d9be12e2))
- **styleguide:** add items-start to button size container to prevent flex stretch ([052f11e](https://github.com/raaaf/starter/commit/052f11e557bedda8bb07c23a24f8345475e765b9))
- **styleguide:** remove .button class to avoid WordPress style conflicts ([6f260b3](https://github.com/raaaf/starter/commit/6f260b345f80a4b79d679ef20f5f54fd556fa964))
- **styleguide:** remove esc_attr from button class strings to preserve CSS tokens ([e97826f](https://github.com/raaaf/starter/commit/e97826f546760905dbf2052d243ff025b7045b43))
- **styleguide:** sync button variants and sizes with Button component ([ca5a428](https://github.com/raaaf/starter/commit/ca5a42837242c4c5911f820f31eceb8bcb7bbb67))
- **styleguide:** use semantic button CSS classes instead of inline Tailwind utilities ([0206eab](https://github.com/raaaf/starter/commit/0206eab9e5194cf603b4b82c867e5983bbc5fe6a))
- suppress password change email when admin edits another user ([d2a5b6f](https://github.com/raaaf/starter/commit/d2a5b6f91c233e3ea52b5f81c0167035b953a53c))
- **svg:** detect real SVG dimensions so logos render at full size ([2dc8eb3](https://github.com/raaaf/starter/commit/2dc8eb3f02844a27be8968d2ac9206e32e7320da))
- sync composer.lock after rebase — add missing integrate-rybbit package ([c60fbe3](https://github.com/raaaf/starter/commit/c60fbe363836f7085d8509a810aa388f9e344731))
- sync package-lock.json with package.json (yaml resolution) ([75057e1](https://github.com/raaaf/starter/commit/75057e1acd9e5866a0e0af71d613289d07019d7d))
- **templates:** hide page header when ACF flexible content exists ([e4111f3](https://github.com/raaaf/starter/commit/e4111f35b885b6732210c9ff6d8ca945bf0e202b))
- **tests:** add phpseclib3 to external namespace whitelist ([61912dc](https://github.com/raaaf/starter/commit/61912dcd3c5425e792a81f59b6b62b790b4b6c40))
- **tests:** update FieldDefinitionsTest to use methods instead of constants ([b7ee4dc](https://github.com/raaaf/starter/commit/b7ee4dc4dd83f7e3fbae5e9a65daba02624c612d))
- **tokens:** ESM guard + trim trailing zeros in clamp output ([fffd80d](https://github.com/raaaf/starter/commit/fffd80d45668459edce7ed64fef2cd4b63be480e))
- **tokens:** replace orphaned CSS custom properties with current token names ([0a2c03c](https://github.com/raaaf/starter/commit/0a2c03c156fced92d627d9493f78215d00b2c9f2))
- update composer.lock to remove spatie/schema-org ([fd8095f](https://github.com/raaaf/starter/commit/fd8095f02ac806febcef6ad41361f03b8dfa2fcc))
- use [br] shortcode instead of HTML for manual line breaks in titles ([db2c843](https://github.com/raaaf/starter/commit/db2c843a8b78516e52b4126dc54cef35595b56d1))
- use basic WYSIWYG toolbar, fix invisible hover on close button ([18b5d4e](https://github.com/raaaf/starter/commit/18b5d4e6eb82fe421734de8b98dec700539771b1))
- use correct wpackagist slug for Rybbit plugin (integrate-rybbit) ([c7423ec](https://github.com/raaaf/starter/commit/c7423ec9e5f0e5b0591e797786f404a5c2dcce13))
- use esc_attr instead of intval for CF7 form ID ([9e5b103](https://github.com/raaaf/starter/commit/9e5b1032b73cbd0a05b50f5cad6bb1b6da035667))
- use placeholder approach for br tag preservation in title fields ([61cd7a3](https://github.com/raaaf/starter/commit/61cd7a36d6955f6762b2c7b8b1d99f10b82fb2bc))
- use var() references for semantic tokens and bind prose colors to design tokens ([139d7cb](https://github.com/raaaf/starter/commit/139d7cbbb618e369ee0c8004cfdabbbf76ab445c))
- **vite:** require .vite-port file before dev server check, set strictPort and CORS origin ([32afedf](https://github.com/raaaf/starter/commit/32afedfc1365cf08e9d2636577872519cb37f229))

### Features

- **acf:** add [br] line break support to all prose text fields ([0818a55](https://github.com/raaaf/starter/commit/0818a5590a7e19d4ce884cc362a804c7b491a829))
- **acf:** add accordion to image column layouts and enable modal search ([2183568](https://github.com/raaaf/starter/commit/218356852bd1156179badbff1861f7c844edad82))
- **acf:** add external video URL source and automatic section anchors ([#1](https://github.com/raaaf/starter/issues/1)) ([9df8543](https://github.com/raaaf/starter/commit/9df8543ab000612731f4623c5a904a586968b78b))
- **acf:** add member-downloads Flexible Content block to member area ([8ec03d5](https://github.com/raaaf/starter/commit/8ec03d57d0e49941834563e31f50091e6a4c62eb))
- **acf:** add one-column, three-columns, and four-columns image layouts ([a5faa9e](https://github.com/raaaf/starter/commit/a5faa9ed18db48edce05b7daa2fd04aaacddfb4c))
- **acf:** add optional label field above images in column layouts ([f586a9f](https://github.com/raaaf/starter/commit/f586a9fa09988452fb21fbbc205ddd384a0e22f5))
- **acf:** add source selection to Team and Testimonials layouts ([aa194c5](https://github.com/raaaf/starter/commit/aa194c540d95eb73b002e2b47e6d937a152aae6a))
- **acf:** extend FieldDefinitions builders and migrate raw field arrays ([769b204](https://github.com/raaaf/starter/commit/769b204a93ba07bb04e0d9fb96926451a1a3be56)), closes [#3](https://github.com/raaaf/starter/issues/3) [#7](https://github.com/raaaf/starter/issues/7)
- **acf:** make multi-column layout fields optional ([f806ce3](https://github.com/raaaf/starter/commit/f806ce3ba0963a7995d9fd87ac60eb588feb8e92))
- **acf:** prefill new pages with Hero layout ([6c957ed](https://github.com/raaaf/starter/commit/6c957ede642b061a0a4fa6d6573d125c450f4ddb))
- add [br] usage hint to title field instructions ([a593dcb](https://github.com/raaaf/starter/commit/a593dcbfaabc131694ccb45a9159ac729e15bcd0))
- add automated releases and WordPress update checker ([15559ec](https://github.com/raaaf/starter/commit/15559ece26c5fccecda16a5a6a8cdefdf896a52c))
- add automatic plugin configuration system ([81d278f](https://github.com/raaaf/starter/commit/81d278fda83f4223c060f45c3e3b60763f4936cf))
- add design token management system ([a848cfc](https://github.com/raaaf/starter/commit/a848cfc1ba3b2869fe264b3528cea2533c8c85c8))
- add footer alert bar for legal disclaimers and notices ([620d4cb](https://github.com/raaaf/starter/commit/620d4cbf726a9d41f9780de7d4c648e1baf92e28))
- add optional scroll animations for sections ([0402619](https://github.com/raaaf/starter/commit/0402619ddcbdf217d27b7e31046fc92d8f980742))
- add section header and icon shortcode to column layouts ([5874339](https://github.com/raaaf/starter/commit/5874339338335af24fb936f83446ea09de77e8d6))
- add theme packaging script ([e6b1852](https://github.com/raaaf/starter/commit/e6b18525b7fb11ba5af70752c424f11d031ecb81))
- add ThemeContext for multisite option key isolation ([5acdc53](https://github.com/raaaf/starter/commit/5acdc536b39fbb8c53726284d807aee1cfc916c1))
- allow manual line breaks in headings and fix badge dark mode text color ([e757e26](https://github.com/raaaf/starter/commit/e757e26fb680d9780e221e816b203ca2fa6935fb))
- block WP backend access for member_area_access role ([1822016](https://github.com/raaaf/starter/commit/182201645a4fd695278aca166b184e95f51223d9))
- **blog:** redesign blog templates with bento grid layout ([e13e6f9](https://github.com/raaaf/starter/commit/e13e6f90d106340e1a01c8b7c4526f8cec754ecb))
- **cf7:** add server-side spam protection for Contact Form 7 ([837737c](https://github.com/raaaf/starter/commit/837737cc3d1238f485f6e69003fdda97f34ab6a6))
- **components:** hint and error props for checkbox, radio, toggle ([7fdaec5](https://github.com/raaaf/starter/commit/7fdaec5f5cf9af83424e9444b77eafde0c4af1f6)), closes [#6](https://github.com/raaaf/starter/issues/6)
- **cpt:** add Team CPT with admin columns and field grouping ([340cefc](https://github.com/raaaf/starter/commit/340cefc70150411b7ee4228663e9ddfb99dee271))
- **css:** add Contact Form 7 design system integration styles ([70779bc](https://github.com/raaaf/starter/commit/70779bc9a5cceae0e6e6ad3c62ec99939fc89fb2))
- **editor:** add TinyMCE editor styles and custom formats ([4c29912](https://github.com/raaaf/starter/commit/4c2991259f186d95d90f887789223ca52c081024))
- **i18n:** add full internationalization support for translation readiness ([186cf76](https://github.com/raaaf/starter/commit/186cf769db65d852ee37fcc5c9e7772d1ad813d1))
- load allowed roles dynamically from WordPress role registry ([2ad47e7](https://github.com/raaaf/starter/commit/2ad47e710590801c31702f4e8c51d6e834aae44f))
- **member-area:** ACF fields, taxonomy, icons, CSS refinements ([46a0262](https://github.com/raaaf/starter/commit/46a02626e6edd56637b7b460e630f2e3d96085c0))
- **member-area:** implement iteration 2 — admin clarity, multi-source docs, simplified frontend ([cfa6866](https://github.com/raaaf/starter/commit/cfa6866cabf4775356a739b7db714a59b45ed38e))
- **member-area:** SFTP sync, encrypted passwords, download table ([714f05b](https://github.com/raaaf/starter/commit/714f05b1bf274faaad9a866f0b9bb78621a5c18c))
- **member-area:** templates, login page, alert component, layout fixes ([8922585](https://github.com/raaaf/starter/commit/8922585e40f87eb63be5ce169f4e10a4bd4a92c1))
- migrate analytics from Pirsch to Rybbit ([33efab7](https://github.com/raaaf/starter/commit/33efab7e3ae877bb88c6f0647906850e7f842dfb))
- **plugins:** expand ASE config and add iThemes Security configurator ([c3210d9](https://github.com/raaaf/starter/commit/c3210d97701a6888475f39abed983fecb248bf52))
- register "Zugang Interner Bereich" WordPress user role ([8344cda](https://github.com/raaaf/starter/commit/8344cdae0ce044e5dea3b1ab4f93c7aeecf25e98))
- run ThemeContext migration on boot ([46cca8c](https://github.com/raaaf/starter/commit/46cca8c9f5f3370ce70271e1caace491635b08a1))
- **seo:** add GEO foundations for AI search visibility ([8ae6cad](https://github.com/raaaf/starter/commit/8ae6cad3892b680bcc29232ce3ece7aaefcbfea1))
- **seo:** add social sharing image with full Open Graph metadata ([886bd14](https://github.com/raaaf/starter/commit/886bd1400dbabc23515866bc538b1f2718d5a01a))
- **setup:** add default contact data and hero layout prefill ([fcb3a6c](https://github.com/raaaf/starter/commit/fcb3a6c32a7a06b694156f2ab12426426762b8fa))
- **stats:** allow decimal numbers in stats block ([4dfef90](https://github.com/raaaf/starter/commit/4dfef906cb2e7bd4dafcc1d4802560ff69080164))
- **tokens:** add borderWidth, opacity, sizing, and gradient token processing ([f301825](https://github.com/raaaf/starter/commit/f30182584cadd03c8f3dccf0122d32d123b36dae))
- **tokens:** add fluid typography scale with clamp() ([49bb3ab](https://github.com/raaaf/starter/commit/49bb3ab58058e85ebc17fa915ef3ab1cd41b4e02))
- use ThemeContext key in Acf/Options ([7d3130b](https://github.com/raaaf/starter/commit/7d3130bcbd6efeda80721d7caec17d12cafe0d5e))
- use ThemeContext keys and guards in PluginServiceProvider ([9672f79](https://github.com/raaaf/starter/commit/9672f792587326052a314babcc5c7a0d3218a47a))
- use ThemeContext keys and guards in WelcomeServiceProvider ([8944e7f](https://github.com/raaaf/starter/commit/8944e7f0190e97788d15b103ce9d60d2f135e94a))
- use ThemeContext keys in AbstractPluginConfigurator ([4bbf73e](https://github.com/raaaf/starter/commit/4bbf73e122c555c232d0810a0460401e906c9f6a))
- **vite:** add dynamic port detection for dev server ([89f571a](https://github.com/raaaf/starter/commit/89f571a2c84166c482b2fb559888cadc0bd874d0))

### Performance Improvements

- **images:** add responsive srcset and sizes to all ACF image blocks ([3a84fe9](https://github.com/raaaf/starter/commit/3a84fe92e8f9af297ecc4636c38da1639f8a186c))
- **images:** fix oversized wysiwyg images via wp_content_img_tag filter ([2cedf56](https://github.com/raaaf/starter/commit/2cedf56e1f5c55b436428de54be041703dda6bea))
- lazy-load medium-zoom, dedupe font preloads, add HSTS ([363bb3c](https://github.com/raaaf/starter/commit/363bb3c4fe64415526da8c16b544f27a50ab6529))
- use wp_get_attachment_image for logo with srcset and sizes ([bfff6da](https://github.com/raaaf/starter/commit/bfff6da40d5add177357340bdbc6c5f292da4dd5))

### BREAKING CHANGES

- Pirsch Analytics is no longer supported.
  Install Rybbit WordPress Plugin from GitHub instead.

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>

# [2.21.0](https://github.com/raaaf/starter/compare/v2.20.4...v2.21.0) (2026-06-11)

### Bug Fixes

- full codebase audit — security, a11y, performance, DRY and docs ([598ebbb](https://github.com/raaaf/starter/commit/598ebbb80120fd817589663050509ad62960e038))
- **security:** hardening set from audit follow-up ([062809d](https://github.com/raaaf/starter/commit/062809de3452d0688bc681db1ac662e12c0b775a)), closes [#8](https://github.com/raaaf/starter/issues/8)

### Features

- **acf:** extend FieldDefinitions builders and migrate raw field arrays ([2aa2a76](https://github.com/raaaf/starter/commit/2aa2a76303554c18dfde31a8a5a7ac5bf3540983)), closes [#3](https://github.com/raaaf/starter/issues/3) [#7](https://github.com/raaaf/starter/issues/7)
- **components:** hint and error props for checkbox, radio, toggle ([376b92b](https://github.com/raaaf/starter/commit/376b92bf944a766d678c76d7027ec01e7289607f)), closes [#6](https://github.com/raaaf/starter/issues/6)

## [2.20.4](https://github.com/raaaf/starter/compare/v2.20.3...v2.20.4) (2026-06-08)

### Bug Fixes

- **build:** pin archiver to ^7 (v8 is ESM-only, breaks package script) ([56536a4](https://github.com/raaaf/starter/commit/56536a4b047d242b6c5fe41471679034a7b67477))

## [2.20.3](https://github.com/raaaf/starter/compare/v2.20.2...v2.20.3) (2026-06-08)

### Bug Fixes

- **deps:** upgrade illuminate 12->13 and symfony 7->8 (requires PHP 8.3) ([4a23cb8](https://github.com/raaaf/starter/commit/4a23cb88968bc5c187fd7c6a9484f7daaa45d55a))

## [2.20.2](https://github.com/raaaf/starter/compare/v2.20.1...v2.20.2) (2026-06-08)

### Bug Fixes

- **deps:** bump phpseclib to 3.0.52 (CVE fix) and update bundled deps ([55f0abf](https://github.com/raaaf/starter/commit/55f0abfda3af6323f3dea0435b2e244365350bfc))

## [2.20.1](https://github.com/raaaf/starter/compare/v2.20.0...v2.20.1) (2026-06-08)

### Bug Fixes

- **cf7:** harden wpcf7_spam callback against older CF7 and non-bool input ([a3ec3bc](https://github.com/raaaf/starter/commit/a3ec3bc7b5da442f22c3b67869242300e5903f8c))

# [2.20.0](https://github.com/raaaf/starter/compare/v2.19.8...v2.20.0) (2026-06-08)

### Features

- **cf7:** add server-side spam protection for Contact Form 7 ([752ed39](https://github.com/raaaf/starter/commit/752ed3904cd77122176085a99c3c91932e393472))

## [2.19.8](https://github.com/raaaf/starter/compare/v2.19.7...v2.19.8) (2026-05-07)

### Bug Fixes

- stretch column cards to full row height in image layouts ([f6a5757](https://github.com/raaaf/starter/commit/f6a575794f4bcbc2275d1842a06ec8123ba31a50))

## [2.19.7](https://github.com/raaaf/starter/compare/v2.19.6...v2.19.7) (2026-04-30)

### Bug Fixes

- **member-area:** fetch nonces on-demand to bypass page caches ([54ca6f3](https://github.com/raaaf/starter/commit/54ca6f33e98b3df4b60ed3db09be97114ffc85a5))

## [2.19.6](https://github.com/raaaf/starter/compare/v2.19.5...v2.19.6) (2026-04-24)

### Bug Fixes

- address audit findings for perf refactor ([13328b4](https://github.com/raaaf/starter/commit/13328b4bfaf413761e4ad3478ff9649ef5c08dbb))

### Performance Improvements

- lazy-load medium-zoom, dedupe font preloads, add HSTS ([adbeff0](https://github.com/raaaf/starter/commit/adbeff0d475e8c9146acdd8f7cabafee980d145a))

## [2.19.5](https://github.com/raaaf/starter/compare/v2.19.4...v2.19.5) (2026-04-24)

### Bug Fixes

- suppress password change email when admin edits another user ([319526d](https://github.com/raaaf/starter/commit/319526d549a2fc5275dd9e0ba78bb2e94c5c44b5))

## [2.19.4](https://github.com/raaaf/starter/compare/v2.19.3...v2.19.4) (2026-04-17)

### Bug Fixes

- **a11y:** resolve WCAG issues across components and templates ([076b3e4](https://github.com/raaaf/starter/commit/076b3e4ce640eb6a79facc3b3b3548fc1610f5d1))

## [2.19.3](https://github.com/raaaf/starter/compare/v2.19.2...v2.19.3) (2026-04-16)

### Bug Fixes

- **header:** use h-12 w-auto for logo instead of w-auto max-h-12 ([82992ea](https://github.com/raaaf/starter/commit/82992ea77494a4a4a9b821ea2299e427de6b8eee))

## [2.19.2](https://github.com/raaaf/starter/compare/v2.19.1...v2.19.2) (2026-04-16)

### Bug Fixes

- **svg:** detect real SVG dimensions so logos render at full size ([016660b](https://github.com/raaaf/starter/commit/016660b2ea78ba8958f00ca60105e52bc55e9adb))

## [2.19.1](https://github.com/raaaf/starter/compare/v2.19.0...v2.19.1) (2026-04-16)

### Bug Fixes

- **tokens:** ESM guard + trim trailing zeros in clamp output ([821e0c7](https://github.com/raaaf/starter/commit/821e0c78741daa45b12141d76915eb258c89d883))

# [2.19.0](https://github.com/raaaf/starter/compare/v2.18.0...v2.19.0) (2026-04-16)

### Features

- **tokens:** add fluid typography scale with clamp() ([94011ec](https://github.com/raaaf/starter/commit/94011ec62295f983af17d87c4aa7b2bd1ec3edcc))

# [2.18.0](https://github.com/raaaf/starter/compare/v2.17.1...v2.18.0) (2026-04-16)

### Features

- **seo:** add GEO foundations for AI search visibility ([b4a16e1](https://github.com/raaaf/starter/commit/b4a16e19415d17be8a719ac7f2e3565656448899))

## [2.17.1](https://github.com/raaaf/starter/compare/v2.17.0...v2.17.1) (2026-04-02)

### Bug Fixes

- add margin between label and image in column layouts ([2ed4f23](https://github.com/raaaf/starter/commit/2ed4f2335ca297819a488b007c464a98d73f43f5))

# [2.17.0](https://github.com/raaaf/starter/compare/v2.16.2...v2.17.0) (2026-04-02)

### Features

- **acf:** add optional label field above images in column layouts ([31ee3bb](https://github.com/raaaf/starter/commit/31ee3bbdec238b52e6e8e4669aee0ab2b9c4c296))

## [2.16.2](https://github.com/raaaf/starter/compare/v2.16.1...v2.16.2) (2026-03-31)

### Bug Fixes

- **acf:** fix field width grouping in image column layouts ([eaa7b1e](https://github.com/raaaf/starter/commit/eaa7b1ee85124914a6546dadaed2b9b60604f27c))

## [2.16.1](https://github.com/raaaf/starter/compare/v2.16.0...v2.16.1) (2026-03-31)

### Bug Fixes

- restore WYSIWYG content fields in image column layouts ([4ef3ca5](https://github.com/raaaf/starter/commit/4ef3ca516b5ca1e8eee4d83028cb912328137083))

# [2.16.0](https://github.com/raaaf/starter/compare/v2.15.2...v2.16.0) (2026-03-31)

### Bug Fixes

- **a11y:** add aria-labelledby to accordion panels in image layouts ([817302a](https://github.com/raaaf/starter/commit/817302a05a27a2f37f80993cb1e8bb5355b12293))

### Features

- **acf:** add accordion to image column layouts and enable modal search ([53e8865](https://github.com/raaaf/starter/commit/53e8865e706c812c4ea0dbf58eb35c7c8455e0ac))

## [2.15.2](https://github.com/raaaf/starter/compare/v2.15.1...v2.15.2) (2026-03-27)

### Bug Fixes

- **css:** use aspect-ratio on mejs-container for responsive video sizing ([b76210e](https://github.com/raaaf/starter/commit/b76210e8a6cbaec57ccd9ef5ebe1849003b60e88))

## [2.15.1](https://github.com/raaaf/starter/compare/v2.15.0...v2.15.1) (2026-03-27)

### Bug Fixes

- **css:** add height auto to mejs-container for responsive video sizing ([c419b78](https://github.com/raaaf/starter/commit/c419b785ed844129a99352978e3cc08b56383426))

# [2.15.0](https://github.com/raaaf/starter/compare/v2.14.2...v2.15.0) (2026-03-27)

### Bug Fixes

- **acf:** add content guards and fix DocBlocks for image layouts ([7323abf](https://github.com/raaaf/starter/commit/7323abf9248d320459314cd974bd65a31d84dbac))
- add missing PHPStan return type annotations for image layout fields ([8755bd7](https://github.com/raaaf/starter/commit/8755bd7d05fea51ceee4c0c2f992400fb7862627))
- **auth:** pass is_ssl() to wp_signon for correct cookie handling ([08a9935](https://github.com/raaaf/starter/commit/08a9935a51b58b81f4787b458262303917a213db))
- **css:** prevent inline video collapse and fix play button centering ([a1e838d](https://github.com/raaaf/starter/commit/a1e838d46366f73222726a746e352449b0cace50))
- skip scroll animations on anchor hash navigation ([dd108d0](https://github.com/raaaf/starter/commit/dd108d0c4ca4aa67a79f8f7e8588dcd2e38ed521))

### Features

- **acf:** add one-column, three-columns, and four-columns image layouts ([360f372](https://github.com/raaaf/starter/commit/360f372724f7f536ee3346c3d4b2f52e9b75836f))

## [2.14.3](https://github.com/raaaf/starter/compare/v2.14.2...v2.14.3) (2026-03-27)

### Bug Fixes

- **auth:** pass is_ssl() to wp_signon for correct cookie handling ([08a9935](https://github.com/raaaf/starter/commit/08a9935a51b58b81f4787b458262303917a213db))
- skip scroll animations on anchor hash navigation ([dd108d0](https://github.com/raaaf/starter/commit/dd108d0c4ca4aa67a79f8f7e8588dcd2e38ed521))

## [2.14.2](https://github.com/raaaf/starter/compare/v2.14.1...v2.14.2) (2026-03-27)

### Bug Fixes

- **auth:** always grant member area access to administrators ([3e2527b](https://github.com/raaaf/starter/commit/3e2527bfebf405879acd706da21fd5f9797462d1))

## [2.14.1](https://github.com/raaaf/starter/compare/v2.14.0...v2.14.1) (2026-03-27)

### Bug Fixes

- **auth:** stop sanitizing passwords and improve login error messages ([b453bb0](https://github.com/raaaf/starter/commit/b453bb0b6bbce96abcae9fb9527af632332f3962))

# [2.14.0](https://github.com/raaaf/starter/compare/v2.13.2...v2.14.0) (2026-03-26)

### Features

- **acf:** add [br] line break support to all prose text fields ([a8ba8c6](https://github.com/raaaf/starter/commit/a8ba8c65cc4003c8e4d850705a31ab10df9a013d))

## [2.13.2](https://github.com/raaaf/starter/compare/v2.13.1...v2.13.2) (2026-03-26)

## [2.13.1](https://github.com/raaaf/starter/compare/v2.13.0...v2.13.1) (2026-03-26)

### Bug Fixes

- **seo:** add is_multisite mock to test bootstrap ([70e3c53](https://github.com/raaaf/starter/commit/70e3c53a8f382523f2d60b2c81f9bc1ce8172960))
- **seo:** correct robots.txt sitemap URL for multisite with domain mapping ([0a4f757](https://github.com/raaaf/starter/commit/0a4f757eba16feb2f00a3276066f913a600b939a))

# [2.13.0](https://github.com/raaaf/starter/compare/v2.12.5...v2.13.0) (2026-03-24)

### Bug Fixes

- alert bar design tweaks and WYSIWYG field ([2ffe275](https://github.com/raaaf/starter/commit/2ffe275f19ae923cdd9e642ef540146858a27279))
- use basic WYSIWYG toolbar, fix invisible hover on close button ([5beb1a1](https://github.com/raaaf/starter/commit/5beb1a134cc03028a95ac3fe7be535f1c9f68c7b))

### Features

- add footer alert bar for legal disclaimers and notices ([a1ccd9d](https://github.com/raaaf/starter/commit/a1ccd9df87a2c6ea740cd7df19894d967c20cc8f))

## [2.12.5](https://github.com/raaaf/starter/compare/v2.12.4...v2.12.5) (2026-03-20)

### Bug Fixes

- make WordPress video player responsive inside card containers ([65cc329](https://github.com/raaaf/starter/commit/65cc3299055324057763117e27ba8e16ac82c08e))

## [2.12.4](https://github.com/raaaf/starter/compare/v2.12.3...v2.12.4) (2026-03-19)

## [2.12.3](https://github.com/raaaf/starter/compare/v2.12.2...v2.12.3) (2026-03-19)

### Bug Fixes

- add space between number and suffix in stats block ([276ab82](https://github.com/raaaf/starter/commit/276ab820c46be1ca0478e232a6668c0b0df45e93))

## [2.12.2](https://github.com/raaaf/starter/compare/v2.12.1...v2.12.2) (2026-03-19)

### Bug Fixes

- use esc_attr instead of intval for CF7 form ID ([9f60ae7](https://github.com/raaaf/starter/commit/9f60ae7f7df2eef89f5a8615c9c4e2a06cf33a51))

## [2.12.1](https://github.com/raaaf/starter/compare/v2.12.0...v2.12.1) (2026-03-19)

### Bug Fixes

- **setup:** replace use statements in Blade and hardcoded references ([db56ecf](https://github.com/raaaf/starter/commit/db56ecf49acf4c93284391e7e7a7fe1dc27bca56))

# [2.12.0](https://github.com/raaaf/starter/compare/v2.11.2...v2.12.0) (2026-03-17)

### Features

- **acf:** make multi-column layout fields optional ([bc72104](https://github.com/raaaf/starter/commit/bc72104c18d4f78d2890eafbdd971ce4a6ed2835))

## [2.11.2](https://github.com/raaaf/starter/compare/v2.11.1...v2.11.2) (2026-03-17)

### Bug Fixes

- make WordPress video embeds responsive with rounded corners ([536586c](https://github.com/raaaf/starter/commit/536586c98895ae298304386ee7e57a8a3404f670))

## [2.11.1](https://github.com/raaaf/starter/compare/v2.11.0...v2.11.1) (2026-03-17)

### Bug Fixes

- **security:** add media-src CSP directive for external video URLs ([a31064b](https://github.com/raaaf/starter/commit/a31064b773302d8ce00bc3353844201a0b8b191c))

# [2.11.0](https://github.com/raaaf/starter/compare/v2.10.0...v2.11.0) (2026-03-16)

### Features

- **acf:** add external video URL source and automatic section anchors ([#1](https://github.com/raaaf/starter/issues/1)) ([b8cd667](https://github.com/raaaf/starter/commit/b8cd667d1fd626f8b1f8d1eda3e73b42a6bd9acb))

# [2.10.0](https://github.com/raaaf/starter/compare/v2.9.7...v2.10.0) (2026-03-12)

### Features

- **stats:** allow decimal numbers in stats block ([748d94e](https://github.com/raaaf/starter/commit/748d94ecc511f593c2d3450ba704d5ffa9a4d7f3))

## [2.9.7](https://github.com/raaaf/starter/compare/v2.9.6...v2.9.7) (2026-03-10)

### Bug Fixes

- resolve security and performance findings from audit ([844101d](https://github.com/raaaf/starter/commit/844101d4495b3f03dc5c74b8ca3cc235cab96cdd))

## [2.9.6](https://github.com/raaaf/starter/compare/v2.9.5...v2.9.6) (2026-03-10)

### Bug Fixes

- full codebase audit — security, a11y, perf, and code quality fixes ([580529b](https://github.com/raaaf/starter/commit/580529b0e1a1ed7dc5c4e674871ebad0c2455870))

## [2.9.5](https://github.com/raaaf/starter/compare/v2.9.4...v2.9.5) (2026-03-06)

### Bug Fixes

- multisite autoloader guard and remove spatie/schema-org dependency ([fba109b](https://github.com/raaaf/starter/commit/fba109bf385f7593364b028dcd3d92f2413ec043))

## [2.9.4](https://github.com/raaaf/starter/compare/v2.9.3...v2.9.4) (2026-03-06)

### Bug Fixes

- update composer.lock to remove spatie/schema-org ([9973574](https://github.com/raaaf/starter/commit/997357451efb1c03d2386c6b40869491dac79573))

## [2.9.3](https://github.com/raaaf/starter/compare/v2.9.2...v2.9.3) (2026-03-06)

### Bug Fixes

- remove extra blank line after namespace declaration ([d2b7669](https://github.com/raaaf/starter/commit/d2b7669a928efac9ee85f8520de996457b2aea78))
- replace spatie/schema-org with direct PHP arrays in breadcrumb schema ([7761c3c](https://github.com/raaaf/starter/commit/7761c3ccd7ad98e8edffd08a6e8c6c67e8e7b7f5))

## [2.9.2](https://github.com/raaaf/starter/compare/v2.9.1...v2.9.2) (2026-03-06)

### Bug Fixes

- isolate option keys for multisite compatibility ([3eb613f](https://github.com/raaaf/starter/commit/3eb613fe43da41b29c20985a6129c07f7258da98))
- **setup:** add ThemeUpdateProvider config and fix CLAUDE.md patterns ([a443f77](https://github.com/raaaf/starter/commit/a443f77d77734cc115f590203994e22471e0466f))

## [2.9.1](https://github.com/raaaf/starter/compare/v2.9.0...v2.9.1) (2026-03-03)

### Bug Fixes

- sync package-lock.json with package.json (yaml resolution) ([cd6617c](https://github.com/raaaf/starter/commit/cd6617cb777c6630f3acff88d5d457e13f22c324))

# [2.9.0](https://github.com/raaaf/starter/compare/v2.8.4...v2.9.0) (2026-03-03)

### Bug Fixes

- replace remaining wp*starter* keys in Acf/Options ([332f6e9](https://github.com/raaaf/starter/commit/332f6e97090267bc5d7bb4e18a999962bb1583a2))

### Features

- add ThemeContext for multisite option key isolation ([9f83039](https://github.com/raaaf/starter/commit/9f83039272f1df3bdd4af088035ae774daa7a5bf))
- run ThemeContext migration on boot ([76be7b6](https://github.com/raaaf/starter/commit/76be7b6abbdcb74a2d6f7187b75b7605ad31c0da))
- use ThemeContext key in Acf/Options ([223257f](https://github.com/raaaf/starter/commit/223257fab71f58393a1730dc6900cb0b82c58de7))
- use ThemeContext keys and guards in PluginServiceProvider ([378f121](https://github.com/raaaf/starter/commit/378f121c3f0248982616e9b784c7eea503748ba7))
- use ThemeContext keys and guards in WelcomeServiceProvider ([9940cf9](https://github.com/raaaf/starter/commit/9940cf9ded5545610196cae611043a8b54cbf696))
- use ThemeContext keys in AbstractPluginConfigurator ([2da9770](https://github.com/raaaf/starter/commit/2da97702754e77368eecb8731ff07bedd045fd02))

## [2.8.4](https://github.com/raaaf/starter/compare/v2.8.3...v2.8.4) (2026-02-27)

### Bug Fixes

- **ci:** restore --no-dev for packaging to reduce ZIP size ([84afb5a](https://github.com/raaaf/starter/commit/84afb5a391829e5e5c7a0b371c2573c96274c638))

## [2.8.3](https://github.com/raaaf/starter/compare/v2.8.2...v2.8.3) (2026-02-27)

### Bug Fixes

- **ci:** include dev dependencies in theme package ZIP ([316d172](https://github.com/raaaf/starter/commit/316d172ea63e3a24d5eacc28508bc65daf742bd9))

## [2.8.2](https://github.com/raaaf/starter/compare/v2.8.1...v2.8.2) (2026-02-27)

### Bug Fixes

- **images:** rebuild srcset for full-size and intermediate editor images ([302724d](https://github.com/raaaf/starter/commit/302724de7bbafc196ecb8dd763ae370578fc52c5))
- **phpcs:** convert indented comments to block comments in ImageServiceProvider ([867bb10](https://github.com/raaaf/starter/commit/867bb10284b34bdd04966b76ef4d0f830dfa5f80))

## [2.8.1](https://github.com/raaaf/starter/compare/v2.8.0...v2.8.1) (2026-02-27)

### Bug Fixes

- **images:** re-enable medium size and fix wp_content_img_tag filter signature ([204374b](https://github.com/raaaf/starter/commit/204374b6731f0a84b6ff8b7d05f1eaf3635ee44d))
- **phpcs:** suppress slow-query and spelling warnings; fix comment format ([403629a](https://github.com/raaaf/starter/commit/403629acb0db6077919fa3af49e3aad89b8abc77))

# [2.8.0](https://github.com/raaaf/starter/compare/v2.7.5...v2.8.0) (2026-02-27)

### Bug Fixes

- **ci:** ignore ITSEC_Modules in PHPStan — premium plugin not in CI ([dbbb7e2](https://github.com/raaaf/starter/commit/dbbb7e298098dc5060b9cee15b1484ca1264eac4))

### Features

- **plugins:** expand ASE config and add iThemes Security configurator ([791ffe2](https://github.com/raaaf/starter/commit/791ffe24be1e7ec8096a68124ba93b9b16730a7f))

### Performance Improvements

- use wp_get_attachment_image for logo with srcset and sizes ([454e637](https://github.com/raaaf/starter/commit/454e637b5f94f54e52e262a1cd1361dfc3935f35))

## [2.7.5](https://github.com/raaaf/starter/compare/v2.7.4...v2.7.5) (2026-02-27)

### Performance Improvements

- **images:** fix oversized wysiwyg images via wp_content_img_tag filter ([ef1b30b](https://github.com/raaaf/starter/commit/ef1b30bc184725d228c5f9efef3640f8bc0ed4ad))

## [2.7.4](https://github.com/raaaf/starter/compare/v2.7.3...v2.7.4) (2026-02-27)

### Bug Fixes

- **a11y:** remove invalid ARIA roles from navigation menu ([98412c4](https://github.com/raaaf/starter/commit/98412c4ebd4eff561a77e2202a7a63308d14a1e9))

## [2.7.3](https://github.com/raaaf/starter/compare/v2.7.2...v2.7.3) (2026-02-27)

### Performance Improvements

- **images:** add responsive srcset and sizes to all ACF image blocks ([f33f935](https://github.com/raaaf/starter/commit/f33f935c415879b702e98290987190a6ce2e6cd7))

## [2.7.2](https://github.com/raaaf/starter/compare/v2.7.1...v2.7.2) (2026-02-27)

### Bug Fixes

- **deploy:** track vendor directory for WordPress SFTP deployments ([da760a5](https://github.com/raaaf/starter/commit/da760a55c97f95b1ed27cf8d7b1d1a3bc20cbe51))
- **tokens:** replace orphaned CSS custom properties with current token names ([24fd92d](https://github.com/raaaf/starter/commit/24fd92d7fb5bdc6b4c1db0dcc9f0d18c792b5591))

## [2.7.1](https://github.com/raaaf/starter/compare/v2.7.0...v2.7.1) (2026-02-27)

# [2.7.0](https://github.com/raaaf/starter/compare/v2.6.0...v2.7.0) (2026-02-27)

### Bug Fixes

- correct WordPress auth mode comparison and login session handling ([e249380](https://github.com/raaaf/starter/commit/e2493807c3d79aabaae028b023a9e8d4bdb92e3d))

### Features

- load allowed roles dynamically from WordPress role registry ([2a001a9](https://github.com/raaaf/starter/commit/2a001a92bf77ff7a06ca8f99b1f3fa53261cb0f7))

# [2.6.0](https://github.com/raaaf/starter/compare/v2.5.0...v2.6.0) (2026-02-27)

### Bug Fixes

- add chevron icon and appearance-none to select filters in member area ([93e4262](https://github.com/raaaf/starter/commit/93e4262f2876cfd5be479a5bb066711013e21565))
- escape wp_die message with esc_html\_\_ to pass PHPCS ([2b67161](https://github.com/raaaf/starter/commit/2b671614e786639a6d0c00c8625c8075433c0e14))
- remove duplicate chevron icons from select; noindex member area pages ([03328e7](https://github.com/raaaf/starter/commit/03328e718d41bb501915a71e672a12df37293c73))

### Features

- **acf:** add member-downloads Flexible Content block to member area ([5d5d29d](https://github.com/raaaf/starter/commit/5d5d29deaae92cd2bb5b2c3f3d54d23e96edf922))
- block WP backend access for member_area_access role ([8094283](https://github.com/raaaf/starter/commit/8094283c28af76befe6cfe501a8322119d26f131))
- register "Zugang Interner Bereich" WordPress user role ([81a6678](https://github.com/raaaf/starter/commit/81a667873293702efc0ad62b0993bbed6a15b790))

# [2.5.0](https://github.com/raaaf/starter/compare/v2.4.0...v2.5.0) (2026-02-26)

### Bug Fixes

- correct composer installer-paths and ignore theme-local plugins dir ([288a566](https://github.com/raaaf/starter/commit/288a566853329bcae019e92c081d4b40b65dd3f2))
- disable rate limiting in WP_DEBUG mode ([8789500](https://github.com/raaaf/starter/commit/87895008a27d31f2f0833b309d0ded15e0445378))
- **phpcs:** resolve empty catch and post-increment violations ([77461a3](https://github.com/raaaf/starter/commit/77461a3138db62638bd8edb4713bd66ff8a39c38))
- **tests:** add phpseclib3 to external namespace whitelist ([0002e3f](https://github.com/raaaf/starter/commit/0002e3faa96e8e80f8df940a3dfd006f4ea5ef19))

### Features

- **member-area:** ACF fields, taxonomy, icons, CSS refinements ([a7d4ebc](https://github.com/raaaf/starter/commit/a7d4ebc4d9bb2656291e19155317f1466a5186ce))
- **member-area:** implement iteration 2 — admin clarity, multi-source docs, simplified frontend ([dd00f6a](https://github.com/raaaf/starter/commit/dd00f6a753c16741f136e228e8c4da069e46a1a6))
- **member-area:** SFTP sync, encrypted passwords, download table ([a53c42d](https://github.com/raaaf/starter/commit/a53c42d5f1a21e2cbb59e72cdbf5fb79de5c1736))
- **member-area:** templates, login page, alert component, layout fixes ([ed78150](https://github.com/raaaf/starter/commit/ed78150b239cac7dfcd3ca2476a1c8369eebf461))

# [2.4.0](https://github.com/raaaf/starter/compare/v2.3.2...v2.4.0) (2026-02-25)

### Bug Fixes

- **acf:** reorder section header fields and align button group choices ([01684b1](https://github.com/raaaf/starter/commit/01684b1ff9ce5ae411ee4a14ccbb962531d6dbb5))
- **admin:** use flex layout for demo post action buttons ([ac70b65](https://github.com/raaaf/starter/commit/ac70b6522a88514e93673322000841d0b7d6440a))
- align icons with text, fix logo slider gradient and prose code color ([4e659ab](https://github.com/raaaf/starter/commit/4e659ab0619396f02e247ed3a7770f852b49958c))
- **button:** increase specificity of sm/lg size modifiers to override variant defaults ([276bfe3](https://github.com/raaaf/starter/commit/276bfe34975eb517c3a8e17f8c5690094538136b))
- **button:** move md sizing after variants so sm/lg overrides work correctly ([3949133](https://github.com/raaaf/starter/commit/3949133c76389dcded9c2e8873a18b76ea86dcf1))
- **button:** use correct CSS custom property names in button classes ([78d0d65](https://github.com/raaaf/starter/commit/78d0d65d438e8e5f6e5009433a3ba1caf78a370b))
- **styleguide:** add items-start to button size container to prevent flex stretch ([946477e](https://github.com/raaaf/starter/commit/946477e81dbf8acd1bcd0620b4191a5b4d3c80df))
- **styleguide:** remove .button class to avoid WordPress style conflicts ([d606dd1](https://github.com/raaaf/starter/commit/d606dd1c4b2218aed84506f4cfe42a5a350b9d08))
- **styleguide:** remove esc_attr from button class strings to preserve CSS tokens ([105c3cb](https://github.com/raaaf/starter/commit/105c3cb42aa7b256534ade3a89520bc61c906a08))
- **styleguide:** sync button variants and sizes with Button component ([b847720](https://github.com/raaaf/starter/commit/b8477202b576dad1ca0dfa98cc8464530a1a9299))
- **styleguide:** use semantic button CSS classes instead of inline Tailwind utilities ([4fb4f1a](https://github.com/raaaf/starter/commit/4fb4f1a83f657672267804c4de451808572281fb))
- sync composer.lock after rebase — add missing integrate-rybbit package ([34422fe](https://github.com/raaaf/starter/commit/34422fe085745c55c3a8d4cedae32e918bf712b1))
- **vite:** require .vite-port file before dev server check, set strictPort and CORS origin ([c2fd830](https://github.com/raaaf/starter/commit/c2fd830824bee4c4beb2b7a57d55eab69862531e))

### Features

- add section header and icon shortcode to column layouts ([6233500](https://github.com/raaaf/starter/commit/623350063840467e805910c8432f79f6711b4632))
- **editor:** add TinyMCE editor styles and custom formats ([104da5c](https://github.com/raaaf/starter/commit/104da5c9eb511b221aee290ebc0676d0229632c1))

## [2.3.2](https://github.com/raaaf/starter/compare/v2.3.1...v2.3.2) (2026-02-12)

### Bug Fixes

- **acf:** apply [br] replacement in templates instead of ACF filter ([4d3e6e4](https://github.com/raaaf/starter/commit/4d3e6e4e291fafad16932cd1756e521349710864))

## [2.3.1](https://github.com/raaaf/starter/compare/v2.3.0...v2.3.1) (2026-02-12)

### Bug Fixes

- resolve phpcs warning in DesignTokenServiceProvider and add [br] hint ([302a000](https://github.com/raaaf/starter/commit/302a0005f16718cea8e7183f91c3a09dd7e85b88))

# [2.3.0](https://github.com/raaaf/starter/compare/v2.2.1...v2.3.0) (2026-02-12)

### Bug Fixes

- use [br] shortcode instead of HTML for manual line breaks in titles ([d884cf2](https://github.com/raaaf/starter/commit/d884cf28acad31ba3ac0bc29dc272932f1ba470a))

### Features

- add [br] usage hint to title field instructions ([01e1d63](https://github.com/raaaf/starter/commit/01e1d63aa59a7c31e53e5c7eb5b2fca52a7f3fac))

## [2.2.1](https://github.com/raaaf/starter/compare/v2.2.0...v2.2.1) (2026-02-12)

### Bug Fixes

- use placeholder approach for br tag preservation in title fields ([a84fa4b](https://github.com/raaaf/starter/commit/a84fa4bfbfd56e150564efa9065c63ac62aca723))

# [2.2.0](https://github.com/raaaf/starter/compare/v2.1.0...v2.2.0) (2026-02-12)

### Features

- allow manual line breaks in headings and fix badge dark mode text color ([f21d4bf](https://github.com/raaaf/starter/commit/f21d4bfa6dc650dcce7c12fe657eee8ca02a3370))
- **css:** add Contact Form 7 design system integration styles ([13b9a91](https://github.com/raaaf/starter/commit/13b9a91f08ecaa60c4ddf017927e7207c0818d59))

# [2.1.0](https://github.com/raaaf/starter/compare/v2.0.3...v2.1.0) (2026-02-11)

### Bug Fixes

- dark mode variant, WCAG contrast overrides, and semantic token usage in templates ([0d3dee7](https://github.com/raaaf/starter/commit/0d3dee70500b0e7e9ebc19d2b283191ece4b0241))

### Features

- **tokens:** add borderWidth, opacity, sizing, and gradient token processing ([888b2b6](https://github.com/raaaf/starter/commit/888b2b638980dda07df2ac7d1a4f0a5afb6e1f40))

## [2.0.3](https://github.com/raaaf/starter/compare/v2.0.2...v2.0.3) (2026-02-11)

### Bug Fixes

- use var() references for semantic tokens and bind prose colors to design tokens ([c41a8b0](https://github.com/raaaf/starter/commit/c41a8b0a66ec6d7144b93bf7831b0e69a93cf198))

## [2.0.2](https://github.com/raaaf/starter/compare/v2.0.1...v2.0.2) (2026-02-11)

### Bug Fixes

- resolve namespace from composer.json in NamespaceConsistencyTest ([b8895a2](https://github.com/raaaf/starter/commit/b8895a2a624bdf7164348f81e5e0f6a3c0a9b1ab))

## [2.0.1](https://github.com/raaaf/starter/compare/v2.0.0...v2.0.1) (2026-02-11)

### Bug Fixes

- use correct wpackagist slug for Rybbit plugin (integrate-rybbit) ([181ce2e](https://github.com/raaaf/starter/commit/181ce2e18c17748c4ab207a80be8f996aef506b7))

# [2.0.0](https://github.com/raaaf/starter/compare/v1.6.1...v2.0.0) (2026-01-30)

### Features

- migrate analytics from Pirsch to Rybbit ([1bfb963](https://github.com/raaaf/starter/commit/1bfb963a6958b565c46a808fc1d21d3c70f679cd))

### BREAKING CHANGES

- Pirsch Analytics is no longer supported.
  Install Rybbit WordPress Plugin from GitHub instead.

Co-Authored-By: Claude Opus 4.5 <noreply@anthropic.com>

## [1.6.1](https://github.com/raaaf/starter/compare/v1.6.0...v1.6.1) (2026-01-29)

### Bug Fixes

- improve dark mode support and WCAG accessibility ([fc3262f](https://github.com/raaaf/starter/commit/fc3262f2df7bd42d25efc3cd2a18b4cd89938da1)), closes [#e5541d](https://github.com/raaaf/starter/issues/e5541d)

# [1.6.0](https://github.com/raaaf/starter/compare/v1.5.0...v1.6.0) (2026-01-28)

### Bug Fixes

- **hero:** use direct opacity instead of Tailwind CSS variable ([545c505](https://github.com/raaaf/starter/commit/545c505babcf8b62f42b0bad65d0ca3a43d459fd))
- lower PHP requirement to 8.2 for broader hosting compatibility ([e46f44e](https://github.com/raaaf/starter/commit/e46f44e228e20b218926f54aeb9bab6586d8c6af))
- resolve PHPCS and PHPUnit CI failures ([e407294](https://github.com/raaaf/starter/commit/e40729472c9a87746c296cc0ac49f109d9fdd6d5))
- resolve PHPStan CI failures ([be2f54d](https://github.com/raaaf/starter/commit/be2f54d378948e32ed8434962cf02c9ea4d36f3b))
- **templates:** hide page header when ACF flexible content exists ([e63df2c](https://github.com/raaaf/starter/commit/e63df2c5d4a59f8ddbff73ce7fddb711ccf1769b))

### Features

- **acf:** add source selection to Team and Testimonials layouts ([3a228d0](https://github.com/raaaf/starter/commit/3a228d0e4cb6c23a162e969d96f798aaba9ccdcb))
- add design token management system ([0e57739](https://github.com/raaaf/starter/commit/0e577393f218144c52533bf7a596cbae1c129535))
- add theme packaging script ([58656b3](https://github.com/raaaf/starter/commit/58656b38200ad383041d0600c638f262cdf89061))
- **cpt:** add Team CPT with admin columns and field grouping ([2edb32a](https://github.com/raaaf/starter/commit/2edb32aaed6f82eb04542ec7795f400d6e0e46ee))
- **setup:** add default contact data and hero layout prefill ([454448b](https://github.com/raaaf/starter/commit/454448bb123a0991e891a7fdedd30bf7284f1f77))

# [1.5.0](https://github.com/raaaf/starter/compare/v1.4.0...v1.5.0) (2026-01-27)

### Features

- add automatic plugin configuration system ([a7370e2](https://github.com/raaaf/starter/commit/a7370e243cdbdacc9700eaef798fed0d62629cdb))

# [1.4.0](https://github.com/raaaf/starter/compare/v1.3.0...v1.4.0) (2026-01-27)

### Features

- **acf:** prefill new pages with Hero layout ([6d41d3c](https://github.com/raaaf/starter/commit/6d41d3cbd6a65c31b56df5862652c8f0d6477b4a))
- **vite:** add dynamic port detection for dev server ([d54c4fe](https://github.com/raaaf/starter/commit/d54c4fe2da7dfbe882cfb4083789fde923ea117c))

# [1.3.0](https://github.com/raaaf/starter/compare/v1.2.0...v1.3.0) (2026-01-22)

### Features

- add optional scroll animations for sections ([4d8e351](https://github.com/raaaf/starter/commit/4d8e3513f6ddbcb3dd5380d081dfb9e2f716fdd2))

# [1.2.0](https://github.com/raaaf/starter/compare/v1.1.2...v1.2.0) (2026-01-22)

### Features

- **blog:** redesign blog templates with bento grid layout ([72c5b9f](https://github.com/raaaf/starter/commit/72c5b9f878f17bbad603bed4d4fb27691eb232ae))

## [1.1.2](https://github.com/raaaf/starter/compare/v1.1.1...v1.1.2) (2026-01-22)

### Bug Fixes

- CSS layer architecture, namespace consistency, and Alpine.js syntax ([ab4e62d](https://github.com/raaaf/starter/commit/ab4e62db6bb8fd63ca73a25362c659ad0c2602f4))

## [1.1.1](https://github.com/raaaf/starter/compare/v1.1.0...v1.1.1) (2026-01-22)

### Bug Fixes

- **release:** auto-update style.css version on release ([15752c0](https://github.com/raaaf/starter/commit/15752c0e201c1e662cb447c53f1ac9b96933e240))

# [1.1.0](https://github.com/raaaf/starter/compare/v1.0.0...v1.1.0) (2026-01-22)

### Bug Fixes

- **deps:** sync composer.lock with webp-express package ([6e62188](https://github.com/raaaf/starter/commit/6e621880a61de28ca666804b88b8c8e0526ef01b))
- **deps:** sync package-lock.json with updated dependencies ([07e50d6](https://github.com/raaaf/starter/commit/07e50d631ff8f53cf40f5475127c56dce776b02a))
- **tests:** update FieldDefinitionsTest to use methods instead of constants ([652db5d](https://github.com/raaaf/starter/commit/652db5dea51078264c710a4292f781534eb72aa2))

### Features

- **i18n:** add full internationalization support for translation readiness ([6d942c1](https://github.com/raaaf/starter/commit/6d942c1a5c898e72d60bc6cfacd7b0b4df76bcef))
- **seo:** add social sharing image with full Open Graph metadata ([a5e60a5](https://github.com/raaaf/starter/commit/a5e60a52327da07368d13e1a2924bfe714aeeae0))

# 1.0.0 (2026-01-22)

### Bug Fixes

- **ci:** mark compiled directory as optional in PHPStan config ([6c7764e](https://github.com/raaaf/starter/commit/6c7764ecb01f42b6a83d18cc40d432abeaec2dc7))
- **i18n:** add missing translators comment for PHPCS ([a1f7dab](https://github.com/raaaf/starter/commit/a1f7dab8071257c9051385e141e8f98d752888e2))

### Features

- add automated releases and WordPress update checker ([53d75f2](https://github.com/raaaf/starter/commit/53d75f25255466de54232b2c66df9ac725318b97))

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Security escaping for all block templates (OWASP compliance)
- Accessibility improvements (WCAG 2.1 AA)
  - Skip link with working `#main-content` target
  - Visible focus states using `focus-visible`
  - Link underlines for non-color-only identification
- WebP image support via `Fields::pictureWebP()` helper
- Resource hints for Pirsch Analytics (dns-prefetch)
- Open Graph and Twitter Card meta tags
- Extended Schema.org markup (Organization, Article)
- Custom 404 error page with search and navigation
- GitHub Actions CI/CD pipeline
- CHANGELOG.md for version tracking

### Changed

- Replaced `focus:outline-none` with `focus-visible` for better keyboard accessibility
- Updated button component with proper URL/attribute escaping
- Improved `Fields::responsiveImage()` with default lazy loading

### Fixed

- XSS vulnerability in CTA block ID generation
- Missing `esc_attr()` on anchor IDs across blocks
- Missing `esc_url()` on video and map URLs

## [1.0.0] - 2026-01-20

### Added

- Initial release with modern WordPress theme architecture
- Vite 7.3 build system with HMR
- TailwindCSS v4.1 with Figma design tokens
- Alpine.js v3.15 for reactive components
- Laravel Blade templating engine
- ACF Pro integration with 27 custom blocks
- Service Provider architecture
- PHP 8.2+ with strict types
- Pirsch Analytics integration (GDPR-compliant)
- Content Security Policy (CSP) headers
- Comprehensive test suite (PHPUnit, Vitest)
