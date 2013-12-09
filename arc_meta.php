<?php
$plugin['name'] = 'arc_meta';
$plugin['version'] = '1.0';
$plugin['author'] = 'Andy Carter';
$plugin['author_uri'] = 'http://andy-carter.com/';
$plugin['description'] = 'Title and Meta tags';
$plugin['order'] = '5';
$plugin['type'] = '0';

if (!defined('txpinterface'))
	@include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
global $prefs, $txpcfg;

function arc_meta_title($atts)
{
	global $parentid, $thisarticle, $id, $q, $c, $context, $s, $sitename, $prefs;

	extract(lAtts(array(
		'separator' => ' | ',
		'title' => null
	), $atts));
	
	if ($title===null) {

		if ($parent_id) {
			$title = gTxt('comments_on').' '.escape_title(safe_field('Title', 'textpattern', "ID = $parent_id")) . $separator . txpspecialchars($sitename);
		} elseif ($thisarticle['title']) {
			$title = escape_title($thisarticle['title']) . $separator . txpspecialchars($sitename);
		} elseif ($q) {
			$out .= gTxt('search_results') . ': ' . txpspecialchars($q) . $separator . txpspecialchars($sitename);
		} elseif ($c) {
			$title = txpspecialchars(fetch_category_title($c, $context)) . $separator . txpspecialchars($sitename);
		} elseif ($s and $s != 'default') {
			$title = txpspecialchars(fetch_section_title($s)) . $separator . txpspecialchars($sitename);
		} else {
			$title = txpspecialchars($sitename) . $separator . txpspecialchars($prefs['site_slogan']);
		}

	}

	$html = tag($title, 'title');
		
	return $html;
}

function arc_meta_canonical($atts)
{
	global $thisarticle, $prefs, $s;

	if ($thisarticle['thisid']) {
		$url = permlinkurl($thisarticle);
	} elseif ($s and $s != 'default') {
		$url = pagelinkurl(array('s' => $s));
	} else {
		$url = hu;
	}

	$html = "<link rel='canonical' href='$url' />";

	return $html;

}


# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---

h1. arc_meta

h2. Usage

All of the following tags should be used within your templates' @<head>@ tags.

h3. arc_meta_title

Outputs a SEO friendly @<title>@ tag.

bc. <txp:arc_meta_title />

h3. arc_meta_canonical

Specify the page's canonical URL. See Google's "Specify Your Canonical":http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html for an explanation.

bc. <txp:arc_meta_canonical />

# --- END PLUGIN HELP ---
-->
<?php
}
?>
