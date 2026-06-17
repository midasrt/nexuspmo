<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Auth Routes
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');
$routes->get('/logout', 'Auth::logout');
$routes->post('/logout', 'Auth::logout');
$routes->get('/reset-password', 'Auth::resetPassword');
$routes->post('/reset-password', 'Auth::resetPassword');

$routes->get('/', 'Home::index');

// Projects CRUD and Detail
$routes->get('/projects', 'Projects::index');
$routes->post('/projects/create', 'Projects::create');
$routes->post('/projects/update/(:any)', 'Projects::update/$1');
$routes->post('/projects/delete/(:any)', 'Projects::delete/$1');
$routes->get('/project/(:any)', 'Projects::detail/$1');

// Project detail interactions
$routes->post('/project/(:any)/upload', 'Projects::uploadDocument/$1');
$routes->post('/project/(:any)/delete-doc/(:any)', 'Projects::deleteDocument/$1/$2');
$routes->post('/project/(:any)/toggle-action/(:any)', 'Projects::toggleActionItem/$1/$2');
$routes->post('/project/(:any)/health/update', 'Projects::updateHealthAjax/$1');
$routes->post('/project/(:any)/echo', 'Projects::echoCommand/$1');

// Project Detail AJAX endpoints
$routes->post('/project/(:any)/phase/create', 'Projects::createPhaseAjax/$1');
$routes->post('/project/(:any)/phase/update/(:num)', 'Projects::updatePhaseAjax/$1/$2');
$routes->post('/project/phase/delete/(:num)', 'Projects::deletePhaseAjax/$1');
$routes->post('/project/(:any)/phases/reorder', 'Projects::reorderPhasesAjax/$1');

// Subtasks AJAX endpoints
$routes->post('/project/(:any)/phase/(:num)/subtask/create', 'Projects::createSubtaskAjax/$1/$2');
$routes->post('/project/(:any)/phase/(:num)/subtask/update/(:num)', 'Projects::updateSubtaskAjax/$1/$2/$3');
$routes->post('/project/phase/subtask/delete/(:num)', 'Projects::deleteSubtaskAjax/$1');
$routes->post('/project/(:any)/phase/(:num)/subtask/toggle/(:num)', 'Projects::toggleSubtaskAjax/$1/$2/$3');

$routes->post('/project/(:any)/resources/assign', 'Projects::assignResourceAjax/$1');
$routes->post('/project/(:any)/resources/unassign', 'Projects::unassignResourceAjax/$1');
$routes->post('/project/(:any)/squad/assign', 'Projects::assignSquadAjax/$1');
$routes->post('/project/(:any)/phase/assign-resource/(:num)', 'Projects::assignPhaseResourceAjax/$1/$2');
$routes->post('/project/(:any)/phase/subtask/assign-resource/(:num)', 'Projects::assignSubtaskResourceAjax/$1/$2');

$routes->post('/project/(:any)/dependency/create', 'Projects::createDependencyAjax/$1');
$routes->post('/project/dependency/delete/(:num)', 'Projects::deleteDependencyAjax/$1');

$routes->post('/project/(:any)/escalation/create', 'Projects::createEscalationAjax/$1');
$routes->post('/project/escalation/delete/(:num)', 'Projects::deleteEscalationAjax/$1');
$routes->post('/project/(:any)/escalation/update/(:num)', 'Projects::updateEscalationAjax/$1/$2');

$routes->post('/project/(:any)/risk/create', 'Projects::createRiskAjax/$1');
$routes->post('/project/risk/delete/(:num)', 'Projects::deleteRiskAjax/$1');
$routes->post('/project/(:any)/risk/update/(:any)', 'Projects::updateRiskAjax/$1/$2');

$routes->post('/project/(:any)/action/create', 'Projects::createActionAjax/$1');
$routes->post('/project/action/delete/(:num)', 'Projects::deleteActionAjax/$1');

// Resources
$routes->get('/resource', 'Resources::index');
$routes->get('/member/(:num)', 'Resources::memberDetail/$1');
$routes->get('/resource-map', 'Resources::map');
$routes->post('/resource/create', 'Resources::create');
$routes->post('/resource/update/(:num)', 'Resources::update/$1');
$routes->post('/resource/delete/(:num)', 'Resources::delete/$1');

// Squads
$routes->get('/squads', 'Squads::index');
$routes->get('/squad-map', 'Squads::map');
$routes->post('/squads/create', 'Squads::create');
$routes->post('/squads/update/(:num)', 'Squads::update/$1');
$routes->post('/squads/delete/(:num)', 'Squads::delete/$1');
$routes->post('/squads/add-member', 'Squads::addMember');
$routes->post('/squads/remove-member/(:num)/(:num)', 'Squads::removeMember/$1/$2');

// Reports
$routes->get('/reports', 'Reports::index');

// Budget Stub
$routes->get('/budget', 'Budget::index');

// Settings
$routes->get('/settings', 'Settings::index');
$routes->post('/settings/status/create', 'Settings::createStatus');
$routes->post('/settings/status/update/(:num)', 'Settings::updateStatus/$1');
$routes->post('/settings/status/delete/(:num)', 'Settings::deleteStatus/$1');
$routes->post('/settings/org/create', 'Settings::createOrg');
$routes->post('/settings/org/update/(:num)', 'Settings::updateOrg/$1');
$routes->post('/settings/org/delete/(:num)', 'Settings::deleteOrg/$1');
$routes->post('/settings/resources/update', 'Settings::updateResources');

// User Management
$routes->get('/users', 'Users::index');
$routes->post('/users/create', 'Users::create');
$routes->post('/users/update/(:num)', 'Users::update/$1');
$routes->post('/users/delete/(:num)', 'Users::delete/$1');
