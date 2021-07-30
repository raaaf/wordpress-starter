<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;

$GLOBALS['filesystem'] = new Filesystem;
$GLOBALS['compiler'] = new BladeCompiler($GLOBALS['filesystem'], getCompiledTemplateDirectory());

add_action('init', function () {
	$GLOBALS['compiler']->component('partials.the_loop', 'loop');
});

add_action('wp_enqueue_scripts', function () {
	$theme = wp_get_theme();

	if (!function_exists('enablejQuery') || !enablejQuery()) {
		wp_deregister_script('jquery');
	}

	wp_enqueue_style('style', theme_get_mix_compiled_asset_url('dist/app.css'), array(), $theme->get('Version'));
	wp_enqueue_script('app', theme_get_mix_compiled_asset_url('dist/app.js'), array('jquery'), $theme->get('Version'), true);
});

add_filter('template_include', function ($template) {
	$templateName = wp_basename(wp_basename($template, '.php'), '.blade');
	if (getBladeViewFactory()->exists($templateName)) {
		$GLOBALS['template_name'] = $templateName;
		$template = __DIR__ . '/../config/index.php';
	}

	return $template;
});

collect([
	'index',
	'404',
	'archive',
	'author',
	'category',
	'tag',
	'taxonomy',
	'date',
	'embed',
	'home',
	'frontpage',
	'privacypolicy',
	'page',
	'paged',
	'search',
	'single',
	'singular',
	'attachment'
])->each(function ($type) {
	add_filter("{$type}_template_hierarchy", function ($templates) {
		return collect($templates)->map(function ($template) {
			$filename = wp_basename($template, '.php');
			return ["templates/$filename.blade.php", $template];
		})->flatten()
			->toArray();
	});
});

function getBladeViewFactory()
{
	$viewResolver = new EngineResolver;

	$viewResolver->register('blade', function () {
		return new CompilerEngine($GLOBALS['compiler']);
	});

	$viewResolver->register('php', function () {
		return new PhpEngine;
	});

	return new Factory(
		$viewResolver,
		new FileViewFinder($GLOBALS['filesystem'], getTemplateDirectory()),
		new Dispatcher()
	);
}

function getTemplateDirectory()
{
	return [__DIR__ . '/../templates/'];
}

function getCompiledTemplateDirectory()
{
	return __DIR__ . '/../compiled/';
}


function register_my_menus()
{
	register_nav_menus(
		array(
			'header-menu' => __('Header Menu'),
			'legal-menu' => __('Legal Menu'),
			'footer-menu' => __('Footer Menu'),
		)
	);
}
add_action('init', 'register_my_menus');

/**
 * Get mix compiled asset.
 *
 * @param string $path The path to the asset.
 *
 * @return string
 */
function theme_get_mix_compiled_asset_url($path)
{
	$path                = '/' . $path;
	$stylesheet_dir_uri  = get_stylesheet_directory_uri();
	$stylesheet_dir_path = get_stylesheet_directory();

	if (!file_exists($stylesheet_dir_path . '/mix-manifest.json')) {
		return $stylesheet_dir_uri . $path;
	}

	$mix_file_path = file_get_contents($stylesheet_dir_path . '/mix-manifest.json');
	$manifest      = json_decode($mix_file_path, true);
	$asset_path    = !empty($manifest[$path]) ? $manifest[$path] : $path;

	return $stylesheet_dir_uri . $asset_path;
}

/**
 * Get data from the brand.json file.
 *
 * @param mixed $key The key to retrieve.
 *
 * @return mixed|null
 */
function brand_get_data($key = null)
{
	$config = json_decode(file_get_contents(get_stylesheet_directory() . '/brand.json'), true);

	if ($key === null) {
		return filter_var_array($config, FILTER_SANITIZE_STRING);
	}

	$option = filter_var($config[$key], FILTER_SANITIZE_STRING);

	return $option ?? null;
}

/**
 * Theme setup.
 */
function theme_setup()
{
	// Let WordPress manage the document title.
	add_theme_support('title-tag');

	// Allow Custom Logo
	add_theme_support( 'custom-logo' );

	// Switch default core markup for search form, comment form, and comments
	// to output valid HTML5.
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		)
	);

	// Adding Thumbnail basic support.
	add_theme_support('post-thumbnails');

	// Block editor.
	add_theme_support('align-wide');

	add_theme_support('wp-block-styles');

	add_theme_support('editor-styles');
	add_editor_style();

	$brand = brand_get_data();

	$colors = array_map(
		function ($color, $hex) {
			return array(
				'name'  => ucfirst($color),
				'slug'  => $color,
				'color' => $hex,
			);
		},
		array_keys($brand['colors']),
		$brand['colors']
	);

	$font_sizes = array_map(
		function ($size, $px) {
			return array(
				'name' => ucfirst($size),
				'size' => $px[0],
				'slug' => $size,
			);
		},
		array_keys($brand['fontSizes']),
		$brand['fontSizes']
	);

	add_theme_support('editor-color-palette', $colors);
	add_theme_support('editor-font-sizes', $font_sizes);
}

add_action('after_setup_theme', 'theme_setup');

/**
 * Adds option 'li_class' to 'wp_nav_menu'.
 *
 * @param string  $classes String of classes.
 * @param mixed   $item The curren item.
 * @param WP_Term $args Holds the nav menu arguments.
 *
 * @return array
 */
function theme_nav_menu_add_li_class($classes, $item, $args, $depth)
{
	if (isset($args->li_class)) {
		$classes[] = $args->li_class;
	}

	if (isset($args->{"li_class_$depth"})) {
		$classes[] = $args->{"li_class_$depth"};
	}

	return $classes;
}

add_filter('nav_menu_css_class', 'theme_nav_menu_add_li_class', 10, 4);

/**
 * Adds option 'submenu_class' to 'wp_nav_menu'.
 *
 * @param string  $classes String of classes.
 * @param mixed   $item The curren item.
 * @param WP_Term $args Holds the nav menu arguments.
 *
 * @return array
 */
function theme_nav_menu_add_submenu_class($classes, $args, $depth)
{
	if (isset($args->submenu_class)) {
		$classes[] = $args->submenu_class;
	}

	if (isset($args->{"submenu_class_$depth"})) {
		$classes[] = $args->{"submenu_class_$depth"};
	}

	return $classes;
}

add_filter('nav_menu_submenu_css_class', 'theme_nav_menu_add_submenu_class', 10, 3);

add_action('admin_init', function () {
    // Redirect any user trying to access comments page
    global $pagenow;

    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }

    // Remove comments metabox from dashboard
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});

// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);

// Remove comments page in menu
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});

// Remove comments links from admin bar
add_action('init', function () {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
});