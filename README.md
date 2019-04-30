# Mint

A MyBB extension that establishes a platform for virtual currency and inventory items held by users.

### Currency
Currency units can enter and exit the circulation by applying user-specific _Balance Operations_ with delta values assigned to traceable _Termination Points_ designating functional origin or destination of currency units.

Manual Balance Transfers allow users to transfer currency units between accounts.

The plugin's _Content Entity Rewards_ handling allows modules to grant, void, and trace rewards for Entities of specified Content Types.

### Items

  
### Dependencies
- MyBB 1.8.x
- https://github.com/frostschutz/MyBB-PluginLibrary
- PHP >= 7.1

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
