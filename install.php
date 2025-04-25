<?php
/**
 * WHMCS Store.icu Provisioning Module Installer
 *
 * This file handles the creation of the necessary database tables for the module.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Create the mod_store_icu table if it doesn't exist
 */
if (!Capsule::schema()->hasTable('mod_store_icu')) {
    try {
        Capsule::schema()->create('mod_store_icu', function ($table) {
            $table->increments('id');
            $table->integer('service_id')->unique();
            $table->string('user_id', 255);
            $table->string('store_id', 255);
            $table->string('shop_handle', 255);
            $table->enum('status', ['active', 'suspended', 'terminated'])->default('active');
            $table->timestamps();
            
            // Add indexes
            $table->index('service_id');
            $table->index('shop_handle');
        });
        
        // Log successful creation
        logActivity("Store.icu Module: Successfully created mod_store_icu table");
    } catch (\Exception $e) {
        // Log error
        logActivity("Store.icu Module: Failed to create mod_store_icu table - " . $e->getMessage());
    }
}

/**
 * Check if we need to upgrade from an older version of the module
 */
if (Capsule::schema()->hasTable('mod_store_icu')) {
    // Example of adding a column if needed in a future update
    // if (!Capsule::schema()->hasColumn('mod_store_icu', 'new_column_name')) {
    //     try {
    //         Capsule::schema()->table('mod_store_icu', function ($table) {
    //             $table->string('new_column_name')->nullable();
    //         });
    //         logActivity("Store.icu Module: Successfully added new_column_name column to mod_store_icu table");
    //     } catch (\Exception $e) {
    //         logActivity("Store.icu Module: Failed to add new_column_name column to mod_store_icu table - " . $e->getMessage());
    //     }
    // }
}
