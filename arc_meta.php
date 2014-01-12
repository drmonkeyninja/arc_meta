<?php
$plugin['name'] = 'arc_meta';
$plugin['version'] = '1.1.1';
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
register_callback('_arc_meta_uninstall','plugin_lifecycle.arc_meta', 'deleted');
register_callback('arc_meta_options','plugin_prefs.arc_meta');
add_privs('plugin_prefs.arc_meta', '1,2');

function arc_meta_title($atts)
{
	global $parentid, $thisarticle, $id, $q, $c, $context, $s, $sitename, $prefs;

	extract(lAtts(array(
		'separator' => ' | ',
		'title' => null,
		'article_title' => $prefs['arc_meta_article_title'],
		'comment_title' => $prefs['arc_meta_comment_title'],
		'search_title' => $prefs['arc_meta_search_title'],
		'category_title' => $prefs['arc_meta_category_title'],
		'section_title' => $prefs['arc_meta_section_title'],
		'homepage_title' => $prefs['arc_meta_homepage_title']
	), $atts));
	
	if ($title===null) {

		$meta = _arc_meta();

		$tokens = array(
			'_%n_' => txpspecialchars($sitename),
			'_%t_' => txpspecialchars($prefs['site_slogan'])
		);

		if (!empty($parent_id) || !empty($thisarticle['title'])) {
			$tokens['_%a_'] = empty($meta['title']) ? escape_title($thisarticle['title']) : $meta['title'];
			$tokens['_%s_'] = txpspecialchars(fetch_section_title($thisarticle['section']));
			$pattern = !empty($parent_id) ? $comment_title : $article_title;
		} elseif ($q) {
			$tokens['_%q_'] = txpspecialchars($q);
			$pattern = $search_title;
		} elseif ($c) {
			$tokens['_%c_'] = empty($meta['title']) ? txpspecialchars(fetch_category_title($c, $context)) : $meta['title'];
			$pattern = $category_title;
		} elseif ($s and $s != 'default') {
			$tokens['_%s_'] = empty($meta['title']) ? txpspecialchars(fetch_section_title($s)) : $meta['title'];
			$pattern = $section_title;
		} else {
			$pattern = !empty($meta['title']) ? $meta['title'] : $homepage_title;
		}

		$title = preg_replace(array_keys($tokens), array_values($tokens), $pattern);

	}

	$html = tag($title, 'title');
		
	return $html;
}

function arc_meta_canonical($atts)
{
	extract(lAtts(array(
		'url' => null
	), $atts));

	$url = $url !==null ? $url : _arc_meta_url();

	$html = "<link rel='canonical' href='$url' />";

	return $html;

}

function arc_meta_description($atts)
{
	extract(lAtts(array(
		'description' => null
	), $atts));

	if ($description===null) {
		$meta = _arc_meta();
		$description = !empty($meta['description']) ? txpspecialchars($meta['description'], ENT_QUOTES) : null;
	}

	if ($description) {
		return "<meta name='description' content='$description' />";		
	}

	return '';

}

function arc_meta_open_graph($atts)
{
	global $thisarticle, $prefs, $s, $c;

	extract(lAtts(array(
		'site_name' => $prefs['sitename'],
		'title' => null,
		'description' => null,
		'url' => null,
		'image' => null
	), $atts));

	$title = $title===null ? _arc_meta_title() : $title;

	$meta = _arc_meta();

	if ($description===null) {

		$description = !empty($meta['description']) ? txpspecialchars($meta['description']) : null;
	
	}

	$url = $url===null ? _arc_meta_url() : $url;

	if ($image===null && $thisarticle['article_image']) {

		$image = _arc_meta_image();

	}

	$html = '';
	if ($site_name) {
		$html .= "<meta property='og:site_name' content='$site_name' />";
	}
	if ($title)	{
		$html .= "<meta property='og:title' content='$title' />";
	}
	if ($description) {
		$html .= "<meta property='og:description' content='$description' />";		
	}
	if ($url) {
		$html .= "<meta property='og:url' content='$url' />";
	}
	if ($image) {
		$html .= "<meta property='og:image' content='$image' />";
	}

	return $html;
}

function arc_meta_twitter_card($atts)
{
	global $thisarticle, $prefs, $s, $c;

	extract(lAtts(array(
		'card' => 'summary',
		'title' => null,
		'description' => null,
		'url' => null,
		'image' => null
	), $atts));

	$title = $title===null ? _arc_meta_title() : $title;

	$meta = _arc_meta();

	if ($description===null) {

		$description = !empty($meta['description']) ? txpspecialchars($meta['description']) : null;
	
	}

	$url = $url===null ? _arc_meta_url() : $url;

	if ($image===null && $thisarticle['article_image']) {

		$image = _arc_meta_image();

	}

	$html = "<meta name='twitter:card' content='$card' />";

	if ($title) {
		$html .= "<meta name='twitter:title' content='$title' />";		
	}
	if ($description) {
		$html .= "<meta name='twitter:description' content='$description' />";		
	}
	if ($url) {
		$html .= "<meta name='twitter:url' content='$url' />";
	}
	if ($image) {
		$html .= "<meta name='twitter:image' content='$image' />";
	}

	return $html;

}

function _arc_meta_title()
{
	global $thisarticle, $prefs, $s, $c;

	if (!empty($thisarticle['thisid'])) {
		$title = txpspecialchars($thisarticle['title']);
	} elseif (!empty($s) and $s != 'default') {
		$title = txpspecialchars(fetch_section_title($s));
	} elseif (!empty($c)) {
		$title = txpspecialchars(fetch_category_title($c));
	} else {
		$title = txpspecialchars($prefs['sitename']);
	}

	return $title;

}

function _arc_meta_url()
{
	global $thisarticle, $s, $c;

	if (!empty($thisarticle['thisid'])) {
		$url = permlinkurl($thisarticle);
	} elseif (!empty($s) and $s != 'default') {
		$url = pagelinkurl(array('s' => $s));
	} elseif (!empty($c)) {
		$url = pagelinkurl(array('c' => $c));
	} else {
		$url = hu;
	}
	return $url;
}

function _arc_meta_image()
{
	global $thisarticle;

	$image = $thisarticle['article_image'];

	if (intval($image)) {

		if ($rs = safe_row('*', 'txp_image', 'id = ' . intval($image))) {
			$image = imagesrcurl($rs['id'], $rs['ext']);
		} else {
			$image = null;
		}

	}

	return $image;
}

function _arc_meta($type = null, $typeId = null)
{
	global $thisarticle, $s, $c, $arc_meta;

	if (empty($arc_meta)) {

		if (empty($type) || empty($typeId)) {

			if (!empty($thisarticle['thisid'])) {
				$typeId = $thisarticle['thisid'];
				$type = 'article';
			} elseif (!empty($c)) {
				$typeId = $c;
				$type = 'category';
			} elseif (!empty($s)) {
				$typeId = $s;
				$type = 'section';
			}

		}
		
		$arc_meta = array(
			'id' => null,
			'title' => null,
			'description' => null
		);

		if (!empty($typeId) && !empty($type)) {

			$meta = safe_row('*', 'arc_meta', "type_id='$typeId' AND type='$type'");
			$arc_meta = array_merge($arc_meta, $meta);
			return $arc_meta;
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

	register_callback('_arc_meta_section_meta', 'section_ui', 'extend_detail_form');
	register_callback('_arc_meta_section_meta_save', 'section', 'section_save');

	register_callback('_arc_meta_category_meta', 'category_ui', 'extend_detail_form');
	register_callback('_arc_meta_category_meta_save', 'category', 'cat_article_save');
}

function _arc_meta_install()
{
	$sql = "CREATE TABLE IF NOT EXISTS " . PFX . "arc_meta (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`type` varchar(8) NOT NULL,
		`type_id` varchar(128) NOT NULL,
		`title` varchar(65) DEFAULT NULL,
		`override_title` tinyint(1) DEFAULT NULL,
		`description` varchar(150) DEFAULT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

	if (!safe_query($sql)) {
		return 'Error - unable to create arc_meta table';
	}

	// Setup the plugin preferences.
	_arc_meta_install_prefs();

	return;
}
/**
 * Setup the plugin preferences if they have not yet been set.
 */
function _arc_meta_install_prefs()
{
	if (!isset($prefs['arc_meta_article_title'])) {
		set_pref('arc_meta_article_title', '%a | %n', 'arc_meta', 1, 'text_input');
	}
	if (!isset($prefs['arc_meta_comment_title'])) {
		set_pref('arc_meta_comment_title', gTxt('comments_on').' %a | %n', 'arc_meta', 1, 'text_input');
	}
	if (!isset($prefs['arc_meta_search_title'])) {
		set_pref('arc_meta_search_title', gTxt('search_results') . ': ' . '%q | %n', 'arc_meta', 1, 'text_input');
	}
	if (!isset($prefs['arc_meta_category_title'])) {
		set_pref('arc_meta_category_title', '%c | %n', 'arc_meta', 1, 'text_input');
	}
	if (!isset($prefs['arc_meta_section_title'])) {
		set_pref('arc_meta_section_title', '%s | %n', 'arc_meta', 1, 'text_input');
	}
	if (!isset($prefs['arc_meta_homepage_title'])) {
		set_pref('arc_meta_homepage_title', '%n | %t', 'arc_meta', 1, 'text_input');
	}
	return;
}

function _arc_meta_uninstall()
{
	$sql = "DROP TABLE IF EXISTS ".PFX."arc_meta;";
	if (!safe_query($sql)) {
		return 'Error - unable to delete arc_meta table';
	}

	$sql = "DELETE FROM  ".PFX."txp_prefs WHERE event='arc_meta';";
	if (!safe_query($sql)) {
		return 'Error - unable to delete arc_meta preferences';
	}
	return;
}

function arc_meta_options($event, $step)
{
	global $prefs;

	if ($step == 'prefs_save') {
		pagetop('arc_meta', 'Preferences saved');
	} else {
		pagetop('arc_meta');
	}

	// Define the form fields.
	$fields = array(
		'arc_meta_article_title' => 'Article Page Titles',
		'arc_meta_comment_title' => 'Comment Page Titles',
		'arc_meta_search_title' => 'Search Page Titles',
		'arc_meta_category_title' => 'Category Titles',		
		'arc_meta_section_title' => 'Section Titles'
	);

	if ($step == 'prefs_save') {

		foreach ($fields as $key => $label) {
			$prefs[$key] = trim(gps($key));
			set_pref($key, $prefs[$key]);
		}

	}

	$form = '';

	foreach ($fields as $key => $label) {
		$form .= "<p class='$key'><span class='edit-label'><label for='$key'>$label</label></span>";
		$form .= "<span class='edit-value'>" . fInput('text', $key, $prefs[$key], '', '', '', '', '', $key) . "</span>";
		$form .= '</p>';
	}

	$form .= sInput('prefs_save').n.eInput('plugin_prefs.arc_meta');

	$form .= '<p>'.fInput('submit', 'Submit', gTxt('save_button'), 'publish').'</p>';

	$html = "<h1 class='txp-heading'>arc_meta</h1>";
	$html .= form("<div class='plugin-column'>" . $form . "</div>", " class='edit-form'");

	echo $html;
}

function _arc_meta_article_meta($event, $step, $data, $rs)
{
	// Get the article meta data.
	$articleId = !empty($rs['ID']) ? $rs['ID'] : null;
	$meta = _arc_meta('article', $articleId);

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

function _arc_meta_section_meta($event, $step, $data, $rs)
{
	// Get the section meta data.
	$sectionName = !empty($rs['name']) ? $rs['name'] : null;
	$meta = _arc_meta('section', $sectionName);

	$form = hInput('arc_meta_id', $meta['id']);
	$form .= "<p class='edit-section-arc_meta_title'>";
	$form .= "<span class='edit-label'> " . tag('Meta title', 'label', ' for="arc_meta_title"') . '</span>';
	$form .= "<span class='edit-value'> " . fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title') . '</span>';
	$form .= '</p>';
	$form .= "<p class='edit-section-arc_meta_description'>";
	$form .= "<span class='edit-label'> " . tag('Meta description', 'label', ' for="arc_meta_description"') . '</span>';
	$form .= "<span class='edit-value'> " . text_area('arc_meta_description', null, null, $meta['description'], 'arc_meta_description') . '</span>';
	$form .= '</p>';

	return $data.$form;
}

function _arc_meta_category_meta($event, $step, $data, $rs)
{
	// Make sure that this is an article category (we don't support other
	// category types).
	if ($rs['type']!='article') {
		return $data;
	}

	// Get the existing meta data for this category.
	$meta = _arc_meta('category', $rs['name']);

	$form = hInput('arc_meta_id', $meta['id']);
	$form .= "<p class='edit-category-arc_meta_title'>";
	$form .= "<span class='edit-label'> " . tag('Meta title', 'label', ' for="arc_meta_title"') . '</span>';
	$form .= "<span class='edit-value'> " . fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title') . '</span>';
	$form .= '</p>';
	$form .= "<p class='edit-category-arc_meta_description'>";
	$form .= "<span class='edit-label'> " . tag('Meta description', 'label', ' for="arc_meta_description"') . '</span>';
	$form .= "<span class='edit-value'> " . text_area('arc_meta_description', null, null, $meta['description'], 'arc_meta_description') . '</span>';
	$form .= '</p>';

	return $data.$form;
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

function _arc_meta_section_meta_save($event, $step)
{
	$sectionName = gps('name');

	$metaId = gps('arc_meta_id');
	$metaTitle = gps('arc_meta_title');
	$metaDescription = gps('arc_meta_description');

	$values = array(
		'type' => 'section',
		'type_id' => $sectionName,
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

function _arc_meta_category_meta_save($event, $step)
{
	$categoryName = gps('name');

	$metaId = gps('arc_meta_id');
	$metaTitle = gps('arc_meta_title');
	$metaDescription = gps('arc_meta_description');

	$values = array(
		'type' => 'category',
		'type_id' => $categoryName,
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

All of the following tags should be used within your templates' @<head>@ tag.

h3. arc_meta_title

Outputs an SEO friendly @<title>@ tag.

bc. <txp:arc_meta_title />

h4. Attributes

The @arc_meta_title@ tag attributes override the defaults. To set the default patterns used for the @<title>@ tag goto the plugin's options page.

* title -- use this to override the title tag's content
* article_title -- sets the pattern for article page titles
* comment_title -- sets the pattern for comment page titles
* search_title -- sets the pattern for search results page titles
* category_title -- sets the pattern for category page titles
* section_title -- sets the pattern for section page titles
* homepage_title -- sets the pattern for the homepage title

h4. Title Tokens

To set a pattern for a page title you can use the following tokens:-

* @%a@ -- article title, can only be used on article and comment pages
* @%s@ -- section name, can be used on article and section pages
* @%c@ -- category name, can be used on category pages (not including filtered section pages)
* @%n@ -- site's name, can be used on all pages
* @%t@ -- site's slogan, can be used on all pages
* @%q@ -- search query, can be used on search results page

For example, you could define the pattern for an article page as:-

bc. %a | %n (%t)

This would output something like:-

bc. <title>Welcome to Your Site! | My site (My pithy slogan)</title>

h4. Examples

h5. Example Using the @section_title@ Attribute

bc. <txp:arc_meta_title section_title='%n / %s' />

Outputs on an 'Articles' section page:-

bc. <title>My site / Articles</title>

h5. Example Using the @title@ Attribute

bc. <txp:arc_meta_title title='Hello World' />

Outputs:-

bc. <title>Hello World</title>

h3. arc_meta_description

Outputs a meta description tag when a description has been set.

bc. <txp:arc_meta_description />

h4. Attributes

* description -- overrides the description set using arc_meta's description field on the article Write page or section/category edit page

h3. arc_meta_canonical

Specify the page's canonical URL. See Google's "Specify Your Canonical":http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html for an explanation.

bc. <txp:arc_meta_canonical />

h4. Attributes

* url -- overrides the default URL

h3. arc_meta_open_graph

Outputs meta tags for using Facebook Open Graph.

bc. <txp:arc_meta_open_graph />

Just including the above tag in your templates' @<head>@ tag will output tags for the following:-

* og:site_name -- your site name from your preferences
* og:title -- the article's title, section name or category name
* og:description -- your page's meta description
* og:url -- your page's canonical URL
* og:image -- the article's image

You can override the default values of any of these by passing a value to one of the tag's attributes defined below. To disable one of the tags just pass an empty attribute value (__e.g.__ @title=''@).

h4. Attributes

* site_name
* title
* description
* url
* image

h3. arc_meta_twitter_card

Outputs meta tags for using Twitter Cards.

bc. <txp:arc_meta_open_graph />

Just including the above tag in your templates' @<head>@ tag will output tags for the following:-

* twitter:card -- 'summary' by default, can also be set to 'photo' for image content or 'player' for videos
* twitter:title -- the article's title, section name or category name
* twitter:description -- your page's meta description
* twitter:url -- your page's canonical URL
* twitter:image -- the article's image

You can override the default values of any of these by passing a value to one of the tag's attributes defined below. To disable one of the tags just pass an empty attribute value (__e.g.__ @title=''@).

h4. Attributes

* card
* title
* description
* url
* image

h2. Admin

h3. Article Write Page

The plugin will add _title_ and _description_ fields to the _Meta_ options. Use these to set your article's meta data.

If you set a title here it will be used in place of the article's title when replacing the @%a@ token for the @arc_meta_title@ tag.

The description will be used for the @arc_meta_description@ tag and for the descriptions used by the @arc_meta_open_graph@ and @arc_meta_twitter_card@ tags.

h3. Section and Category Pages

The plugin will add _meta title_ and _meta description_ fields to the section and category edit forms.

If you set a title here it will be used in place of the section's/category's name when replacing the ==<code>%s</code>/<code>%c</code>== token for the @arc_meta_title@ tag. When editing the homepage section the _meta title_ will replace any pattern defined for the page title.

The description will be used for the @arc_meta_description@ tag and for the descriptions used by the @arc_meta_open_graph@ and @arc_meta_twitter_card@ tags.

h3. Plugin Options

From the plugin's options page you can set the default patterns used for the arc_meta_title tag. These can all be overridden when the tag is included in your page templates, but it may be easier to set the default patterns if you want to change the ones that come with the plugin when installed.

# --- END PLUGIN HELP ---
-->
<?php
}
?>
