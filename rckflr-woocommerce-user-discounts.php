<?php
/*
Plugin Name: Descuentos de Usuario para WooCommerce
Description: Este plugin permite asignar descuentos personalizados a usuarios en WooCommerce basados en una taxonomía específica. Los descuentos pueden aplicarse tanto a productos simples como a variaciones de productos. Además, se ha añadido una funcionalidad que ajusta el rango de precios mostrado para productos variables basándose en los precios con descuento.
Version: 1.0
Author: Mauricio Perera
Author URI: https://www.linkedin.com/in/mauricioperera/
Donate link: https://www.buymeacoffee.com/rckflr
*/ 
// Registrar la Taxonomía para Usuarios

function custom_user_taxonomy() {
    $labels = array(
        'name' => 'Descuentos de Usuario',
        'singular_name' => 'Descuento de Usuario',
        // ... otros labels que quieras definir
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_tagcloud' => true,
        'show_in_quick_edit' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'user_discount'),
    );

    register_taxonomy('user_discount', 'user', $args);
}
add_action('init', 'custom_user_taxonomy');

// Mostrar la Taxonomía en la Página de Edición de Usuarios
function custom_user_taxonomy_add_section($user) {
    $terms = get_terms(array('taxonomy' => 'user_discount', 'hide_empty' => false));
    $user_term = reset(wp_get_object_terms($user->ID, 'user_discount'));
    ?>
    <h3>Descuento de Usuario</h3>
    <table class="form-table">
        <tr>
            <th><label for="user_discount">Asignar Descuento</label></th>
            <td>
                <select name="user_discount" id="user_discount">
                    <option value="">Seleccionar</option>
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo $term->term_id; ?>" <?php selected($user_term->term_id, $term->term_id); ?>><?php echo $term->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'custom_user_taxonomy_add_section');
add_action('edit_user_profile', 'custom_user_taxonomy_add_section');

// Guardar la Asociación de Taxonomía y Usuario
function custom_user_taxonomy_save($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['user_discount'])) {
        $term = intval($_POST['user_discount']);
        wp_set_object_terms($user_id, $term, 'user_discount', false);
        clean_object_term_cache($user_id, 'user_discount');
    }
}
add_action('personal_options_update', 'custom_user_taxonomy_save');
add_action('edit_user_profile_update', 'custom_user_taxonomy_save');

// Agregar campos al formulario de creación de términos
function custom_taxonomy_add_form_fields($taxonomy) {
    ?>
    <div class="form-field">
        <label for="discount_percentage">Porcentaje de Descuento</label>
        <input type="number" name="discount_percentage" id="discount_percentage">
    </div>
    <div class="form-field">
        <label for="access_sale_price">
            <input type="checkbox" name="access_sale_price" id="access_sale_price" value="1">
            ¿Puede acceder a precios de oferta?
        </label>
    </div>
    <div class="form-field">
        <label for="discount_on_sale">
            <input type="checkbox" name="discount_on_sale" id="discount_on_sale" value="1">
            ¿El descuento se aplica sobre los precios de oferta?
        </label>
    </div>
    <?php
}
add_action('user_discount_add_form_fields', 'custom_taxonomy_add_form_fields');

// Agregar campos al formulario de edición de términos
function custom_taxonomy_edit_form_fields($term, $taxonomy) {
    $discount_percentage = get_term_meta($term->term_id, 'discount_percentage', true);
    $access_sale_price = get_term_meta($term->term_id, 'access_sale_price', true);
    $discount_on_sale = get_term_meta($term->term_id, 'discount_on_sale', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="discount_percentage">Porcentaje de Descuento</label></th>
        <td><input type="number" name="discount_percentage" id="discount_percentage" value="<?php echo esc_attr($discount_percentage); ?>"></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="access_sale_price">Acceso a precios de oferta</label></th>
        <td><input type="checkbox" name="access_sale_price" id="access_sale_price" value="1" <?php checked($access_sale_price, 1); ?>></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="discount_on_sale">Descuento en precios de oferta</label></th>
        <td><input type="checkbox" name="discount_on_sale" id="discount_on_sale" value="1" <?php checked($discount_on_sale, 1); ?>></td>
    </tr>
    <?php
}
add_action('user_discount_edit_form_fields', 'custom_taxonomy_edit_form_fields', 10, 2);

// Guardar los valores de los campos adicionales
function save_custom_taxonomy_custom_fields($term_id) {
    if (isset($_POST['discount_percentage'])) {
        update_term_meta($term_id, 'discount_percentage', sanitize_text_field($_POST['discount_percentage']));
    }
    if (isset($_POST['access_sale_price'])) {
        update_term_meta($term_id, 'access_sale_price', 1);
    } else {
        update_term_meta($term_id, 'access_sale_price', 0);
    }
    if (isset($_POST['discount_on_sale'])) {
        update_term_meta($term_id, 'discount_on_sale', 1);
    } else {
        update_term_meta($term_id, 'discount_on_sale', 0);
    }
}
add_action('created_user_discount', 'save_custom_taxonomy_custom_fields');
add_action('edited_user_discount', 'save_custom_taxonomy_custom_fields');

function add_user_taxonomy_admin_page() {
    $tax = get_taxonomy('user_discount');
    add_users_page(
        esc_attr($tax->labels->menu_name),      // Título de la página
        esc_attr($tax->labels->menu_name),      // Título del menú
        $tax->cap->manage_terms,                // Capacidad requerida
        'edit-tags.php?taxonomy=' . $tax->name, // URL del menú
        '',                                     // Función de callback (no necesitamos una para esto)
        '',                                     // Icono (opcional)
        70                                      // Posición en el menú (opcional)
    );
}
add_action('admin_menu', 'add_user_taxonomy_admin_page');
/*function get_discounted_price_based_on_access($access_sale_price, $discount_percentage, $discount_on_sale, $product) {
    // Si el producto es una variación específica
    if ($product instanceof WC_Product_Variation) {
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
    }
    // Si el producto es simple
    elseif ($product instanceof WC_Product_Simple) {
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
    }
    // Si el producto es variable pero no se ha seleccionado una variación específica
    elseif ($product instanceof WC_Product_Variable) {
        $prices = $product->get_variation_prices();
        $regular_price = min($prices['regular_price']);
        $sale_price = isset($prices['sale_price']) ? min($prices['sale_price']) : null;
    }
    else {
        // Tipo de producto desconocido, retornar 0
        return 0;
    }

    // Calcular el descuento sobre el precio regular
    $discounted_regular_price = $regular_price - ($regular_price * ($discount_percentage / 100));

    // Si $access_sale_price es 0, retornar el precio regular con descuento
    if ($access_sale_price == 0) {
        return $discounted_regular_price;
    }

    // Si $access_sale_price es 1 y $discount_on_sale es 0, retornar el precio de oferta
    if ($access_sale_price == 1 && $discount_on_sale == 0) {
        return $sale_price;
    }

    // Si $access_sale_price es 1 y $discount_on_sale es 1, retornar el precio de oferta con descuento
    if ($access_sale_price == 1 && $discount_on_sale == 1) {
        $discounted_sale_price = $sale_price - ($sale_price * ($discount_percentage / 100));
        return $discounted_sale_price;
    }

    // En caso de que $access_sale_price no sea ni 0 ni 1, retornar el precio regular con descuento como valor predeterminado
    return $discounted_regular_price;
}
*/
function get_discounted_price_based_on_access($access_sale_price, $discount_percentage, $discount_on_sale, $product) {
    $discount_percentage = floatval($discount_percentage);

    $regular_price = floatval($product->get_regular_price());
    $sale_price = $product->get_sale_price() ? floatval($product->get_sale_price()) : $regular_price;

    $discounted_regular_price = $regular_price - ($regular_price * ($discount_percentage / 100));

    if ($access_sale_price == '0') {
        return $discounted_regular_price;
    }
    
    if ($access_sale_price == '1' && $sale_price > 0) {
        if ($discount_on_sale == '1') {
            $discounted_sale_price = $sale_price - ($sale_price * ($discount_percentage / 100));
            return $discounted_sale_price;
        }
        return $sale_price;
    }
    
    return $discounted_regular_price;
}

function get_user_discounted_price($price, $product) {
    static $is_processing = false;

    if ($is_processing) {
        return $price;
    }

    // Actuar solo si el producto es simple
    if (!$product->is_type('simple')) {
        return $price;
    }

    $is_processing = true;

    $user_id = get_current_user_id();

    if (!$user_id) {
        $is_processing = false;
        return $price;
    }

    $user_terms = wp_get_object_terms($user_id, 'user_discount');
    if (empty($user_terms) || is_wp_error($user_terms) || !isset($user_terms[0]->term_id)) {
        $is_processing = false;
        return $price;
    }

    $term = $user_terms[0];

    $discount_percentage = get_term_meta($term->term_id, 'discount_percentage', true);
    $access_sale_price = get_term_meta($term->term_id, 'access_sale_price', true);
    $discount_on_sale = get_term_meta($term->term_id, 'discount_on_sale', true);

    $new_price = get_discounted_price_based_on_access($access_sale_price, $discount_percentage, $discount_on_sale, $product);

    $is_processing = false;

    return $new_price;
}

add_filter('woocommerce_product_get_price', 'get_user_discounted_price', 10, 2);
add_filter('woocommerce_product_get_sale_price', 'get_user_discounted_price', 10, 2);

function variable_get_discounted_price_based_on_access($access_sale_price, $discount_percentage, $discount_on_sale, $variation) {
    $discount_percentage = floatval($discount_percentage);

    $regular_price = floatval($variation->get_regular_price());
    $sale_price = $variation->get_sale_price() ? floatval($variation->get_sale_price()) : null;

    $discounted_regular_price = $regular_price - ($regular_price * ($discount_percentage / 100));

    if ($access_sale_price == '0') {
        return $discounted_regular_price;
    }
    
    if ($access_sale_price == '1') {
        if ($sale_price) {
            if ($discount_on_sale == '1') {
                $discounted_sale_price = $sale_price - ($sale_price * ($discount_percentage / 100));
                return $discounted_sale_price;
            }
            return $sale_price;
        } else {
            return $discounted_regular_price;
        }
    }
    
    return $regular_price;
}

function variable_get_user_discounted_price($price, $variation) {
    static $is_processing = false;

    if ($is_processing) {
        return $price;
    }

    // Asegurarse de que el producto es una variación
    if (!$variation->is_type('variation')) {
        return $price;
    }

    $is_processing = true;

    $user_id = get_current_user_id();

    if (!$user_id) {
        $is_processing = false;
        return $price;
    }

    $user_terms = wp_get_object_terms($user_id, 'user_discount');
    if (empty($user_terms) || is_wp_error($user_terms) || !isset($user_terms[0]->term_id)) {
        $is_processing = false;
        return $price;
    }

    $term = $user_terms[0];

    $discount_percentage = get_term_meta($term->term_id, 'discount_percentage', true);
    $access_sale_price = get_term_meta($term->term_id, 'access_sale_price', true);
    $discount_on_sale = get_term_meta($term->term_id, 'discount_on_sale', true);

    $new_price = variable_get_discounted_price_based_on_access($access_sale_price, $discount_percentage, $discount_on_sale, $variation);

    $is_processing = false;

    return $new_price;
}

// Filtros que actúan solo para variaciones de productos variables
add_filter('woocommerce_product_variation_get_price', 'variable_get_user_discounted_price', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'variable_get_user_discounted_price', 10, 2);
add_filter('woocommerce_product_variation_get_sale_price', 'variable_get_user_discounted_price', 10, 2);
function custom_variable_price_range($price, $product) {
    // Verificar si el producto es variable
    if (!$product->is_type('variable')) {
        return $price;
    }

    // Obtener todas las variaciones del producto
    $variations = $product->get_available_variations();

    $min_price = null;
    $max_price = null;

    foreach ($variations as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);
        $variation_price = floatval(variable_get_user_discounted_price($variation_obj->get_price(), $variation_obj));

        if (is_null($min_price) || $variation_price < $min_price) {
            $min_price = $variation_price;
        }

        if (is_null($max_price) || $variation_price > $max_price) {
            $max_price = $variation_price;
        }
    }

    if (!is_null($min_price) && !is_null($max_price)) {
        if ($min_price === $max_price) {
            $price = wc_price($min_price);
        } else {
            $price = sprintf('%1$s&ndash;%2$s', wc_price($min_price), wc_price($max_price));
        }
    }

    return $price;
}
add_filter('woocommerce_variable_sale_price_html', 'custom_variable_price_range', 10, 2);
add_filter('woocommerce_variable_price_html', 'custom_variable_price_range', 10, 2);
?>
