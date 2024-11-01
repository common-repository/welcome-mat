<?php
defined('ABSPATH') or die('No direct access permitted');

$plugin_version = get_option(MAXINBOUND_VERSION_KEY);
$theme = get_theme_data(get_stylesheet_directory() . '/style.css');
$browser = maxinbound_get_browser();
$templates_copied = maxinbound_check_if_templates_were_copied();

function maxinbound_check_if_templates_were_copied() {
	return false;
	$template_files = MI()->templates()->getlocal();

	foreach ($template_files as $file) {

	}

	// Otherwise
	return true;
}

// http://www.php.net/manual/en/function.get-browser.php#101125.
// Cleaned up a bit, but overall it's the same.
function maxinbound_get_browser() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser_name = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    // First get the platform
    if (preg_match('/linux/i', $user_agent)) {
        $platform = 'Linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
        $platform = 'Mac';
    }
    elseif (preg_match('/windows|win32/i', $user_agent)) {
        $platform = 'Windows';
    }

    // Next get the name of the user agent yes seperately and for good reason
    if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
		$browser_name = 'Internet Explorer';
        $browser_name_short = "MSIE";
    }
    elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser_name = 'Mozilla Firefox';
        $browser_name_short = "Firefox";
    }
    elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser_name = 'Google Chrome';
        $browser_name_short = "Chrome";
    }
    elseif (preg_match('/Safari/i', $user_agent)) {
        $browser_name = 'Apple Safari';
        $browser_name_short = "Safari";
    }
    elseif (preg_match('/Opera/i', $user_agent)) {
        $browser_name = 'Opera';
        $browser_name_short = "Opera";
    }
    elseif (preg_match('/Netscape/i', $user_agent)) {
        $browser_name = 'Netscape';
        $browser_name_short = "Netscape";
    }

    // Finally get the correct version number
    $known = array('Version', $browser_name_short, 'other');
    $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $user_agent, $matches)) {
        // We have no matching number just continue
    }

    // See how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        // We will have two since we are not using 'other' argument yet
        // See if version is before or after the name
        if (strripos($user_agent, "Version") < strripos($user_agent, $browser_name_short)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }

    // Check if we have a number
    if ($version == null || $version == "") { $version = "?"; }

    return array(
        'user_agent' => $user_agent,
        'name' => $browser_name,
        'version' => $version,
        'platform' => $platform,
        'pattern' => $pattern
    );
}
?>

<div id="maxinbound" class="editor" data-view='tabs'>
	<div class="wrap">
		<h1><?php _e("Info","maxinbound"); ?></h1>

	<div class='mb_tab option-container'>
		<div class="title">
			<span class="dashicons dashicons-admin-settings"></span>
			<span class="title"><?php _e("Options","maxinbound"); ?></span>
		</div>

		<div class='inside'>


	<textarea class="system-info" readonly="readonly" wrap="off">
----- Begin System Info -----

MaxInbound Version:     <?php echo $plugin_version . "\n"; ?>
WordPress Version:      <?php echo get_bloginfo('version') . "\n"; ?>
PHP Version:            <?php echo PHP_VERSION . "\n"; ?>
MySQL Version:          <?php echo mysqli_get_server_info() . "\n"; ?>
Web Server:             <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

WordPress URL:          <?php echo get_bloginfo('wpurl') . "\n"; ?>
Home URL:               <?php echo get_bloginfo('url') . "\n"; ?>

PHP cURL Support:       <?php echo (function_exists('curl_init')) ? 'Yes' . "\n" : 'No' . "\n"; ?>
PHP GD Support:         <?php echo (function_exists('gd_info')) ? 'Yes' . "\n" : 'No' . "\n"; ?>
PHP Memory Limit:       <?php echo ini_get('memory_limit') . "\n"; ?>
PHP Post Max Size:      <?php echo ini_get('post_max_size') . "\n"; ?>
PHP Upload Max Size:    <?php echo ini_get('upload_max_filesize') . "\n"; ?>

WP_DEBUG:               <?php echo defined('WP_DEBUG') ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>
Multi-Site Active:      <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

Operating System:       <?php echo $browser['platform'] . "\n"; ?>
Browser:                <?php echo $browser['name'] . ' ' . $browser['version'] . "\n"; ?>
User Agent:             <?php echo $browser['user_agent'] . "\n"; ?>

Active Theme:
- <?php echo $theme['Name'] ?> <?php echo $theme['Version'] . "\n"; ?>
  <?php echo $theme['URI'] . "\n"; ?>

Active Plugins:
<?php
$plugins = get_plugins();
$active_plugins = get_option('active_plugins', array());

foreach ($plugins as $plugin_path => $plugin) {

	// Only show active plugins
	if (in_array($plugin_path, $active_plugins)) {
		echo '- ' . $plugin['Name'] . ' ' . $plugin['Version'] . "\n";

		if (isset($plugin['PluginURI'])) {
			echo '  ' . $plugin['PluginURI'] . "\n";
		}

		echo "\n";
	}
}
?>
----- End System Info -----
	</textarea>
	</div> <!-- inside -->
</div> <!-- tab -->

<div class='mb_tab option-container'>
	<div class="title">
		<span class="dashicons dashicons-admin-settings"></span>
		<span class="title"><?php _e("Templates","maxinbound"); ?></span>
	</div>

	<div class='inside'>


	</div><!-- inside -->
</div>

<div class='mb_tab option-container'>
	<div class='title'>
		<span class="dashicons dashicons-admin-settings"></span>
		<span class="title"><?php _e("Modules","maxinbound"); ?></span>
	</div>
	<div class='inside'>


	</div><!-- inside -->
</div>
