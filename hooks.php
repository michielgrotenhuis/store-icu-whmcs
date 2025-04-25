<?php
/**
 * WHMCS Store.icu Provisioning Module Hooks
 *
 * This file contains hooks for additional automation and integration
 * with the Store.icu module.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Add a menu item to the client's services action menu
 */
add_hook('ClientAreaProductDetailsActionLinks', 1, function($vars) {
    // Only show for services using the store_icu module
    if ($vars['modulename'] == 'store_icu') {
        $serviceId = $vars['id'];
        
        // Check if store is active before showing the link
        try {
            $storeInfo = Capsule::table('mod_store_icu')
                ->where('service_id', $serviceId)
                ->first();

            // Load language file
            require_once __DIR__ . '/store_icu.php';
            $language = isset($_SESSION['Language']) ? $_SESSION['Language'] : 'english';
            $lang = store_icu_LoadLanguage($language);
                
            if ($storeInfo && $storeInfo->status == 'active') {
                return [
                    '<a href="clientarea.php?action=productdetails&id=' . $serviceId . '&modop=custom&a=sso" class="btn btn-default btn-sm">' . $lang['login_to_store'] . '</a>'
                ];
            }
        } catch (\Exception $e) {
            // Silently fail - don't show the button if we can't verify status
        }
    }
    
    return [];
});

/**
 * Add information to the client's product details sidebar
 */
add_hook('ClientAreaProductDetailsSidebar', 1, function($vars) {
    // Only show for services using the store_icu module
    if ($vars['modulename'] == 'store_icu') {
        $serviceId = $vars['id'];
        $output = '';
        
        try {
            $storeInfo = Capsule::table('mod_store_icu')
                ->where('service_id', $serviceId)
                ->first();
                
            if ($storeInfo) {
                $output .= '<div class="panel panel-default">';
                $output .= '<div class="panel-heading"><h3 class="panel-title">Store.icu Details</h3></div>';
                $output .= '<div class="panel-body">';
                $output .= '<p><strong>Shop Handle:</strong> ' . htmlspecialchars($storeInfo->shop_handle) . '</p>';
                $output .= '<p><strong>Status:</strong> ' . ucfirst(htmlspecialchars($storeInfo->status)) . '</p>';
                
                if ($storeInfo->status == 'active') {
                    $output .= '<a href="clientarea.php?action=productdetails&id=' . $serviceId . '&modop=custom&a=sso" class="btn btn-primary btn-block">Login to Store</a>';
                }
                
                $output .= '</div></div>';
            }
        } catch (\Exception $e) {
            // Silently fail - don't show anything if we can't get the info
        }
        
        return $output;
    }
    
    return '';
});

/**
 * After module change (e.g., when admin changes module settings)
 * Ensure the database table exists
 */
add_hook('AfterModuleChange', 1, function($vars) {
    if ($vars['moduletype'] == 'store_icu') {
        // Include the install file to ensure the table exists
        include_once __DIR__ . '/install.php';
    }
});

/**
 * After module create (e.g., when a new product is created with this module)
 * Ensure the database table exists
 */
add_hook('AfterModuleCreate', 1, function($vars) {
    if ($vars['moduletype'] == 'store_icu') {
        // Include the install file to ensure the table exists
        include_once __DIR__ . '/install.php';
    }
});

/**
 * Add a widget to the client area homepage
 */
add_hook('ClientAreaHomePagePanels', 1, function($vars) {
    // Only show for logged in clients
    if (!isset($vars['clientid']) || empty($vars['clientid'])) {
        return;
    }
    
    $clientId = $vars['clientid'];
    $output = '';
    
    try {
        // Get all active store_icu services for this client
        $services = Capsule::table('tblhosting')
            ->join('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->where('tblhosting.userid', $clientId)
            ->where('tblhosting.domainstatus', 'Active')
            ->where('tblproducts.servertype', 'store_icu')
            ->select('tblhosting.id', 'tblhosting.domain')
            ->get();
            
        if (count($services) > 0) {
            // Get store details for each service
            $stores = [];
            foreach ($services as $service) {
                $storeInfo = Capsule::table('mod_store_icu')
                    ->where('service_id', $service->id)
                    ->first();
                    
                if ($storeInfo && $storeInfo->status == 'active') {
                    $stores[] = [
                        'id' => $service->id,
                        'domain' => $service->domain,
                        'shop_handle' => $storeInfo->shop_handle,
                    ];
                }
            }
            
            if (count($stores) > 0) {
                $output .= '<div class="panel panel-default">';
                $output .= '<div class="panel-heading"><h3 class="panel-title">My Online Stores</h3></div>';
                $output .= '<div class="panel-body">';
                $output .= '<div class="row">';
                
                foreach ($stores as $store) {
                    $output .= '<div class="col-md-6">';
                    $output .= '<div class="well well-sm">';
                    $output .= '<h4>' . htmlspecialchars($store['shop_handle']) . '</h4>';
                    $output .= '<a href="clientarea.php?action=productdetails&id=' . $store['id'] . '&modop=custom&a=sso" class="btn btn-primary btn-sm">Login to Store</a>';
                    $output .= ' <a href="clientarea.php?action=productdetails&id=' . $store['id'] . '" class="btn btn-default btn-sm">Manage</a>';
                    $output .= '</div>';
                    $output .= '</div>';
                }
                
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';
            }
        }
    } catch (\Exception $e) {
        // Silently fail
    }
    
    return $output;
});
