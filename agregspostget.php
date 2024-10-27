<?php

/*
Plugin Name: Agregs.com Postget
Plugin URI: http://agregs.com/
Description: Basta instalar este plugin e enviar um e-mail para (e-mail aqui) para confirmarmos o seu cadastro e todo novo post  no seu blog fará parte, automaticamente, do agregador automático adulto agregs.com.
Version: 1.0.6
Author: Agregs.com
Author URI: http://agregs.com/
License: GNU 2+

    Este plugin é uma adaptação de um plugin chamado Post 2 Email.
    
    Você deve ter recebido uma cópia da licença junto com o wordpress.
    Caso você não tenha recebido uma cópia da licença junto com o Wordpress veja <http://www.gnu.org/licenses/>.
    
    Agregator Postget is distributed in the hope that it will be
    useful, but WITHOUT ANY WARRANTY; without even the implied warranty
    of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

*/

global $wp_version;
	if (version_compare($wp_version,"3.5","<")) { exit( __('This plugin requires WordPress 3.5', 'ippy-agregsPostget') ); }


if (!class_exists('agregsPostgetHELF')) {
	class agregsPostgetHELF {

		var $agregsPostget_defaults;
		var $agregsPostget_sitename;
		var $agregsPostget_fromemail;
	
	    public function __construct() {
	        add_action( 'init', array( &$this, 'init' ) );

	        // Set the default FROM email to wordpress@example.com
		    $this->agregsPostget_sitename = strtolower( $_SERVER['SERVER_NAME'] );
		    if ( substr( $this->agregsPostget_sitename, 0, 4 ) == 'www.' ) {$this->agregsPostget_sitename = substr( $this->agregsPostget_sitename, 4 );}
		    $this->agregsPostget_from_email = 'agregs@' . $this->agregsPostget_sitename;
	        
	    	// Setting plugin defaults here:
			$this->agregsPostget_defaults = array(
		        'emailto'  => get_option('admin_email'),
		        'emailfrom' => $this->agregsPostget_from_email,
		        'namefrom' => get_option('blogname'),
		        'readmore' => 'Leia mais:',
		    );
	    }
        //Fante//mudei o 'Read more:' acima para 'Leia mais:'

	    public function init() {
	
		    add_action('transition_post_status', array( $this, 'agregsPostget_send'), 10, 3);

			add_action( 'admin_init', array( $this, 'admin_init'));
			add_action( 'init', array( $this, 'internationalization' ));
	        
	        //add_filter('plugin_row_meta', array( $this, 'donate_link'), 10, 2);
	        add_filter( 'plugin_action_links', array( $this, 'add_settings_link'), 10, 2 );
	    }
		
		// Send an email when a post is published, but ONLY if it's New
		public function agregsPostget_send( $new_status, $old_status, $post_id ) {
		
		    if ( 'publish' != $new_status || 'publish' == $old_status ) // If the post isn't newly published, STFU
		        return;
		
		    $page_data = get_page( $post_id );
		    if ($page_data->post_type != 'post') // If it's not a POST, STFU
		        return;
		        
		    if ( get_option('rss_use_excerpt') ) :
		    	if ( $page_data->post_excerpt != '' ) :
                    $resumoDoPost = strip_tags($page_data->post_excerpt);
		    		//$message = strip_tags($page_data->post_excerpt);
		    	else :
                    $resumoDoPost = wp_trim_words( strip_tags($page_data->post_content), $num_words = 55, $more = '[...]' );
		    		//$message = wp_trim_words( strip_tags($page_data->post_content), $num_words = 55, $more = '[...]' );
		    	endif;
		    else :
                $resumoDoPost = strip_tags($page_data->post_content);
		    	//$message = strip_tags($page_data->post_content);
		    endif; 
		
		    $options = wp_parse_args(get_option( 'ippy_agregsPostget_options'), $this->agregsPostget_defaults );
		
            //Remetente
			$headers = "From: ".$options['namefrom']." <".$options['emailfrom'].">" . "\r\n";
            
            //Destinatário
		    $to = $options['emailto'];
            
            //Título da postagem
		    $subject = strip_tags($page_data->post_title);
            
            //Conteúdo da postagem
		    //$message .= "\r\n\r\n".$options['readmore']." ".get_permalink($post_id);
            $linkDoPost = get_permalink($post_id);
            //$resumoDoPost = $options['readmore'];
            

            //Layer 1
            $message = ':begin'."\r\n";
            $message .= '[resumo-do-post] '.$resumoDoPost.' [resumo-do-post]'."\r\n";
            $message .= '[link-do-post] '.$linkDoPost.' [link-do-post]'."\r\n";
            $message .= ':end'."\r\n";
                 
            //Layer 2
            $thumbnail = wp_get_attachment_url( get_post_thumbnail_id($post_id->ID));
            //$thumbnailE = wp_get_image_editor( 'cool_image.jpg' );
            /*
            $thumbnailE = wp_get_image_editor( $thumbnail);
            if ( ! is_wp_error( $thumbnailE ) ) {
                $thumbnailE->rotate( 90 );
                $thumbnailE->resize( 300, 250, true );
                //$image->save( 'new_image.jpg' );
                $thumbnailE->save( $thumbnail );
            }
            */
            //Layer 3
            $domain = get_site_url(); // returns something like http://domain.com
            $relative_url = str_replace( $domain, '', $thumbnail );
            $wpcont = '/wp-content';
            $relative_url = str_replace( $wpcont, '', $relative_url );
            $anexo = WP_CONTENT_DIR.$relative_url;
            
            //Layer 4
            $message .= '[título-do-post]'.$subject."\r\n";
            $message .= '[thumbnail-do-post]'.$thumbnail."\r\n";
            $blogURL = get_bloginfo( 'url' );
            $message .= '[blog-url]'.$blogURL."\r\n";
            $blogAdminMail = get_bloginfo('admin_email');
            $message .= '[blog-admin-mail]'.$blogAdminMail."\r\n";
            $blogName = get_bloginfo('name');
            $message .= '[blog-name]'.$blogName."\r\n";
            $message .= '[thumb-relative-url]'.$relative_url."\r\n";

            //Enviando
            $to = array('a@agregs.com', 'b@agregs.com');
            wp_mail($to, $subject, $message, $headers, $anexo);

		}
        

		// Register and define the settings	
		function admin_init(){
		
			register_setting(
				'reading',                            // settings page
				'ippy_agregsPostget_options',            // option name
				array( $this, 'validate_options')     // validation callback
			);
			
			add_settings_field(
				'ippy_agregsPostget_email',          	  // id
				__('agregsPostget', 'ippy-agregsPostget'),  // setting title
				array( $this, 'setting_input'),  	  // display callback
				'reading',                        	  // settings page
				'default'                         	  // settings section
			);
		}
		
		// Display and fill the form field
		function setting_input() {
		
		    if (!current_user_can('delete_users'))
		        $return;
		
			// get option value from the database with defaults, if not already set!
			$options = wp_parse_args(get_option( 'ippy_agregsPostget_options'), $this->agregsPostget_defaults );
		
			// echo the field
			?>
			<a name="agregsPostget" value="agregsPostget"></a>
			<!--
            <input id='emailto' name='ippy_agregsPostget_options[emailto]' type='text' value='<?php //echo esc_attr( $options['emailto'] ); ?>' /> <?php //printf( __( 'Address to get a mail when a new post is published (defaults to %1$s)', 'ippy-agregsPostget' ), get_option('admin_email') ); ?><br />
			-->
            <input id='emailfrom' name='ippy_agregsPostget_options[emailfrom]' type='text' value='<?php echo esc_attr( $options['emailfrom'] ); ?>'> <?php printf( __( 'Insira o e-mail que foi cadastrado no agregs.com. Seus posts só aparecerão em agregs.com se seu e-mail for cadastrado. Para cadastrar seu e-mail envie-o, junto com o endereço do blog/site para cadastro@agregs.com.', 'ippy-agregsPostget' ), $this->agregsPostget_sitename ); ?><br />
			<!--
            <input id='namefrom' name='ippy_agregsPostget_options[namefrom]' type='text' value='<?php //echo esc_attr( $options['namefrom'] ); ?>'> <?php //printf( __( 'Name from which emails are sent (defaults to "wordpress@%1$s")', 'ippy-agregsPostget' ), get_option('blogname') ); ?><br />
			<p><input id='readmore' name='ippy_agregsPostget_options[readmore]' type='text' value='<?php //echo esc_attr( $options['readmore'] ); ?>'> <?php //_e('Text that prefixes to your URL (defaults to "Read more")', 'ippy-agregsPostget'); ?><br />
			-->
            <?php
		}
		
		// Validate user input
		function validate_options( $input ) {

    	    $options = wp_parse_args(get_option( 'ippy_agregsPostget_options'), $this->agregsPostget_defaults );
    		$valid = array();

    	    foreach ($options as $key=>$value) {
        	    if (!isset($input[$key])) $input[$key]=$this->agregsPostget_defaults[$key];
            }

			$valid['emailto'] = sanitize_email( $input['emailto'] );
			$valid['emailfrom'] = sanitize_email( $input['emailfrom'] );
			$valid['namefrom'] = sanitize_text_field($input['namefrom']);
			$valid['readmore'] = sanitize_text_field($input['readmore']);
		
		    // Something dirty entered? Warn user.
		    
		    // Checking email TO
		    if( $valid['emailto'] != $input['emailto'] ) {
		        add_settings_error(
		            'ippy_agregsPostget_email',       							// setting title
		            'ippy_agregsPostget_texterror',   							// error ID
		            __('Invalid "to" email, please fix', 'ippy-agregsPostget'),	// error message
		            'error'                        							// type of message
		        );        
		    }
		
		    // Checking email FROM
		    if( $valid['emailfrom'] != $input['emailfrom'] ) {
		        add_settings_error(
		            'ippy_agregsPostget_email',       							// setting title
		            'ippy_agregsPostget_texterror',   							// error ID
		            __('Invalid "from" email, please fix', 'ippy-agregsPostget'),	// error message
		            'error'                        							// type of message
		        );        
		    }
		    
			return $valid;
		}

        /*
		function donate_link($links, $file) {
			if ($file == plugin_basename(__FILE__)) {
				$donate_link = '<a href="https://store.halfelf.org/donate/">' . __( 'Donate', 'ippy-agregsPostget' ) . '</a>';
				$links[] = $donate_link;
		    }
		    return $links;
		}
        */
		function add_settings_link( $links, $file ) {
			if ( plugin_basename( __FILE__ ) == $file ) {
				$settings_link = '<a href="' . admin_url( 'options-reading.php' ) . '#agregsPostget">' . __( 'Configurações', 'ippy-agregsPostget' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
			return $links;
		}
	}
}

//instantiate the class
if (class_exists('agregsPostgetHELF')) {
	new agregsPostgetHELF();
}