<?php
$plugin['name'] = 'arc_meta';
$plugin['version'] = '1.0';
$plugin['author'] = 'Andy Carter';
$plugin['author_uri'] = 'http://andy-carter.com/';
$plugin['description'] = 'Title and Meta tags';
$plugin['order'] = '5';
$plugin['type'] = '5';
$plugin['flags'] = '3';

if (!defined('txpinterface'))
	@include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
global $prefs, $txpcfg;

register_callback('_arc_meta_install','plugin_lifecycle.arc_meta', 'installed');

function arc_meta_title($atts)
{
	global $parentid, $thisarticle, $id, $q, $c, $context, $s, $sitename, $prefs;

	extract(lAtts(array(
		'separator' => ' | ',
		'title' => null
	), $atts));
	
	if ($title===null) {

		$meta = _arc_meta();

		$tokens = array(
			'_%n_' => txpspecialchars($sitename),
			'_%t_' => txpspecialchars($prefs['site_slogan'])
		);

		if (!empty($parent_id) || !empty($thisarticle['title'])) {
			$tokens['_%a_'] = empty($meta['title']) ? escape_title($thisarticle['title']) : $meta['title'];
			$pattern = !empty($parent_id) ? gTxt('comments_on').' %a | %n' : '%a | %n';
		} elseif ($q) {
			$tokens['_%q_'] = txpspecialchars($q);
			$pattern = gTxt('search_results') . ': ' . '%q | %n';
		} elseif ($c) {
			$tokens['_%c_'] = txpspecialchars(fetch_category_title($c, $context));
			$pattern = '%c | %n';
		} elseif ($s and $s != 'default') {
			$tokens['_%s_'] = txpspecialchars(fetch_section_title($s));
			$pattern = '%s | %n';
		} else {
			$pattern = '%n | %t';
		}

		$title = preg_replace(array_keys($tokens), array_values($tokens), $pattern);

	}

	$html = tag($title, 'title');
		
	return $html;
}

function arc_meta_canonical($atts)
{
	global $thisarticle, $prefs, $s;

	if (!empty($thisarticle['thisid'])) {
		$url = permlinkurl($thisarticle);
	} elseif (!empty($s) and $s != 'default') {
		$url = pagelinkurl(array('s' => $s));
	} else {
		$url = hu;
	}

	$html = "<link rel='canonical' href='$url' />";

	return $html;

}

function arc_meta_description($atts)
{
	$meta = _arc_meta();

	$description = urlencode($meta['description']);

	$html = "<meta name='description' content='$description' />";

	return $html;

}

function _arc_meta()
{
	global $thisarticle, $s, $c, $arc_meta;

	if (empty($arc_meta)) {

		if (!empty($thisarticle['thisid'])) {
			$typeId = $thisarticle['thisid'];
			$type = 'article';
		} elseif (!empty($c)) {
			$type = 'category';
		} elseif (!empty($s)) {
			$type = 'section';
		}
		
		$arc_meta = array(
			'id' => null,
			'title' => null,
			'description' => null
		);

		if (!empty($typeId) && !empty($type)) {
			$meta = safe_row('*', 'arc_meta', "type_id=$typeId AND type='$type'");
			return array_merge($arc_meta, $meta);
		}

	}

	return $arc_meta;

}

if (@txpinterface == 'admin') 
{
	register_callback('_arc_meta_article_meta', 'article_ui', 'keywords');
	register_callback('_arc_meta_article_meta_save', 'ping');
	register_callback('_arc_meta_article_meta_save', 'article_saved');
	register_callback('_arc_meta_article_meta_save', 'article_posted');
}

function _arc_meta_install()
{
	$sql = "CREATE TABLE IF NOT EXISTS " . PFX . "arc_meta (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`type` varchar(8) NOT NULL,
		`type_id` int(11) NOT NULL,
		`title` varchar(65) DEFAULT NULL,
		`override_title` tinyint(1) DEFAULT NULL,
		`description` varchar(150) DEFAULT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	if (!safe_query($sql)) {
		return 'Error - unable to create arc_meta table';
	}

	return;
}

function _arc_meta_article_meta($event, $step, $data, $rs)
{
	$meta = array(
		'id' => null,
		'title' => null,
		'description' => null
	);
	if ($rs['ID']) {
		$articleMeta = safe_row('*', 'arc_meta', "type_id={$rs['ID']} AND type='article'");
		$meta = array_merge($meta, $articleMeta);
	}

	$form = hInput('arc_meta_id', $meta['id']);
	$form .= "<p class='arc_meta_title'>";
	$form .= tag('Title', 'label', ' for="arc_meta_title"') . '<br />';
	$form .= fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title');
	$form .= "</p>";
	$form .= "<p class='arc_meta_description'>";
	$form .= tag('Description', 'label', ' for="arc_meta_description"') . '<br />';
	$form .= text_area('arc_meta_description', null, null, $meta['description'], 'arc_meta_description');
	$form .= "</p>";

	return $form.$data;
}

function _arc_meta_article_meta_save($event, $step)
{
	$articleId = empty($GLOBALS['ID']) ? gps('ID') : $GLOBALS['ID'];

	$metaId = gps('arc_meta_id');
	$metaTitle = gps('arc_meta_title');
	$metaDescription = gps('arc_meta_description');

	$values = array(
		'type' => 'article',
		'type_id' => $articleId,
		'title' => doSlash($metaTitle),
		'description' => doSlash($metaDescription)
	);

	foreach ($values as $key => $value) {
		$sql[] = "$key = '$value'";
	}
	$sql = implode(', ', $sql);

	if ($metaId) {

		// Update existing meta data.
		safe_update('arc_meta', $sql, "id=$metaId");

	} elseif (!empty($metaTitle) || !empty($metaDescription)) { 

		// Create new meta data only if there is data to be saved.
		safe_insert('arc_meta', $sql);

	}
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
