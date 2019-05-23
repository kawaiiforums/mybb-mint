# Mint

A MyBB extension that establishes a platform for virtual currency and inventory items held by users.

### Dependencies
- MyBB 1.8.x
- https://github.com/frostschutz/MyBB-PluginLibrary
- PHP >= 7.1

### Currency
The Currency platform allows units to enter and exit the circulation by applying user-specific _Balance Operations_ with delta values assigned to traceable _Termination Points_ designating functional origin or destination of currency units.

Manual Balance Transfers allow users to transfer currency units &mdash; using public and private transfers &mdash; between accounts with attached messages.

The plugin's _Content Entity Rewards_ handling allows modules to grant, void, and trace rewards for Entities of specified Content Types.

Selected user groups are able to _mint_ and _burn_ amounts assigned to individual accounts, inserting and removing currency from circulation, respectively.

### Items
The Items platform handles Item Types assigned to Categories, providing blueprints for invididual user-owned Items. Inventory Types and size bonuses allow to specify how many slots are available to hold Items. 

Manual Item Transactions allow users to exchange currency units and items.

Selected user groups are able to _forge_ amounts of an Item Type to be assigned to individual accounts, inserting it into the economy.

### Widgets & Variables
##### Global
- `{$mintBalance}` - formatted balance of the currently logged in user; empty if guest (`global_start` hook)
- `{$mintInventoryStatus}` - number of occupied slots in inventory of the currently logged in user; empty if guest (`global_start` hook)

##### User Profiles
- `{$mintRecentBalanceOperations}` - recent Balance Operations widget on user profiles (`member_profile_end` hook)
- `{$mintInventoryPreview}` - Inventory preview widget on user profiles (`member_profile_end` hook)
- `{$mint}`

##### Postbit
- `{$post['mintBalance']}` - formatted balance of the author (`global_start` hook)
- `{$post['mintInventoryStatus']}` - number of occupied slots in inventory of the author (`global_start` hook)

### Plugin Management Events
- **Install:**
  - Database structure created/altered
  - Cache entries created
  - Tasks registered
- **Uninstall:**
  - Database structure & data deleted/restored
  - Settings deleted
  - Cache entries removed
  - Tasks removed
- **Activate:**
  - Modules detected
  - Settings populated/updated
  - Templates & stylesheets inserted/altered
- **Deactivate:**
  - Templates & stylesheets removed/restored

### Development Mode
The plugin can operate in development mode, where plugin templates are being fetched directly from the `templates/` directory - set `mint\DEVELOPMENT_MODE` to `true` in `inc/plugins/mint.php`.
