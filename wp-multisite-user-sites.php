<?php 
/*
 * Plugin Name: Multisite User Site Manager
 * Description: Manage a users sites and roles on each site.
 * Version:     1.0.0a
 * Plugin URI:  https://github.com/RobertMeyerJr/wp-multisite-user-site-manager
 * Author:      Robert Meyer Jr
 * Author URI:  
 * Text Domain: multi-site user-management
 * Network:     true
*/

$Multisite_User_Sites = Multisite_User_Sites::getInstance();

class Multisite_User_Sites{
	public static function getInstance(){
		static $instance;
		if( ! isset( $instance ) ) {
			$instance = new self();
		}
		return $instance;
	}
	
	public function __construct(){
		add_action('init',[$this,'init']);
	}
	
	public function init(){
		//Only allow on Main site, and if user is a superadmin (via update_core cap)
		if( is_main_site() && current_user_can('update_core') ){
			add_action('edit_user_profile',			[$this,	'list_sites'], 1000);
			add_action('edit_user_profile_update', 	[$this,	'update']);		
		}
	}
	
	public function update($user_id){
		$site_roles = $_POST['ms_user_sites_role'];
		
		$current_blog_id = get_current_blog_id();		
		foreach($site_roles as $site_id=>$role){
			if($current_blog_id != $site_id){
				switch_to_blog( $site_id );
			}
			if( !empty($role) ){
				$user = new \WP_User( $user_id );
				$user->set_role( $role );
			}
			if($current_blog_id != $site_id){
				restore_current_blog();
			}
		}
	}
	
	public function list_sites($profileuser){
		$user_id = $profileuser->ID;
		
		$blogs = get_blogs_of_user($user_id);
		$sites = get_sites();
		
		?>
		<h2>User Sites</h2>
		<table class="wp-list-table  fixed striped">
			<thead>
				<tr>
					<th>Site ID</th>
					<th>Blog</th>
					<th>Domain</th>
					<th>Role</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($sites as $s) : ?>
				<?php 
					switch_to_blog( $s->blog_id );
					$ud = get_userdata($user_id);					
				?>
				<tr>
					<td><?php echo $s->blog_id?></td>
					<td><?php echo $s->blogname?></td>
					<td><a target=_blank href="<?php echo $s->siteurl ?>"><?php echo $s->domain?></a></td>
					<td>	
						<select name=ms_user_sites_role[<?php echo $s->blog_id?>]>
							<?php if( empty($ud->roles) ) : ?>
								<option value=''>( None )</option>
							<?php endif; ?>
							<?php wp_dropdown_roles($ud->roles[0]) ?>
						</select>
					</td>
				</tr>
				<?php restore_current_blog(); ?>
			<?php endforeach ; ?>
			</tbody>
		</table>
		<?php
	}
	
}

