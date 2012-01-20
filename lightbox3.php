<?php
/*
Plugin Name: Lightbox 3
Plugin URI: http://www.boiteaweb.fr/lightbox3
Description: Used to overlay images on the current page. Lightbox JS v2.03 by <a href="http://www.huddletogether.com/projects/lightbox2/" title="Lightbox JS v2.2 ">Lokesh Dhakar</a>.
Version: 3.0
Author: Juliobox
Author URI: http://www.boiteaweb.fr
*/

/* Traduction */
function bawlb3_l10n_init()
{
  load_plugin_textdomain( 'lightbox_3', '', dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
add_action( 'init','bawlb3_l10n_init' );

function bawlb3_check_ajax()
{
    if (!isset($_POST['lightbox_download']))
        return;

    $file = str_replace(get_bloginfo('url') . '/', ABSPATH, $_POST['lightbox_download']);
    if (!is_file($file))
        return;
    
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $accepted = array('jpeg', 'jpg', 'gif', 'png');
    if (!in_array($ext, $accepted))
        return;

    $mimetypes = array(
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif'
    );

    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private',false);
    header('Content-Type: '.$mimetypes[$ext]);
    header('Content-Disposition: attachment; filename="'.basename($file).'"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: '.filesize($file));
    readfile($file);
    exit();
}
add_action( 'init','bawlb3_check_ajax' );

/* Désinstallation de la version 2 */
if ( isset( $_GET['uninstall_lb2'] ) )
{
  function bawlb3_uninstall_lightbox2()
  {
    if ( wp_verify_nonce( $_GET['_wpnonce'], 'uninstall_lb2' ) )
    {
      delete_option( 'lightbox_2_theme' );
      delete_option( 'lightbox_2_automate' );
      delete_option( 'lightbox_2_resize_on_demand' );
      delete_option( 'lightbox_2_theme_path' );
      wp_redirect( admin_url( 'admin.php?page=bawlb3_config' ) );
    }
  }
  add_action( 'admin_init', 'bawlb3_uninstall_lightbox2' );
}

/* Valeurs par défaut lors de l'activation */
function bawlb3_default_values()
{
  update_option( 'bawlb3_theme', 'Black' );
  update_option( 'bawlb3_automate', 1 );
  update_option( 'bawlb3_resize_on_demand', 0 );
}
register_activation_hook( __FILE__, 'bawlb3_default_values' );

/* Ajout du ou des menus */
function bawlb3_create_menu()
{
  if ( !defined( 'BAW_MENU' ) ) {
    define( 'BAW_MENU', true );
    add_menu_page( 'BoiteAWeb.fr', 'BoiteAWeb', 'manage_options', 'baw_menu', 'baw_about', plugins_url('/images/icon.png', __FILE__) );
  }
  add_submenu_page( 'baw_menu', 'Lightbox 3', 'Lightbox 3', 'install_plugins', 'bawlb3_config', 'bawlb3_page' );
}
add_action( 'admin_menu', 'bawlb3_create_menu' );

/* Fonction perso de sanitization */
function bawlb3_sanitize_theme( $str )
{
  return preg_replace( '/[^0-9a-zA-Z ]/', '', $str );
}

/* Ajout des CSS & JS */
function bawlb3_style_script() {
  $bawlb3_theme = bawlb3_sanitize_theme( get_option( 'bawlb3_theme' ) );

  wp_register_style( 'bawlb3_css', plugins_url( '/Themes/' . $bawlb3_theme . '/lightbox.css', __FILE__ ) );
  wp_enqueue_style( 'bawlb3_css');

  if ( get_option( 'bawlb3_resize_on_demand' ) == 'on' ) {
  	$stimuli_lightbox_js = 'lightbox-resize.js';
  } else {
  	$stimuli_lightbox_js = 'lightbox.js';
  }

	wp_register_script( 'bawlb3_js', plugins_url( $stimuli_lightbox_js, __FILE__ ), array( 'scriptaculous-effects' ), '1.8' );
  wp_enqueue_script( 'bawlb3_js' );
}
add_action( 'init', 'bawlb3_style_script' );

/* Ajout du code html pour les liens des images */
function bawlb3_autoexpand_rel_lightbox($content) {
	global $post;
	$pattern        = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.(?:bmp|gif|jpg|jpeg|png)['\"][^\>]*)>/i";
	$replacement    = '$1 rel="lightbox['.$post->ID.']">';
	$content = preg_replace($pattern, $replacement, $content);
	return $content;
}

/* Actions si l'option est cochée */
if ( get_option( 'bawlb3_automate' ) == 'on') {
	add_action( 'the_content', 'bawlb3_autoexpand_rel_lightbox', 99 );
	add_action( 'the_excerpt', 'bawlb3_autoexpand_rel_lightbox', 99 );
}

/* Page d'options */
function bawlb3_page()
{
?>
<div class="wrap">
  <h2>Lightbox 3 <?php _e('options', 'lightbox_3') ?></h2>
<?php
if ( isset( $_GET['settings-updated'] ) ) {
  echo '<div class="updated"><p><strong>Lightbox 3 : </strong> ' . __( 'Settings updated' ) .'</p></div>';
  }
?>
  <form name="form1" method="post" action="options.php">
    <table width="100%" cellspacing="2" cellpadding="5" class="form-table">
        <tr valign="baseline">
        <th scope="row"><?php _e( 'Lightbox Appearance', 'lightbox_3' ) ?></th>
        <td>

<?php /* Boucle pour les thèmes */
$dirs = glob( realpath( dirname( __FILE__ ) ) . '/Themes/*',GLOB_ONLYDIR );
if ( count( $handle > 0 ) ) {
  foreach($dirs as $dir) {
    $dirss = explode( '/', $dir);
    $theme_dirs[$dirss[count($dirss)-1]] = $dir;
  }
}
echo "\n" . '<select name="bawlb3_theme">' . "\n";
$current_theme = get_option( 'bawlb3_theme' );
foreach( $theme_dirs as $shortname => $fullpath ) {
  if ( file_exists( $fullpath . '/lightbox.css') ) {
    echo '<option value="' . $shortname . '" ' . selected( $current_theme, $shortname ) . '>' . $shortname . '</option>' ."\n";
  }
}
echo "\n" .'</select>';
?>
<p><small><?php _e('Default : "Black" theme', 'lightbox_3') ?></small></p>
        </td>
        </tr>
		<tr valign="baseline">
        <th scope="row"><?php _e( 'Auto-lightbox image links', 'lightbox_3' ) ?></th>
        <td>
          <input type="checkbox" name="bawlb3_automate" <?php checked( get_option( 'bawlb3_automate' ), 'on' ); ?> />
        <p><small><?php _e( 'Let the plugin add necessary html to image links', 'lightbox_3' ) ?></small></p>
        </td>
        <tr valign="baseline">
        <th scope="row"><?php _e( 'Shrink large images to fit smaller screens', 'lightbox_3' ) ?></th>
        <td>
          <input type="checkbox" name="bawlb3_resize_on_demand" <?php checked( get_option( 'bawlb3_resize_on_demand' ), 'on' ); ?> />
        <p><small><?php _e( 'Note: <u>Excessively large images</u> waste bandwidth and slow browsing!', 'lightbox_3' ) ?></small></p>
        </td>
        </tr>
        <?php
        if ( get_option( 'lightbox_2_theme' ) <> '' )
        {
        ?>
        <tr valign="baseline">
        <th scope="row">Lightbox 3</th>
        <td>
        <input type="button" value="<?php _e( 'Uninstall Lighbox 2', 'lightbox_3' ); ?>" onclick="location.href='<?php echo wp_nonce_url( admin_url( 'admin.php?page=bawlb3_config&uninstall_lb2=true' ), 'uninstall_lb2' ); ?>';" />
        <p><small><?php _e( 'Lightbox 2 is not properly uninstalled (still remains some options in DB), clic here to delete this options.', 'lightbox_3' ) ?></small></p>
        </td>
        </tr>
        <?php
         }
        ?>
     </table>

    <?php /* Ajout du nonce anti CSRF et des autres input hidden */
    settings_fields( 'lightbox_3-settings-group' );
    ?>

    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e( 'Save Changes', 'lightbox_3' ) ?>" />
    </p>
  </form>
</div>
<?php
}

/* Enregistrement des champs autorisés dans le formulaire */
function bawlb3_register_settings()
{
  register_setting( 'lightbox_3-settings-group', 'bawlb3_theme', 'bawlb3_sanitize_theme' );
  register_setting( 'lightbox_3-settings-group', 'bawlb3_automate' );
  register_setting( 'lightbox_3-settings-group', 'bawlb3_resize_on_demand' );
}
add_action( 'admin_init', 'bawlb3_register_settings' );

/* Fonction de désinstalaltion de la V3 */
function bawlb3_uninstaller(){
  delete_option( 'bawlb3_theme' );
  delete_option( 'bawlb3_automate' );
  delete_option( 'bawlb3_resize_on_demand' );
}
register_uninstall_hook( __FILE__, 'bawlb3_uninstaller' );

?>
