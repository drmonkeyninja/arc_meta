<?php
$plugin['name'] = 'arc_meta';
$plugin['version'] = '1.4.1';
$plugin['author'] = 'Andy Carter';
$plugin['author_uri'] = 'http://andy-carter.com/';
$plugin['description'] = 'Title and Meta tags';
$plugin['order'] = '5';
$plugin['type'] = '5';
$plugin['flags'] = '3';

if (!defined('txpinterface')) {
    @include_once('zem_tpl.php');
}

# --- BEGIN PLUGIN CODE ---
global $prefs, $txpcfg;

register_callback('_arc_meta_install', 'plugin_lifecycle.arc_meta', 'installed');
register_callback('_arc_meta_uninstall', 'plugin_lifecycle.arc_meta', 'deleted');
register_callback('arc_meta_options', 'plugin_prefs.arc_meta');
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
        'section_category_title' => $prefs['arc_meta_section_category_title'],
        'homepage_title' => $prefs['arc_meta_homepage_title']
    ), $atts));

    if ($title === null) {
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
            if ($s and $s != 'default') {
                $tokens['_%s_'] = txpspecialchars(fetch_section_title($s));
                $pattern = $section_category_title;
            } else {
                $pattern = $category_title;
            }
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

    $url = $url !== null ? $url : _arc_meta_url();

    $html = "<link rel=\"canonical\" href=\"$url\" />";

    return $html;
}

function arc_meta_description($atts)
{
    extract(lAtts(array(
        'description' => null,
        'type' => null
    ), $atts));

    if ($description === null) {
        $meta = _arc_meta();
        $description = !empty($meta['description']) ? txpspecialchars($meta['description'], ENT_QUOTES) : _arc_meta_description($type);
    }

    if ($description) {
        return "<meta name=\"description\" content=\"$description\" />";
    }

    return '';
}

function arc_meta_robots($atts)
{
    extract(lAtts(array(
        'robots' => null
    ), $atts));

    if ($robots === null) {
        $meta = _arc_meta();
        $robots = !empty($meta['robots']) ? $meta['robots'] : null;
    }

    $out = '';

    if (get_pref('production_status') != 'live') {
        $out .= "<meta name=\"robots\" content=\"noindex, nofollow\" />";
        $out .= $robots ? "<!-- $robots -->" : null;
    } elseif ($robots) {
        $out .= "<meta name=\"robots\" content=\"$robots\" />";
    }

    return $out;
}

function arc_meta_keywords($atts)
{
    global $thisarticle;

    extract(lAtts(array(
        'keywords' => null
    ), $atts));

    $keywords = $keywords===null && isset($thisarticle['keywords']) ? $thisarticle['keywords'] : null;

    if ($keywords) {
        $keywords = txpspecialchars($keywords);
        return "<meta name=\"keywords\" content=\"$keywords\" />";
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
    $description = $description===null ? _arc_meta_description() : $description;
    $url = $url===null ? _arc_meta_url() : $url;
    $image = $image===null ? _arc_meta_image() : $image;

    $html = '';
    if ($site_name) {
        $html .= "<meta property=\"og:site_name\" content=\"$site_name\" />";
    }
    if ($title) {
        $html .= "<meta property=\"og:title\" content=\"$title\" />";
    }
    if ($description) {
        $html .= "<meta property=\"og:description\" content=\"$description\" />";
    }
    if ($url) {
        $html .= "<meta property=\"og:url\" content=\"$url\" />";
    }
    if ($image) {
        $html .= "<meta property=\"og:image\" content=\"$image\" />";
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
    $description = $description===null ? _arc_meta_description() : $description;
    $url = $url===null ? _arc_meta_url() : $url;
    $image = $image===null ? _arc_meta_image() : $image;

    $html = "<meta name=\"twitter:card\" content=\"$card\" />";
    $html .= "<meta name=\"twitter:title\" content=\"$title\" />";
    $html .= "<meta name=\"twitter:description\" content=\"$description\" />";

    if ($url) {
        $html .= "<meta name=\"twitter:url\" content=\"$url\" />";
    }
    if ($image) {
        $html .= "<meta name=\"twitter:image:src\" content=\"$image\" />";
    }

    return $html;

}

function arc_meta_organization($atts)
{
    global $prefs;

    extract(lAtts(array(
        'name' => $prefs['sitename'],
        'logo' => null,
        'facebook' => null,
        'gplus' => null,
        'twitter' => null
    ), $atts));

    if (empty($logo)) {
        trigger_error('arc_meta_organization missing logo attribute', E_USER_WARNING);
    }

    $data = array(
        '@context' => 'http://schema.org',
        '@type' => 'Organization',
        'name' => $name,
        'logo' => $logo,
        'url' => hu
    );

    $sameAs = array();
    if (!empty($facebook)) {
        $sameAs[] = $facebook;
    }
    if (!empty($gplus)) {
        $sameAs[] = $gplus;
    }
    if (!empty($twitter)) {
        $sameAs[] = $twitter;
    }

    if (!empty($sameAs)) {
        $data['sameAs'] = $sameAs;
    }

    return '<script type="application/ld+json">' . str_replace('\\/', '/', json_encode($data)) . '</script>';

}

function arc_meta_person($atts)
{
    global $prefs;

    extract(lAtts(array(
        'name' => $prefs['sitename'],
        'logo' => null,
        'facebook' => null,
        'gplus' => null,
        'twitter' => null
    ), $atts));

    $data = array(
        '@context' => 'http://schema.org',
        '@type' => 'Person',
        'name' => $name,
        'url' => hu
    );

    $sameAs = array();
    if (!empty($facebook)) {
        $sameAs[] = $facebook;
    }
    if (!empty($gplus)) {
        $sameAs[] = $gplus;
    }
    if (!empty($twitter)) {
        $sameAs[] = $twitter;
    }

    if (!empty($sameAs)) {
        $data['sameAs'] = $sameAs;
    }

    return '<script type="application/ld+json">' . str_replace('\\/', '/', json_encode($data)) . '</script>';

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

    } else {
        $meta = _arc_meta();
        if (!empty($meta['image']) && $rs = safe_row('*', 'txp_image', 'id = ' . intval($meta['image']))) {
            $image = imagesrcurl($rs['id'], $rs['ext']);
        } else {
            $image = null;
        }

    }

    return $image;
}

/**
 * Fetch meta description from the given (or automatic) context.
 *
 * Category context may be refined by specifying the content type as well
 * after a dot. e.g. category.image to check image context category.
 *
 * @param string $type Flavour of meta content to fetch (section, category, article)
 */
function _arc_meta_description($type = null)
{
    global $thisarticle;

    $metaDescription = getMetaDescription($type);

    if (!empty($metaDescription)) {
        $description = txpspecialchars($metaDescription);
    } elseif (!empty($thisarticle['excerpt'])) {
        $description = strip_tags($thisarticle['excerpt']);
        $description = substr($description, 0, 200);
        $description = txpspecialchars($description);
    } elseif (!empty($thisarticle['body'])) {
        $description = strip_tags($thisarticle['body']);
        $description = substr($description, 0, 200);
        $description = txpspecialchars($description);
    } else {
        $description = null;
    }

    return $description;
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
            'description' => null,
            'image' => null,
            'robots' => null
        );

        if (!empty($typeId) && !empty($type)) {
            $meta = safe_row('*', 'arc_meta', "type_id='$typeId' AND type='$type'");
            $arc_meta = array_merge($arc_meta, $meta);
            return $arc_meta;
        }

    }

    return $arc_meta;
}

if (@txpinterface == 'admin') {
    register_callback('_arc_meta_article_meta', 'article_ui', 'keywords');
    register_callback('_arc_meta_article_meta_save', 'ping');
    register_callback('_arc_meta_article_meta_save', 'article_saved');
    register_callback('_arc_meta_article_meta_save', 'article_posted');

    register_callback('_arc_meta_section_meta', 'section_ui', 'extend_detail_form');
    register_callback('_arc_meta_section_meta_save', 'section', 'section_save');

    register_callback('_arc_meta_category_meta', 'category_ui', 'extend_detail_form');
    register_callback('_arc_meta_category_meta_save', 'category', 'cat_article_save');

    if (!empty($prefs['arc_meta_section_tab'])) {
        add_privs('arc_meta_section_tab', '1,2,3,4');
        register_tab($prefs['arc_meta_section_tab'], 'arc_meta_section_tab', 'Sections Meta Data');
        register_callback('arc_meta_section_tab', 'arc_meta_section_tab');
    }
}

function _arc_meta_install()
{
    $sql = "CREATE TABLE IF NOT EXISTS " . PFX . "arc_meta (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `type` varchar(8) NOT NULL,
        `type_id` varchar(128) NOT NULL,
        `title` varchar(250) DEFAULT NULL,
        `override_title` tinyint(1) DEFAULT NULL,
        `robots` varchar(45) DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

    if (!safe_query($sql)) {
        return 'Error - unable to create arc_meta table';
    }

    $dbTable = getThings('DESCRIBE ' . safe_pfx('arc_meta'));

    if (!in_array('robots', $dbTable)) {
        safe_alter('arc_meta', 'ADD robots VARCHAR(45)');
    }

    if (!in_array('image', $dbTable)) {
        safe_alter('arc_meta', 'ADD image INT(11) DEFAULT NULL');
        // Increased size of title and description columns.
        safe_alter('arc_meta', 'CHANGE title title VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
    }

    // Upgrade plugin to 2.x.
    if (in_array('description', $dbTable)) {
        // Copy meta description data to main TXP tables.
        safe_query('UPDATE ' . safe_pfx(textpattern) . ', ' . safe_pfx(arc_meta) . ' SET textpattern.description = arc_meta.description WHERE textpattern.ID = arc_meta.type_id AND arc_meta.type = \'article\' AND arc_meta.description IS NOT NULL AND textpattern.description = \'\'');
        safe_query('UPDATE ' . safe_pfx(txp_category) . ', ' . safe_pfx(arc_meta) . ' SET txp_category.description = arc_meta.description WHERE txp_category.name = arc_meta.type_id AND arc_meta.type = \'category\' AND arc_meta.description IS NOT NULL AND txp_category.description = \'\'');
        safe_query('UPDATE ' . safe_pfx(txp_section) . ', ' . safe_pfx(arc_meta) . ' SET txp_section.description = arc_meta.description WHERE txp_section.name = arc_meta.type_id AND arc_meta.type = \'section\' AND arc_meta.description IS NOT NULL AND txp_section.description = \'\'');
        // Drop old meta description column.
        safe_alter('arc_meta', 'DROP COLUMN description');
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
    if (!isset($prefs['arc_meta_section_category_title'])) {
        set_pref('arc_meta_section_category_title', '%c - %s | %n', 'arc_meta', 1, 'text_input');
    }
    if (!isset($prefs['arc_meta_homepage_title'])) {
        set_pref('arc_meta_homepage_title', '%n | %t', 'arc_meta', 1, 'text_input');
    }
    if (!isset($prefs['arc_meta_section_tab'])) {
        set_pref('arc_meta_section_tab', 'content', 'arc_meta', 1, 'text_input');
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

function arc_meta_section_tab($event, $step)
{
    switch ($step) {
        case 'edit':
            arc_meta_section_edit();
            break;

        case 'save':
            _arc_meta_section_meta_save($event, $step);
            // Fall through to section list.

        default:
            arc_meta_section_list();
            break;
    }
}

function arc_meta_section_list()
{
    global $event;

    pagetop('Section Meta Data');

    $html = '<h1 class="txp-heading">Section Meta Data</h1>';

    $rs = safe_query(
        'SELECT sections.*, arc_meta.title AS meta_title FROM ' . safe_pfx('txp_section') . ' sections LEFT JOIN ' . safe_pfx('arc_meta') . ' arc_meta ON arc_meta.type = "section" AND arc_meta.type_id = sections.name WHERE 1=1 ORDER BY CASE WHEN sections.name = "default" THEN 1 ELSE 2 END, sections.name ASC'
    );

    if ($rs) {
        $html .= n . '<div id="' . $event . '_container" class="txp-container">';

        $html .= n . '<div class="txp-listtables">' . n
                . n . startTable('', '', 'txp-list')
                . n . '<thead>'
                . n . tr(hCell('Name') . hCell('Title') . hCell('Meta Title') . hCell('Manage'))
            . n . '</thead>';

        while ($row = nextRow($rs)) {
            $editLink = href(
                gTxt('edit'),
                '?event=arc_meta_section_tab&amp;step=edit&amp;name=' . $row['name']
            );
            $html .= n . tr(
                n . td($row['name']) . td($row['title']) . td($row['meta_title']) . td($editLink)
            );
        }

        $html .= n . endTable();

        $html .= n . '</div>';

    }

    echo $html;
}

function arc_meta_section_edit()
{
    global $event;

    $name = gps('name');

    $rs = safe_query(
        'SELECT sections.title AS section, sections.description, arc_meta.* FROM ' . safe_pfx('txp_section') . ' sections LEFT JOIN ' . safe_pfx('arc_meta') . ' arc_meta ON arc_meta.type = "section" AND arc_meta.type_id = sections.name WHERE sections.name="' . doSlash($name) . '"'
    );

    $meta = nextRow($rs);

    pagetop('Section Meta Data');

    $form = '';

    $form .= '<div class="txp-edit">';
    $form .= hed('Edit Section Meta Data', 2);

    // We include the section title as a disabled field for the user's
    // reference.
    $form .= "<span class='edit-label'> " . tag('Section', 'label', ' for="section"') . '</span>';
    $form .= "<span class='edit-value'> " . fInput('text', 'section', $meta['section'], '', '', '', '32', '', 'section', true) . '</span>';
    $form .= '</p>';

    // Meta data fields
    $form .= hInput('arc_meta_id', $meta['id']);
    $form .= hInput('name', $name);
    $form .= "<span class='edit-label'> " . tag('Meta title', 'label', ' for="arc_meta_title"') . '</span>';
    $form .= "<span class='edit-value'> " . fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-section-arc_meta_description'>";
    $form .= "<span class='edit-label'> " . tag('Meta description', 'label', ' for="arc_meta_description"') . '</span>';
    $form .= "<span class='edit-value'> " . text_area('arc_meta_description', null, null, $meta['description'], 'arc_meta_description') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-section-arc_meta_image'>";
    $form .= "<span class='edit-label'> " . tag('Meta image', 'label', ' for="arc_meta_image"') . '</span>';
    $form .= "<span class='edit-value'> " . fInput('number', 'arc_meta_image', $meta['image'], '', '', '', '32', '', 'arc_meta_image') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-category-arc_meta_robots'>";
    $form .= "<span class='edit-label'> " . tag('Meta robots', 'label', ' for="arc_meta_description"') . '</span>';
    $form .= "<span class='edit-value'> " . selectInput('arc_meta_robots', _arc_meta_robots(), $meta['robots'], 'arc_meta_robots') . '</span>';
    $form .= '</p>';

    $form .= sInput('save') . eInput($event) . fInput('submit', 'save', gTxt('Save'), 'publish');

    $form .= '</div>';

    $html = '<div id="' . $event . '_container" class="txp-container">' . form($form, '', '', 'post', 'edit-form') . '</div>';

    echo $html;
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
        'arc_meta_section_title' => 'Section Titles',
        'arc_meta_section_category_title' => 'Section Category Titles',
        'arc_meta_section_tab' => 'Location of Panel'
    );

    if ($step == 'prefs_save') {
        foreach ($fields as $key => $label) {
            $prefs[$key] = trim(gps($key));
            set_pref($key, $prefs[$key]);
        }

    }

    // Remove the arc_meta_section_tab field as we want to manually add this.
    unset($fields['arc_meta_section_tab']);

    $form = '';

    $form .= hed('Page Title Patterns', 2);

    foreach ($fields as $key => $label) {
        $form .= "<p class='$key'><span class='edit-label'><label for='$key'>$label</label></span>";
        $form .= "<span class='edit-value'>" . fInput('text', $key, $prefs[$key], '', '', '', '', '', $key) . "</span>";
        $form .= '</p>';
    }

    $panels = array(
        'content' => 'Content',
        'extensions' => 'Extensions',
        '' => 'Hidden'
    );

    $panel = $prefs['arc_meta_section_tab'];

    $form .= hed('Sections Meta Data Panel', 2);
    $form .= '<p class="arc_meta_section_tab"><span class="edit-label"><label for="arc_meta_section_tab">Location of Panel</label></span>';
    $form .= '<span class="edit-value">' . selectInput('arc_meta_section_tab', $panels, $panel, '', '', 'arc_meta_section_tab') . "</span>";
    $form .= '</p>';

    $form .= sInput('prefs_save') . n . eInput('plugin_prefs.arc_meta');

    $form .= '<p>' . fInput('submit', 'Submit', gTxt('save_button'), 'publish') . '</p>';

    $html = "<h1 class='txp-heading'>arc_meta</h1>";
    $html .= form("<div class='txp-edit'>" . $form . "</div>", " class='edit-form'");

    echo $html;

    return;
}

function _arc_meta_article_meta($event, $step, $data, $rs)
{
    // Get the article meta data.
    $articleId = !empty($rs['ID']) ? $rs['ID'] : null;
    $meta = _arc_meta('article', $articleId);

    $form = hInput('arc_meta_id', $meta['id']);
    $form .= "<p class='arc_meta_title'>";
    $form .= tag('Meta title', 'label', ' for="arc_meta_title"') . '<br />';
    $form .= fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title');
    $form .= "</p>";
    $form .= "<p class='edit-category-arc_meta_robots'>";
    $form .= tag('Meta robots', 'label', ' for="arc_meta_description"') . '<br />';
    $form .= selectInput('arc_meta_robots', _arc_meta_robots(), $meta['robots'], 'arc_meta_robots');
    $form .= '</p>';

    return $form . $data;
}

function _arc_meta_section_meta($event, $step, $data, $rs)
{
    // Get the section meta data.
    $sectionName = !empty($rs['name']) ? $rs['name'] : null;
    $meta = _arc_meta('section', $sectionName);

    $form = hInput('arc_meta_id', $meta['id']);
    $form .= "<p class='edit-section-arc_meta_title'>";
    $form .= "<span class='txp-label'> " . tag('Meta title', 'label', ' for="arc_meta_title"') . '</span>';
    $form .= "<span class='txp-value'> " . fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-section-arc_meta_image'>";
    $form .= "<span class='txp-label'> " . tag('Meta image', 'label', ' for="arc_meta_image"') . '</span>';
    $form .= "<span class='txp-value'> " . fInput('number', 'arc_meta_image', $meta['image'], '', '', '', '32', '', 'arc_meta_image') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-category-arc_meta_robots'>";
    $form .= "<span class='txp-label'> " . tag('Meta robots', 'label', ' for="arc_meta_description"') . '</span>';
    $form .= "<span class='txp-value'> " . selectInput('arc_meta_robots', _arc_meta_robots(), $meta['robots'], 'arc_meta_robots') . '</span>';
    $form .= '</p>';

    return $data . $form;
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
    $form .= "<span class='txp-label'> " . tag('Meta title', 'label', ' for="arc_meta_title"') . '</span>';
    $form .= "<span class='txp-value'> " . fInput('text', 'arc_meta_title', $meta['title'], '', '', '', '32', '', 'arc_meta_title') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-category-arc_meta_image'>";
    $form .= "<span class='txp-label'> " . tag('Meta image', 'label', ' for="arc_meta_image"') . '</span>';
    $form .= "<span class='txp-value'> " . fInput('number', 'arc_meta_image', $meta['image'], '', '', '', '32', '', 'arc_meta_image') . '</span>';
    $form .= '</p>';
    $form .= "<p class='edit-category-arc_meta_robots'>";
    $form .= "<span class='txp-label'> " . tag('Meta robots', 'label', ' for="arc_meta_description"') . '</span>';
    $form .= "<span class='txp-value'> " . selectInput('arc_meta_robots', _arc_meta_robots(), $meta['robots'], 'arc_meta_robots') . '</span>';
    $form .= '</p>';

    return $data . $form;
}

function _arc_meta_article_meta_save($event, $step)
{
    $articleId = empty($GLOBALS['ID']) ? gps('ID') : $GLOBALS['ID'];

    $metaId = gps('arc_meta_id');
    $metaTitle = gps('arc_meta_title');
    $metaRobots = gps('arc_meta_robots');

    $values = array(
        'type' => 'article',
        'type_id' => $articleId,
        'title' => doSlash($metaTitle),
        'robots' => doSlash($metaRobots)
    );

    foreach ($values as $key => $value) {
        $sql[] = "$key = '$value'";
    }
    $sql = implode(', ', $sql);

    if ($metaId) {
        // Update existing meta data.
        safe_update('arc_meta', $sql, "id=$metaId");

    } elseif (!empty($metaTitle) || !empty($metaRobots)) {
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
    $metaImage = gps('arc_meta_image');
    $metaRobots = gps('arc_meta_robots');

    $values = array(
        'type' => 'section',
        'type_id' => $sectionName,
        'title' => doSlash($metaTitle),
        'image' => intval($metaImage),
        'robots' => doSlash($metaRobots)
    );

    if (empty($values['image'])) {
        unset($values['image']);
        $sql[] = "image = NULL";
    }

    foreach ($values as $key => $value) {
        $sql[] = "$key = '$value'";
    }
    $sql = implode(', ', $sql);

    if ($metaId) {
        // Update existing meta data.
        safe_update('arc_meta', $sql, "id=$metaId");

    } elseif (!empty($metaTitle) || !empty($metaImage) || !empty($metaRobots)) {
        // Create new meta data only if there is data to be saved.
        safe_insert('arc_meta', $sql);

    }

    // Update the meta description.
    $metaDescription = doSlash($metaDescription);
    safe_update('txp_section', "description = '$metaDescription'", "name='$sectionName'");
}

function _arc_meta_category_meta_save($event, $step)
{
    $categoryName = gps('name');

    $metaId = gps('arc_meta_id');
    $metaTitle = gps('arc_meta_title');
    $metaImage = gps('arc_meta_image');
    $metaRobots = gps('arc_meta_robots');

    $values = array(
        'type' => 'category',
        'type_id' => $categoryName,
        'title' => doSlash($metaTitle),
        'image' => intval($metaImage),
        'robots' => doSlash($metaRobots)
    );

    foreach ($values as $key => $value) {
        $sql[] = "$key = '$value'";
    }
    $sql = implode(', ', $sql);

    if ($metaId) {
        // Update existing meta data.
        safe_update('arc_meta', $sql, "id=$metaId");

    } elseif (!empty($metaTitle) || !empty($metaDescription) || !empty($metaImage) || !empty($metaRobots)) {
        // Create new meta data only if there is data to be saved.
        safe_insert('arc_meta', $sql);

    }
}

function _arc_meta_robots()
{
    return array(
        'index, follow' => 'index, follow',
        'index, nofollow' => 'index, nofollow',
        'noindex, follow' => 'noindex, follow',
        'noindex, nofollow' => 'noindex, nofollow'
    );
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
* @%c@ -- category name, can be used on category pages
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

h3. arc_meta_robots

Outputs a meta robots tag when robots have been set. When a site is in testing or debugging mode it will automatically output a 'noindex, nofollow' tag with your actual setting as a comment immediately after the tag for reference.

bc. <txp:arc_meta_robots />

h4. Attributes

* robots -- overrides the robots instructions set using the meta robots field on the article Write page or section/category edit page

h3. arc_meta_keywords

Outputs a meta keywords tag when keywords have been set (only works for articles).

bc. <txp:arc_meta_keywords />

h4. Attributes

* keywords -- overrides the keywords set using the meta keywords field on the article Write page

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

bc. <txp:arc_meta_twitter_card />

Just including the above tag in your templates' @<head>@ tag will output tags for the following:-

* twitter:card -- 'summary' by default, can also be set to 'photo' for image content or 'player' for videos
* twitter:title -- the article's title, section name or category name
* twitter:description -- your page's meta description
* twitter:url -- your page's canonical URL
* twitter:image -- the article's image

You can override the default values of any of these by passing a value to one of the tag's attributes defined below.

To start using Twitter cards you will need to authorise them for your domain by "validating and applying":https://dev.twitter.com/docs/cards/validation/validator on the Twitter website. You will need to supply Twitter with a few details including the URL of a page with a complete Twitter card. It can take several days for Twitter to authorise your site.

h4. Attributes

* card
* title
* description
* url
* image

h3. arc_meta_organization

Outputs Knowledge Graph social profiles script tag for an organisation.

bc. <txp:arc_meta_organization />

h4. Attributes

* name -- Organisation's name
* logo -- URL to organisation's logo (required, will throw an error if not set)
* facebook -- URL to Facebook page
* gplus -- URL to Google+ page
* twitter -- URL to Twitter account

h3. arc_meta_person

Outputs Knowledge Graph social profiles script tag for a person.

bc. <txp:arc_meta_person />

h4. Attributes

* name -- Person's name
* logo -- URL to person's picture
* facebook -- URL to Facebook page
* gplus -- URL to Google+ page
* twitter -- URL to Twitter account

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
