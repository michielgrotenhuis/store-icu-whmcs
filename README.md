# Store.icu WHMCS Provisioning Module

This module integrates WHMCS with the Store.icu ecommerce sitebuilder API, allowing web hosting companies to offer store-building services to their clients.

## Features

- **Automated Account Provisioning**: Automatically creates user accounts and provisions stores on the Store.icu platform.
- **Account Management**: Supports suspend, unsuspend, terminate, and package change operations.
- **Single Sign-On**: Allows clients to directly access their store from WHMCS client area.
- **Admin Interface**: View client's stores and their status directly from the WHMCS admin area.

## Installation

1. Download and extract the module files to your WHMCS installation.
2. Place the `store_icu` folder in the `/modules/servers/` directory of your WHMCS installation.
3. Navigate to Setup > Products/Services > Servers in your WHMCS admin area.
4. The module will automatically create its required database table on first use.

## Configuration

### Server Setup (Optional)

Although this module doesn't require a server to be set up in WHMCS, you can create one if you want to organize your Store.icu services:

1. Go to Setup > Products/Services > Servers
2. Click "Add New Server"
3. Enter a name for the server (e.g., "Store.icu")
4. Select "Store.icu Ecommerce Sitebuilder" as the server type
5. Save the server

### Product Setup

1. Create a new product in WHMCS
2. In the "Module Settings" tab, select "Store.icu Ecommerce Sitebuilder" as the module
3. Configure the following settings:
   - **Distributor Email ID**: Your Store.icu distributor/reseller email
   - **Distributor Password**: Your Store.icu distributor/reseller password
   - **Default Store Currency**: (Optional) Default currency for new stores (e.g., USD)
   - **Default Store Timezone**: (Optional) Default timezone for new stores
   - **Default Store Country**: (Optional) Default country code for new stores
   - **Default Store Language**: (Optional) Default language for new stores
   - **Package Type**: Select the package type for new stores (basic, standard, premium, ultimate)

## Usage

### Client Area

Clients can:
- View their store information
- Access their store directly via Single Sign-On

### Admin Area

Admins can:
- View client's stores and their status
- Perform suspend, unsuspend, terminate operations
- Change package types

## Support

For support, please contact your Store.icu account manager or submit a support ticket.

## License

This module is provided under a commercial license by Store.icu. Unauthorized distribution is prohibited.
