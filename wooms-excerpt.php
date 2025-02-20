<?php
/**
 * Plugin Name: WooMS Excerpt (extension)
 * Description: Краткое описание товара в МойСклад в дополнительном поле сохраняется в excerpt товара
 * Plugin URI: https://github.com/wpcraft-ru/wooms/issues/400
 * Version: 1.1
 * Author: OGlekler
 * Author URI: https://github.com/OlaIola/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * WooMS tested up to: 8.2.0
 *
 * @package WooMS Excerpt
 */

namespace WooMS;

defined('ABSPATH') || exit;

class ProductExcerpt {
	/**
	 * Initialization
	 *
	 * Hooks:
	 * - plugins_loaded
	 * - wooms_add_settings
	 * - admin_init
	 * - wooms_product_save
	 * - wooms_attributes
	 */
	public static function init() {
		add_action('plugins_loaded', function() {
			if (!class_exists('WooMS_Core')) {
				add_action('after_plugin_row_' . plugin_basename(__FILE__), array(__CLASS__, 'plugin_row'), 5, 3);
				return;
			}

			add_action('wooms_add_settings', array(__CLASS__, 'add_settings'), 20);
			add_action('admin_init', array(__CLASS__, 'add_settings'), 20);

			if (get_option('wooms_excerpt')) {
				add_filter('wooms_product_save', array(__CLASS__, 'update_custom_excerpt'), 20, 2);
			}

			// Предотвращаем сохранение поля краткого описания как атрибута
			if (get_option('wooms_excerpt_name')) {
				add_filter('wooms_attributes', array(__CLASS__, 'remove_short_description_from_product_attributes'), 15, 3);
			}
		});
	}


	/**
	 * Update product excerpt from MoySklad
	 *
	 * @param WC_Product $product
	 * @param array $data_api
	 *
	 * @return WC_Product
	 */
	public static function update_custom_excerpt($product, $data_api) {
		$field_name = get_option('wooms_excerpt_name');
		
		if (!$field_name || empty($data_api['attributes'])) {
			return $product;
		}

		$short_description = '';
		
		foreach ($data_api['attributes'] as $attribute) {
			if (empty($attribute['name'])) {
				continue;
			}

			if ($attribute['name'] == $field_name) {
				$short_description = $attribute['value'];
				
				if (!get_option('wooms_short_description')) {
					$product->set_short_description($short_description);
				} else {
					$product->set_description($short_description);
				}
				break;
			}
		}

		// Очищаем краткое описание если оно отсутствует в МойСклад
		if (empty($short_description)) {
			if (!get_option('wooms_short_description')) {
				$product->set_short_description('');
			} else {
				$product->set_description('');
			}
		}

		return $product;
	}

	public static function add_settings() {
		$option_name = 'wooms_excerpt';
		
		register_setting('mss-settings', $option_name);
		register_setting('mss-settings', 'wooms_excerpt_name');

		add_settings_field(
			'wooms_excerpt',
			'Использовать краткое описание из МойСклад',
			function($args) {
				printf(
					'<input type="checkbox" name="%s" value="1" %s />',
					$args['key'],
					checked(1, $args['value'], false)
				);
				echo '<p><small>Активирует синхронизацию краткого описания из дополнительного поля МойСклад</small></p>';
			},
			'mss-settings',
			'woomss_section_other',
			[
				'key' => $option_name,
				'value' => get_option($option_name, 0)
			]
		);

		add_settings_field(
			'wooms_excerpt_name',
			'Название поля для краткого описания',
			function($args) {
				printf(
					'<input type="text" name="%s" value="%s" />',
					$args['key'],
					esc_attr($args['value'])
				);
				echo '<p><small>Укажите название дополнительного поля в МойСклад для краткого описания</small></p>';
			},
			'mss-settings',
			'woomss_section_other',
			[
				'key' => 'wooms_excerpt_name',
				'value' => get_option('wooms_excerpt_name', '')
			]
		);

		remove_action('admin_init', array(__CLASS__, 'add_settings'), 20);
	}

	public static function remove_short_description_from_product_attributes($product_attributes, $product_id, $item) {
		$field_name = get_option('wooms_excerpt_name');
		
		foreach ($product_attributes as $slug => $attribute) {
			if ($attribute->get_name() == $field_name) {
				unset($product_attributes[$slug]);
				break;
			}
		}
		
		return $product_attributes;
	}

	public static function plugin_row( $plugin_file, $plugin_data, $status ) {

		$base_name = plugin_basename( $plugin_file );

		// >= WP 5.5
		$colspan = 4;

		// < WP 5.5
		if( version_compare( $GLOBALS['wp_version'], '5.5', '<' ) ) {
			$colspan = 3;
		}
		?>

		<style>
			.plugins tr[data-plugin='<?php echo $base_name; ?>'] th,
			.plugins tr[data-plugin='<?php echo $base_name; ?>'] td{
				box-shadow:none;
			}
		</style>

		<tr class="plugin-update-tr active">
			<td colspan="<?php echo $colspan; ?>" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-error notice-alt">
					<p><?php _e( 'WooMS Excerpt требуется <a href="https://wpcraft.ru/product/wooms/" target="_blank">Wooms</a> (minimum: 8.1).' ); ?></p>
				</div>
			</td>
		</tr>

		<?php

	}
}

ProductExcerpt::init();
