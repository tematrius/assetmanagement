<?php

declare(strict_types=1);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/password/change', [AuthController::class, 'showChangePassword']);
$router->post('/password/change', [AuthController::class, 'changePassword']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/mon-materiel', [PortalController::class, 'equipment']);
$router->get('/mes-demandes', [PortalController::class, 'requests']);
$router->get('/mes-demandes/archives', [PortalController::class, 'requestArchives']);
$router->get('/validations', [PortalController::class, 'validations']);
$router->get('/mon-profil', [PortalController::class, 'profile']);
$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/read-all', [NotificationController::class, 'readAll']);
$router->post('/notifications/{id}/read', [NotificationController::class, 'read']);

$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/categories/create', [CategoryController::class, 'create']);
$router->post('/categories', [CategoryController::class, 'store']);
$router->get('/categories/import', [CategoryController::class, 'importForm']);
$router->post('/categories/import', [CategoryController::class, 'import']);
$router->get('/categories/export.xlsx', [CategoryController::class, 'exportXlsx']);
$router->get('/categories/{id}', [CategoryController::class, 'show']);
$router->get('/categories/{id}/edit', [CategoryController::class, 'edit']);
$router->put('/categories/{id}', [CategoryController::class, 'update']);
$router->get('/categories/{id}/attributes', [CategoryController::class, 'attributes']);

$router->get('/stocks', [StockController::class, 'index']);
$router->get('/stocks/create', [StockController::class, 'create']);
$router->get('/stocks/equipements', [StockController::class, 'equipements']);
$router->post('/stocks', [StockController::class, 'store']);
$router->get('/stocks/{id}', [StockController::class, 'show']);
$router->get('/stocks/{id}/edit', [StockController::class, 'edit']);
$router->put('/stocks/{id}', [StockController::class, 'update']);
$router->post('/stocks/{id}/assign', [StockController::class, 'assign']);
$router->post('/stocks/{id}/return', [StockController::class, 'restore']);
$router->post('/stocks/{id}/state', [StockController::class, 'changeState']);

$router->get('/utilisateurs', [UtilisateurController::class, 'index']);
$router->get('/utilisateurs/equipements/search', [UtilisateurController::class, 'searchEquipements']);
$router->get('/utilisateurs/import', [UtilisateurController::class, 'importForm']);
$router->post('/utilisateurs/import', [UtilisateurController::class, 'import']);
$router->get('/utilisateurs/export', [UtilisateurController::class, 'exportCsv']);
$router->get('/utilisateurs/export.xlsx', [UtilisateurController::class, 'exportXlsx']);
$router->get('/utilisateurs/create', [UtilisateurController::class, 'create']);
$router->post('/utilisateurs', [UtilisateurController::class, 'store']);
$router->get('/utilisateurs/{id}', [UtilisateurController::class, 'show']);
$router->get('/utilisateurs/{id}/edit', [UtilisateurController::class, 'edit']);
$router->put('/utilisateurs/{id}', [UtilisateurController::class, 'update']);
$router->delete('/utilisateurs/{id}', [UtilisateurController::class, 'delete']);

$router->get('/equipements', [EquipementController::class, 'index']);
$router->get('/equipements/import', [EquipementController::class, 'importForm']);
$router->post('/equipements/import', [EquipementController::class, 'import']);
$router->post('/equipements/bulk-action', [EquipementController::class, 'bulkAction']);
$router->get('/equipements/create', [EquipementController::class, 'create']);
$router->get('/equipements/categories/{id}', [EquipementController::class, 'category']);
$router->post('/equipements', [EquipementController::class, 'store']);
$router->get('/equipements/{id}', [EquipementController::class, 'show']);
$router->get('/equipements/{id}/edit', [EquipementController::class, 'edit']);
$router->put('/equipements/{id}', [EquipementController::class, 'update']);
$router->post('/equipements/{id}/assign', [EquipementController::class, 'assign']);
$router->post('/equipements/{id}/change-state', [EquipementController::class, 'changeState']);
$router->post('/equipements/{id}/transfer', [EquipementController::class, 'transfer']);
$router->post('/equipements/{id}/return', [EquipementController::class, 'returnToStock']);
$router->post('/equipements/{id}/maintenance', [EquipementController::class, 'maintenance']);
$router->post('/equipements/{id}/declassify', [EquipementController::class, 'declassify']);

$router->get('/equipements/{id}/history', [EquipementController::class, 'historyByEquipement']);
$router->get('/equipment-types/{id}/attributes', [EquipementController::class, 'attributes']);

$router->get('/mouvements', [MouvementController::class, 'index']);
$router->get('/mouvements/create', [MouvementController::class, 'create']);
$router->get('/mouvements/equipements/search', [MouvementController::class, 'searchEquipements']);
$router->post('/mouvements', [MouvementController::class, 'store']);
$router->get('/mouvements/export.pdf', [MouvementController::class, 'exportPdf']);
$router->get('/mouvements/{id}', [MouvementController::class, 'show']);
$router->get('/mouvements/{id}/pdf', [MouvementController::class, 'pdf']);

$router->get('/demandes', [DemandeController::class, 'index']);
$router->get('/demandes/archives', [DemandeController::class, 'archives']);
$router->get('/demandes/create', [DemandeController::class, 'create']);
$router->post('/demandes', [DemandeController::class, 'store']);
$router->get('/demandes/{id}/edit', [DemandeController::class, 'edit']);
$router->put('/demandes/{id}', [DemandeController::class, 'update']);
$router->post('/demandes/{id}/fulfill', [DemandeController::class, 'fulfill']);
$router->get('/demandes/{id}', [DemandeController::class, 'show']);
$router->post('/demandes/{id}/validate', [DemandeController::class, 'validateDemand']);
$router->get('/demandes/{id}/print', [DemandeController::class, 'printable']);
$router->get('/demandes/{id}/signed', [DemandeController::class, 'signed']);
$router->get('/demandes/{id}/pdf', [DemandeController::class, 'pdf']);

$router->get('/reporting', [ReportingController::class, 'index']);
$router->get('/reporting/export/sites', [ReportingController::class, 'exportSiteCsv']);
$router->get('/reporting/export/departements', [ReportingController::class, 'exportDepartementCsv']);
$router->get('/reporting/export/sites.xlsx', [ReportingController::class, 'exportSiteXlsx']);
$router->get('/reporting/export/departements.xlsx', [ReportingController::class, 'exportDepartementXlsx']);
$router->get('/reporting/export/global.xlsx', [ReportingController::class, 'exportGlobalXlsx']);
