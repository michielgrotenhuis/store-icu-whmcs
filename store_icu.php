<?php
/**
 * WHMCS Store.icu Provisioning Module
 *
 * This module integrates with the Store.icu ecommerce sitebuilder API.
 *
 * @copyright Copyright (c) 2025
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Load language file
 */
function store_icu_LoadLanguage($language = null)
{
    global $_LANG;
    
    if (!$language) {
        $language = (isset($_SESSION['Language'])) ? $_SESSION['Language'] : 'english';
    }
    
    $languageFilePath = __DIR__ . '/lang/' . strtolower($language) . '.php';
    
    // Fall back to English if the requested language file doesn't exist
    if (!file_exists($languageFilePath)) {
        $languageFilePath = __DIR__ . '/lang/english.php';
    }
    
    require_once($languageFilePath);
    
    return $_LANG['store_icu'];
}

/**
 * Define module related metadata
 *
 * @return array
 */
function store_icu_MetaData()
{
    return [
        'DisplayName' => 'Store.icu Ecommerce Sitebuilder',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
    ];
}

/**
 * Define product configuration options
 *
 * @return array
 */
function store_icu_ConfigOptions()
{
    return [
        'distributor_email' => [
            'FriendlyName' => 'Distributor Email ID',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Distributor or reseller or subreseller email ID',
            'Default' => '',
            'SimpleMode' => true,
        ],
        'distributor_password' => [
            'FriendlyName' => 'Distributor Password',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Distributor or reseller or subreseller email ID password',
            'Default' => '',
            'SimpleMode' => true,
        ],
        'default_currency' => [
            'FriendlyName' => 'Default Store Currency',
            'Type' => 'text',
            'Size' => '10',
            'Description' => 'Default currency for new stores (e.g., USD)',
            'Default' => 'USD',
        ],
        'default_timezone' => [
            'FriendlyName' => 'Default Store Timezone',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Default timezone for new stores (e.g., America/New_York)',
            'Default' => 'UTC',
        ],
        'default_country' => [
            'FriendlyName' => 'Default Store Country',
            'Type' => 'text',
            'Size' => '2',
            'Description' => 'Default country code for new stores (ISO 2-letter code)',
            'Default' => 'US',
        ],
        'default_language' => [
            'FriendlyName' => 'Default Store Language',
            'Type' => 'text',
            'Size' => '5',
            'Description' => 'Default language for new stores (e.g., en-US)',
            'Default' => 'en-US',
        ],
        'package_type' => [
            'FriendlyName' => 'Package Type',
            'Type' => 'dropdown',
            'Options' => 'basic,standard,premium,ultimate',
            'Description' => 'Package type for new stores',
            'Default' => 'basic',
            'SimpleMode' => true,
        ],
    ];
}

/**
 * Make API call to Store.icu
 *
 * @param array $params Module parameters
 * @param string $endpoint API endpoint
 * @param array $postData POST data for the API call
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param string $authToken Auth token (optional)
 * @return array Response from the API
 */
function store_icu_ApiCall($params, $endpoint, $postData = [], $method = 'POST', $authToken = '')
{
    // API base URL
    $apiBaseUrl = 'https://core-api.store.icu/v1/account/super/';
    
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set headers
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    // Add auth token if provided
    if (!empty($authToken)) {
        $headers[] = 'Authorization: Bearer ' . $authToken;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Set request method and data
    switch ($method) {
        case 'GET':
            if (!empty($postData)) {
                $queryString = http_build_query($postData);
                curl_setopt($ch, CURLOPT_URL, $apiBaseUrl . $endpoint . '?' . $queryString);
            }
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($postData)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            }
            break;
    }
    
    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        $errorMessage = 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        
        logModuleCall(
            'store_icu',
            $endpoint,
            $postData,
            $errorMessage,
            $errorMessage
        );
        
        return [
            'success' => false,
            'error' => $errorMessage,
        ];
    }
    
    curl_close($ch);
    
    // Parse response
    $responseData = json_decode($response, true);
    
    // Log module call
    logModuleCall(
        'store_icu',
        $endpoint,
        [
            'postData' => $postData,
            'method' => $method,
        ],
        $response,
        $responseData,
        ['distributor_password']
    );
    
    // Check for successful response
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'data' => $responseData,
        ];
    } else {
        $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown error';
        return [
            'success' => false,
            'error' => $errorMessage,
            'http_code' => $httpCode,
        ];
    }
}

/**
 * Authenticate with the API and get auth token
 *
 * @param array $params Module parameters
 * @return array Authentication result
 */
function store_icu_Authenticate($params)
{
    $email = $params['configoption1']; // distributor_email
    $password = $params['configoption2']; // distributor_password
    
    if (empty($email) || empty($password)) {
        return [
            'success' => false,
            'error' => 'Distributor email or password not set',
        ];
    }
    
    $postData = [
        'email' => $email,
        'password' => $password,
    ];
    
    $response = store_icu_ApiCall($params, 'login', $postData);
    
    if ($response['success'] && isset($response['data']['token'])) {
        return [
            'success' => true,
            'token' => $response['data']['token'],
        ];
    } else {
        return [
            'success' => false,
            'error' => isset($response['error']) ? $response['error'] : 'Authentication failed',
        ];
    }
}

/**
 * Create a new user account
 *
 * @param array $params Module parameters
 * @param string $authToken Authentication token
 * @return array User creation result
 */
function store_icu_CreateUser($params, $authToken)
{
    $client = $params['clientsdetails'];
    
    $postData = [
        'name' => $client['firstname'] . ' ' . $client['lastname'],
        'email' => $client['email'],
        'country' => $client['countrycode'] ?: $params['configoption5'], // Use client country or default
        'password' => $params['password'], // Auto-generated password from WHMCS
    ];
    
    $response = store_icu_ApiCall($params, 'users/create', $postData, 'POST', $authToken);
    
    if ($response['success'] && isset($response['data']['user_id'])) {
        return [
            'success' => true,
            'user_id' => $response['data']['user_id'],
            'data' => $response['data'],
        ];
    } else {
        return [
            'success' => false,
            'error' => isset($response['error']) ? $response['error'] : 'Failed to create user',
        ];
    }
}

/**
 * Provision a new store for a user
 *
 * @param array $params Module parameters
 * @param string $authToken Authentication token
 * @param string $userId User ID
 * @return array Store provisioning result
 */
function store_icu_ProvisionStore($params, $authToken, $userId)
{
    // Generate a shop handle based on domain name or username
    $shopHandle = '';
    if (!empty($params['domain'])) {
        // Remove non-alphanumeric characters and convert to lowercase
        $shopHandle = preg_replace('/[^a-zA-Z0-9]/', '', $params['domain']);
    } else {
        // Use username as fallback
        $shopHandle = preg_replace('/[^a-zA-Z0-9]/', '', $params['username']);
    }
    
    // Ensure handle is lowercase
    $shopHandle = strtolower($shopHandle);
    
    $postData = [
        'user_id' => $userId,
        'shop_handle' => $shopHandle,
        'package_type' => $params['configoption7'], // Package type
        'currency' => $params['configoption3'], // Default currency
        'timezone' => $params['configoption4'], // Default timezone
        'country' => $params['configoption5'], // Default country
        'language' => $params['configoption6'], // Default language
    ];
    
    $response = store_icu_ApiCall($params, 'stores/provision', $postData, 'POST', $authToken);
    
    if ($response['success'] && isset($response['data']['store_id'])) {
        return [
            'success' => true,
            'store_id' => $response['data']['store_id'],
            'shop_handle' => $shopHandle,
            'data' => $response['data'],
        ];
    } else {
        return [
            'success' => false,
            'error' => isset($response['error']) ? $response['error'] : 'Failed to provision store',
        ];
    }
}

/**
 * Provision a service
 *
 * @param array $params Module parameters
 * @return string "success" or error message
 */
function store_icu_CreateAccount($params)
{
    try {
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return $authResult['error'];
        }
        
        $authToken = $authResult['token'];
        
        // Create user account
        $userResult = store_icu_CreateUser($params, $authToken);
        if (!$userResult['success']) {
            return $userResult['error'];
        }
        
        $userId = $userResult['user_id'];
        
        // Provision store for the user
        $storeResult = store_icu_ProvisionStore($params, $authToken, $userId);
        if (!$storeResult['success']) {
            return $storeResult['error'];
        }
        
        // Save store information in the database for future reference
        try {
            Capsule::table('mod_store_icu')->insert([
                'service_id' => $params['serviceid'],
                'user_id' => $userId,
                'store_id' => $storeResult['store_id'],
                'shop_handle' => $storeResult['shop_handle'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Log error but continue since the store is already provisioned
            logModuleCall(
                'store_icu',
                'CreateAccount_SaveDB',
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
        
        return 'success';
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'CreateAccount',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Suspend a service
 *
 * @param array $params Module parameters
 * @return string "success" or error message
 */
function store_icu_SuspendAccount($params)
{
    try {
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return $authResult['error'];
        }
        
        $authToken = $authResult['token'];
        
        // Get shop handle from database
        try {
            $storeInfo = Capsule::table('mod_store_icu')
                ->where('service_id', $params['serviceid'])
                ->first();
                
            if (!$storeInfo) {
                return 'Error: Store information not found';
            }
            
            $shopHandle = $storeInfo->shop_handle;
        } catch (\Exception $e) {
            return 'Error retrieving store information: ' . $e->getMessage();
        }
        
        // Call suspend-shop endpoint
        $response = store_icu_ApiCall(
            $params,
            'stores/suspend-shop',
            ['shop_handle' => $shopHandle],
            'POST',
            $authToken
        );
        
        if ($response['success']) {
            // Update status in database
            try {
                Capsule::table('mod_store_icu')
                    ->where('service_id', $params['serviceid'])
                    ->update([
                        'status' => 'suspended',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } catch (\Exception $e) {
                // Log error but continue since the store is already suspended
                logModuleCall(
                    'store_icu',
                    'SuspendAccount_UpdateDB',
                    $params,
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            }
            
            return 'success';
        } else {
            return isset($response['error']) ? $response['error'] : 'Failed to suspend store';
        }
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'SuspendAccount',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Unsuspend a service
 *
 * @param array $params Module parameters
 * @return string "success" or error message
 */
function store_icu_UnsuspendAccount($params)
{
    try {
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return $authResult['error'];
        }
        
        $authToken = $authResult['token'];
        
        // Get shop handle from database
        try {
            $storeInfo = Capsule::table('mod_store_icu')
                ->where('service_id', $params['serviceid'])
                ->first();
                
            if (!$storeInfo) {
                return 'Error: Store information not found';
            }
            
            $shopHandle = $storeInfo->shop_handle;
        } catch (\Exception $e) {
            return 'Error retrieving store information: ' . $e->getMessage();
        }
        
        // Call unsuspend-shop endpoint
        $response = store_icu_ApiCall(
            $params,
            'stores/unsuspend-shop',
            ['shop_handle' => $shopHandle],
            'POST',
            $authToken
        );
        
        if ($response['success']) {
            // Update status in database
            try {
                Capsule::table('mod_store_icu')
                    ->where('service_id', $params['serviceid'])
                    ->update([
                        'status' => 'active',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } catch (\Exception $e) {
                // Log error but continue since the store is already unsuspended
                logModuleCall(
                    'store_icu',
                    'UnsuspendAccount_UpdateDB',
                    $params,
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            }
            
            return 'success';
        } else {
            return isset($response['error']) ? $response['error'] : 'Failed to unsuspend store';
        }
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'UnsuspendAccount',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Terminate a service
 *
 * @param array $params Module parameters
 * @return string "success" or error message
 */
function store_icu_TerminateAccount($params)
{
    try {
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return $authResult['error'];
        }
        
        $authToken = $authResult['token'];
        
        // Get store and user information from database
        try {
            $storeInfo = Capsule::table('mod_store_icu')
                ->where('service_id', $params['serviceid'])
                ->first();
                
            if (!$storeInfo) {
                return 'Error: Store information not found';
            }
            
            $shopHandle = $storeInfo->shop_handle;
            $userId = $storeInfo->user_id;
        } catch (\Exception $e) {
            return 'Error retrieving store information: ' . $e->getMessage();
        }
        
        // Deactivate store
        $storeResponse = store_icu_ApiCall(
            $params,
            'stores/deactivate',
            ['shop_handle' => $shopHandle],
            'POST',
            $authToken
        );
        
        // Deactivate user
        $userResponse = store_icu_ApiCall(
            $params,
            'users/deactivate',
            ['user_id' => $userId],
            'POST',
            $authToken
        );
        
        // Check if either operation was successful
        if ($storeResponse['success'] || $userResponse['success']) {
            // Update status in database
            try {
                Capsule::table('mod_store_icu')
                    ->where('service_id', $params['serviceid'])
                    ->update([
                        'status' => 'terminated',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } catch (\Exception $e) {
                // Log error but continue since the store is already terminated
                logModuleCall(
                    'store_icu',
                    'TerminateAccount_UpdateDB',
                    $params,
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            }
            
            return 'success';
        } else {
            $error = '';
            if (isset($storeResponse['error'])) {
                $error .= 'Store deactivation error: ' . $storeResponse['error'] . '. ';
            }
            if (isset($userResponse['error'])) {
                $error .= 'User deactivation error: ' . $userResponse['error'];
            }
            return $error ?: 'Failed to terminate account';
        }
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'TerminateAccount',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Change package for a service
 *
 * @param array $params Module parameters
 * @return string "success" or error message
 */
function store_icu_ChangePackage($params)
{
    try {
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return $authResult['error'];
        }
        
        $authToken = $authResult['token'];
        
        // Get shop handle from database
        try {
            $storeInfo = Capsule::table('mod_store_icu')
                ->where('service_id', $params['serviceid'])
                ->first();
                
            if (!$storeInfo) {
                return 'Error: Store information not found';
            }
            
            $shopHandle = $storeInfo->shop_handle;
        } catch (\Exception $e) {
            return 'Error retrieving store information: ' . $e->getMessage();
        }
        
        // Call change-package endpoint
        $response = store_icu_ApiCall(
            $params,
            'stores/change-package',
            [
                'shop_handle' => $shopHandle,
                'package_type' => $params['configoption7'], // New package type
            ],
            'POST',
            $authToken
        );
        
        if ($response['success']) {
            // Update status in database
            try {
                Capsule::table('mod_store_icu')
                    ->where('service_id', $params['serviceid'])
                    ->update([
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } catch (\Exception $e) {
                // Log error but continue since the package is already changed
                logModuleCall(
                    'store_icu',
                    'ChangePackage_UpdateDB',
                    $params,
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            }
            
            return 'success';
        } else {
            return isset($response['error']) ? $response['error'] : 'Failed to change package';
        }
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'ChangePackage',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Admin services tab fields
 *
 * @param array $params Module parameters
 * @return array Admin area fields
 */
function store_icu_AdminServicesTabFields($params)
{
    try {
        // Get store information from database
        $storeInfo = Capsule::table('mod_store_icu')
            ->where('service_id', $params['serviceid'])
            ->first();
            
        if (!$storeInfo) {
            return [
                'Store Information' => 'No store information found for this service',
            ];
        }
        
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return [
                'Store Information' => 'API Authentication Error: ' . $authResult['error'],
            ];
        }
        
        $authToken = $authResult['token'];
        
        // Get stores for this client
        $response = store_icu_ApiCall(
            $params,
            'stores/list',
            ['user_id' => $storeInfo->user_id],
            'GET',
            $authToken
        );
        
        if (!$response['success']) {
            return [
                'Store Information' => 'API Error: ' . (isset($response['error']) ? $response['error'] : 'Unknown error'),
            ];
        }
        
        // Format store information for display
        $storeList = '';
        if (isset($response['data']['stores']) && count($response['data']['stores']) > 0) {
            $storeList = '<table class="table table-bordered">';
            $storeList .= '<thead><tr><th>Shop Handle</th><th>Status</th><th>Created</th></tr></thead>';
            $storeList .= '<tbody>';
            
            foreach ($response['data']['stores'] as $store) {
                $storeList .= '<tr>';
                $storeList .= '<td>' . htmlspecialchars($store['shop_handle']) . '</td>';
                $storeList .= '<td>' . htmlspecialchars($store['status']) . '</td>';
                $storeList .= '<td>' . htmlspecialchars(date('Y-m-d H:i:s', strtotime($store['created_at']))) . '</td>';
                $storeList .= '</tr>';
            }
            
            $storeList .= '</tbody></table>';
        } else {
            $storeList = 'No stores found for this user';
        }
        
        return [
            'Shop Handle' => $storeInfo->shop_handle,
            'Status' => ucfirst($storeInfo->status),
            'Created At' => $storeInfo->created_at,
            'Stores' => $storeList,
        ];
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'AdminServicesTabFields',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        
        return [
            'Error' => 'An error occurred: ' . $e->getMessage(),
        ];
    }
}

/**
 * Single sign-on for a service
 *
 * @param array $params Module parameters
 * @return array Single sign-on result
 */
function store_icu_ServiceSingleSignOn($params)
{
    try {
        // Get store information from database
        $storeInfo = Capsule::table('mod_store_icu')
            ->where('service_id', $params['serviceid'])
            ->first();
            
        if (!$storeInfo) {
            return [
                'success' => false,
                'errorMsg' => 'No store information found for this service',
            ];
        }
        
        // Authenticate with Store.icu API
        $authResult = store_icu_Authenticate($params);
        if (!$authResult['success']) {
            return [
                'success' => false,
                'errorMsg' => 'API Authentication Error: ' . $authResult['error'],
            ];
        }
        
        $authToken = $authResult['token'];
        
        // Get SSO URL for this store
        $response = store_icu_ApiCall(
            $params,
            'stores/get-sso-url',
            ['shop_handle' => $storeInfo->shop_handle],
            'POST',
            $authToken
        );
        
        if (!$response['success']) {
            return [
                'success' => false,
                'errorMsg' => 'API Error: ' . (isset($response['error']) ? $response['error'] : 'Unknown error'),
            ];
        }
        
        if (!isset($response['data']['sso_url'])) {
            return [
                'success' => false,
                'errorMsg' => 'No SSO URL returned from API',
            ];
        }
        
        return [
            'success' => true,
            'redirectTo' => $response['data']['sso_url'],
        ];
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'ServiceSingleSignOn',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        
        return [
            'success' => false,
            'errorMsg' => 'An error occurred: ' . $e->getMessage(),
        ];
    }
}

/**
 * Client area output
 *
 * @param array $params Module parameters
 * @return array Client area output
 */
function store_icu_ClientArea($params)
{
    try {
        // Get store information from database
        $storeInfo = Capsule::table('mod_store_icu')
            ->where('service_id', $params['serviceid'])
            ->first();
            
        if (!$storeInfo) {
            return [
                'templatefile' => 'templates/error',
                'vars' => [
                    'error' => 'No store information found for this service',
                ],
            ];
        }
        
        // Prepare variables for template
        $templateVars = [
            'serviceid' => $params['serviceid'],
            'shop_handle' => $storeInfo->shop_handle,
            'status' => ucfirst($storeInfo->status),
            'created_at' => $storeInfo->created_at,
            'LANG' => $params['_lang'],
        ];
        
        return [
            'templatefile' => 'templates/clientarea',
            'vars' => $templateVars,
        ];
    } catch (\Exception $e) {
        logModuleCall(
            'store_icu',
            'ClientArea',
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        
        return [
            'templatefile' => 'templates/error',
            'vars' => [
                'error' => 'An error occurred: ' . $e->getMessage(),
            ],
        ];
    }
}

/**
 * Custom buttons for client area
 *
 * @return array Custom buttons
 */
function store_icu_ClientAreaCustomButtonArray()
{
    return [
        'Login to Store' => 'sso',
    ];
}
