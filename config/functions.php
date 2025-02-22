<?php

// Load Composer dependencies only if the file exists
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

use Illuminate\Container\Container;
use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;

function getBladeViewFactory()
{
    return $GLOBALS['blade'] ?? null;
}

// Set up Laravel Blade templating system
function theme_setup_blade()
{
    $filesystem = new Filesystem();
    $compiler = new BladeCompiler($filesystem, getCompiledTemplateDirectory());

    $viewResolver = new EngineResolver();
    $viewResolver->register('blade', function () use ($compiler) {
        return new CompilerEngine($compiler);
    });
    $viewResolver->register('php', function () {
        return new PhpEngine();
    });

    $container = new Container();
    $container->singleton('blade.compiler', function () use ($compiler) {
        return $compiler;
    });

    $container->singleton('view', function () use ($viewResolver, $filesystem) {
        return new Factory($viewResolver, new FileViewFinder($filesystem, getTemplateDirectory()), new Dispatcher());
    });

    // Set the application container for Facades
    Facade::setFacadeApplication($container);

    return $container->make('view');
}

$GLOBALS['blade'] = theme_setup_blade();

// Register Blade components only if Blade exists
add_action('init', function () {
    if (class_exists('Illuminate\View\Factory')) {
        Blade::component('partials.the_loop', 'loop');
    }
});

// Register theme menus
add_action('init', function () {
    register_nav_menus([
        'header-menu' => __('Header Menu'),
        'legal-menu'  => __('Legal Menu'),
        'footer-menu' => __('Footer Menu'),
    ]);
});

// Enqueue styles and scripts from Laravel Mix
add_action('wp_enqueue_scripts', function () {
    $theme_version = get_transient('theme_version');
    if (!$theme_version) {
        $theme_version = wp_get_theme()->get('Version');
        set_transient('theme_version', $theme_version, DAY_IN_SECONDS);
    }

    // Load Laravel Mix assets
    wp_enqueue_style('tailwindcss', theme_get_mix_asset('dist/app.css'), [], $theme_version);

    // Load main JS but defer for slow connections
    echo '<script>
        if (navigator.connection && navigator.connection.effectiveType === "4g") {
            let script = document.createElement("script");
            script.src = "' . theme_get_mix_asset('dist/app.js') . '";
            script.defer = true;
            document.body.appendChild(script);
        }
    </script>';
}, 20);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('critical-css', get_stylesheet_directory_uri() . '/dist/critical.css', [], null, 'all');
}, 1);

// Optimize template loading
add_filter('template_include', function ($template) {
    $templateName = wp_basename($template, '.php');
    $templateName = wp_basename($templateName, '.blade');

    if ($GLOBALS['blade']->exists($templateName)) {
        $GLOBALS['template_name'] = $templateName;
        return __DIR__ . '/../config/index.php';
    }

    return $template;
});

// Function to load assets from Laravel Mix
function theme_get_mix_asset($path)
{
    $stylesheet_dir_uri  = rtrim(get_stylesheet_directory_uri(), '/');
    $stylesheet_dir_path = get_stylesheet_directory();
    $manifest_path       = $stylesheet_dir_path . '/mix-manifest.json';

    if (!file_exists($manifest_path)) {
        return $stylesheet_dir_uri . '/' . ltrim($path, '/');
    }

    $manifest = json_decode(file_get_contents($manifest_path), true);
    $key = '/' . ltrim($path, '/');

    if (!isset($manifest[$key])) {
        return $stylesheet_dir_uri . '/' . ltrim($path, '/');
    }

    return $stylesheet_dir_uri . $manifest[$key];
}

// Define template directories
function getTemplateDirectory()
{
    return [__DIR__ . '/../templates/'];
}

function getCompiledTemplateDirectory()
{
    return __DIR__ . '/../compiled/';
}

// Theme setup
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ]);
    add_theme_support('post-thumbnails');
    add_theme_support('align-wide');
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_editor_style('dist/editor-style.css');
    add_theme_support('editor-color-palette');
    add_theme_support('editor-font-sizes');
});

// Add custom classes to menu items
add_filter('nav_menu_css_class', function ($classes, $item, $args, $depth) {
    if (isset($args->li_class)) {
        $classes[] = $args->li_class;
    }
    if (isset($args->{"li_class_$depth"})) {
        $classes[] = $args->{"li_class_$depth"};
    }
    return $classes;
}, 10, 4);

add_filter('nav_menu_submenu_css_class', function ($classes, $args, $depth) {
    if (isset($args->submenu_class)) {
        $classes[] = $args->submenu_class;
    }
    if (isset($args->{"submenu_class_$depth"})) {
        $classes[] = $args->{"submenu_class_$depth"};
    }
    return $classes;
}, 10, 3);

// Disable comments completely
add_action('admin_init', function () {
    global $pagenow;
    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);
add_filter('comments_array', '__return_empty_array', 10, 2);
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});
add_action('init', function () {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
});

// ADD nonce
$nonce = wp_create_nonce('csp-nonce');

// Theme json
function theme_json_ld()
{
    $json_ld = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => get_bloginfo('name'),
        "url" => home_url(),
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => home_url() . "/?s={search_term_string}",
            "query-input" => "required name=search_term_string"
        ]
    ];
    echo '<script type="application/ld+json">' . json_encode($json_ld) . '</script>';
}
add_action('wp_head', 'theme_json_ld');
